<?php

namespace DuncanMcClean\GuestEntries\Http\Controllers;

use Carbon\Carbon;
use DuncanMcClean\GuestEntries\Events\GuestEntryCreated;
use DuncanMcClean\GuestEntries\Events\GuestEntryDeleted;
use DuncanMcClean\GuestEntries\Events\GuestEntryUpdated;
use DuncanMcClean\GuestEntries\Exceptions\AssetContainerNotSpecified;
use DuncanMcClean\GuestEntries\Http\Requests\DestroyRequest;
use DuncanMcClean\GuestEntries\Http\Requests\StoreRequest;
use DuncanMcClean\GuestEntries\Http\Requests\UpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Rhukster\DomSanitizer\DOMSanitizer;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site as SiteFacade;
use Statamic\Facades\Stache;
use Statamic\Fields\Field;
use Statamic\Fieldtypes\Assets\Assets as AssetFieldtype;
use Statamic\Fieldtypes\Date as DateFieldtype;
use Statamic\Fieldtypes\Replicator;
use Statamic\Rules\AllowedFile;
use Statamic\Sites\Site;

class GuestEntryController extends Controller
{
    protected $ignoredParameters = ['_token', '_collection', '_id', '_redirect', '_error_redirect', '_request', 'slug', 'published'];

    public function store(StoreRequest $request)
    {
        if (! $this->honeypotPassed($request)) {
            return $this->withSuccess($request);
        }

        $collection = Collection::find($request->get('_collection'));

        /** @var \Statamic\Entries\Entry $entry */
        $entry = Entry::make()
            ->collection($collection->handle())
            ->locale($site = $this->guessSiteFromRequest($request))
            ->published(false);

        // By setting the ID here, it can be used as a dynamic folder name in the Assets fieldtype.
        // However, this will only work for the Stache driver.
        if (config('statamic.eloquent-driver.entries.driver' === 'file')) {
            $entry->id(Stache::generateId());
        }

        if ($collection->dated()) {
            $this->ignoredParameters[] = 'date';
            $entry->date($request->get('date') ?? now());
        }

        if ($request->has('published')) {
            $entry->published($request->get('published') == '1' || $request->get('published') == 'true' ? true : false);
        }

        foreach (Arr::except($request->all(), $this->ignoredParameters) as $key => $value) {
            /** @var \Statamic\Fields\Field $blueprintField */
            $field = $collection->entryBlueprint()->field($key);

            $entry->set(
                $key,
                $field
                    ? $this->processField($entry, $field, $key, $value, $request)
                    : $value
            );
        }

        if ($request->has('slug')) {
            $entry->slug($request->get('slug'));
        } elseif ($collection->entryBlueprint()->hasField('title')) {
            $entry->slug($this->generateEntrySlug($entry));
        }

        if ($collection->hasStructure() && $structure = $collection->structure()) {
            $tree = $structure->in($site->handle());

            $entry->afterSave(function ($entry) use ($tree) {
                $tree->append($entry)->save();

                Stache::store('entries')
                    ->store($entry->collectionHandle())
                    ->updateUris([$entry->id()]);
            });
        }

        $entry->touch();

        event(new GuestEntryCreated($entry));

        return $this->withSuccess($request);
    }

    public function update(UpdateRequest $request)
    {
        if (! $this->honeypotPassed($request)) {
            return $this->withSuccess($request);
        }

        /** @var \Statamic\Entries\Entry $entry */
        $entry = Entry::find($request->get('_id'));

        /** @var array $data */
        $data = $entry->data()->toArray();

        if ($request->has('slug')) {
            $entry->slug($request->get('slug'));
        }

        if ($entry->collection()->dated()) {
            $this->ignoredParameters[] = 'date';
        }

        if ($request->has('published')) {
            $entry->published($request->get('published') == 1 || $request->get('published') == 'true' ? true : false);
        }

        foreach (Arr::except($request->all(), $this->ignoredParameters) as $key => $value) {
            /** @var \Statamic\Fields\Field $blueprintField */
            $field = $entry->blueprint()->field($key);

            $data[$key] = $field
                ? $this->processField($entry, $field, $key, $value, $request)
                : $value;
        }

        if ($entry->revisionsEnabled()) {
            /** @var \Statamic\Revisions\Revision $revision */
            $revision = $entry->makeWorkingCopy();
            $revision->id($entry->id());

            $revision->attributes([
                'title' => $entry->get('title'),
                'slug' => $entry->slug(),
                'published' => $entry->published(),
                'data' => $data,
            ]);

            if ($entry->collection()->dated() && $request->has('date')) {
                $revision->date($request->get('date'));
            }

            if ($request->user()) {
                $revision->user($revision->user());
            }

            $revision->message(__('Guest Entry Updated'));
            $revision->action('revision');

            $revision->save();
            $entry->save();
        } else {
            $entry->data($data);

            if ($entry->collection()->dated() && $request->has('date')) {
                $entry->date($request->get('date'));
            }

            $entry->touch();
        }

        event(new GuestEntryUpdated($entry));

        return $this->withSuccess($request);
    }

    public function destroy(DestroyRequest $request)
    {
        if (! $this->honeypotPassed($request)) {
            return $this->withSuccess($request);
        }

        $entry = Entry::find($request->get('_id'));

        $entry->delete();

        event(new GuestEntryDeleted($entry));

        return $this->withSuccess($request);
    }

    protected function processField($entry, Field $field, $key, $value, $request): mixed
    {
        if ($field && $field->fieldtype() instanceof Replicator) {
            $replicatorField = $field;

            return collect($value)
                ->map(function ($item, $index) use ($entry, $replicatorField, $request) {
                    $set = $item['type'] ?? array_values($replicatorField->fieldtype()->config('sets'))[0];

                    return collect($item)
                        ->reject(function ($value, $fieldHandle) {
                            return $fieldHandle === 'type';
                        })
                        ->map(function ($value, $fieldHandle) use ($entry, $replicatorField, $index, $set, $request) {
                            $field = collect($set['fields'])
                                ->where('handle', $fieldHandle)
                                ->map(function ($field) {
                                    return new Field($field['handle'], $field['field']);
                                })
                                ->first();

                            if (! $field) {
                                return $value;
                            }

                            $key = "{$replicatorField->handle()}.{$index}.{$fieldHandle}";

                            return $field
                                ? $this->processField($entry, $field, $key, $value, $request)
                                : $value;
                        })
                        ->merge([
                            'type' => $item['type'] ?? array_keys($replicatorField->fieldtype()->config('sets'))[0],
                        ])
                        ->toArray();
                })
                ->toArray();
        }

        if ($field && $field->fieldtype() instanceof AssetFieldtype) {
            $value = $this->uploadFile($entry, $key, $field, $request);
        }

        if ($value && $field && $field->fieldtype() instanceof DateFieldtype) {
            $format = $field->fieldtype()->config(
                'format',
                strlen($value) > 10 ? $field->fieldtype()::DEFAULT_DATETIME_FORMAT : $field->fieldtype()::DEFAULT_DATE_FORMAT
            );

            $value = Carbon::parse($value)->format($format);
        }

        return $value;
    }

    protected function generateEntrySlug($entry): string
    {
        $iteration = 0;

        $slug = $originalSlug = Str::slug($entry->get('title') ?? $entry->autoGeneratedTitle(), '-', $entry->site()->lang());

        while (true) {
            $query = Entry::query()
                ->where('collection', $entry->collectionHandle())
                ->where('site', $entry->site()->handle())
                ->where('slug', $slug);

            if ($entry->collection()->structure()) {
                $query->where('parent', $entry->parent());
            }

            $exists = $query->count() > 0;

            if (! $exists) {
                return $slug;
            }

            $iteration++;
            $slug = $originalSlug.'-'.$iteration;
        }
    }

    protected function uploadFile($entry, string $key, Field $field, Request $request)
    {
        if (! isset($field->config()['container'])) {
            throw new AssetContainerNotSpecified("Please specify an asset container on your [{$key}] field, in order for file uploads to work.");
        }

        /** @var \Statamic\Assets\AssetContainer $assetContainer */
        $assetContainer = AssetContainer::findByHandle($field->config()['container']);

        $files = [];

        // Handle uploaded files.
        $uploadedFiles = $request->file($key);

        if (! is_array($uploadedFiles)) {
            $uploadedFiles = [$uploadedFiles];
        }

        $uploadedFiles = collect($uploadedFiles)
            ->each(function ($file) use ($key) {
                $validator = Validator::make([$key => $file], [
                    $key => ['file', new AllowedFile],
                ]);

                if ($validator->fails()) {
                    throw ValidationException::withMessages($validator->errors()->toArray());
                }
            })
            ->filter()
            ->toArray();

        /* @var \Illuminate\Http\Testing\File $file */
        foreach ($uploadedFiles as $uploadedFile) {
            if (Str::endsWith($uploadedFile->getClientOriginalExtension(), 'svg')) {
                $sanitizer = new DOMSanitizer(DOMSanitizer::SVG);

                $contents = $sanitizer->sanitize($svg = File::get($uploadedFile->getPathname()), [
                    'remove-xml-tags' => ! Str::startsWith($svg, '<?xml'),
                ]);

                File::put($uploadedFile->getPathname(), $contents);
            }

            $folder = match (true) {
                ! is_null($field->get('folder')) => $field->get('folder'),
                $field->get('dynamic') === 'id' => $entry->id(),
                $field->get('dynamic') === 'slug' => $entry->slug() ?? $request->get('slug') ?? Str::slug($request->get('title'), '-'),
                $field->get('dynamic') === 'author' => $entry->author ?? $request->get('author'),
                default => '',
            };

            $path = '/'.$uploadedFile->storeAs(
                path: $folder,
                name: now()->timestamp.'-'.$uploadedFile->getClientOriginalName(),
                options: ['disk' => $assetContainer->diskHandle()]
            );

            // Does path start with a '/'? If so, strip it off.
            if (substr($path, 0, 1) === '/') {
                $path = substr($path, 1);
            }

            // Ensure asset is created in Statamic (otherwise, it won't show up in
            // the Control Panel for sites with the Stache watcher disabled).
            $asset = Asset::make()
                ->container($assetContainer->handle())
                ->path($path);

            $asset->save();

            // Push to the array
            $files[] = $path;
        }

        // Handle existing files.
        $existingFiles = $request->get($key, []);

        foreach ($existingFiles as $existingFile) {
            $files[] = $existingFile;
        }

        if (count($files) === 0) {
            return null;
        }

        if (count($files) === 1) {
            return $files[0];
        }

        return $files;
    }

    protected function honeypotPassed(Request $request): ?bool
    {
        $honeypot = config('guest-entries.honeypot');

        if (! $honeypot) {
            return true;
        }

        return empty($request->get($honeypot));
    }

    protected function guessSiteFromRequest($request): Site
    {
        if ($site = $request->get('site')) {
            return SiteFacade::get($site);
        }

        foreach (SiteFacade::all() as $site) {
            if (Str::contains($request->url(), $site->url())) {
                return $site;
            }
        }

        if ($referer = $request->header('referer')) {
            foreach (SiteFacade::all() as $site) {
                if (Str::contains($referer, $site->url())) {
                    return $site;
                }
            }
        }

        return SiteFacade::current();
    }

    protected function withSuccess(Request $request, array $data = [])
    {
        if ($request->wantsJson()) {
            $data = array_merge($data, [
                'status' => 'success',
                'message' => null,
            ]);

            return response()->json($data);
        }

        $request->session()->flash('guest-entries.success', true);

        return $request->_redirect ?
            redirect($request->_redirect)->with($data)
            : back()->with($data);
    }
}
