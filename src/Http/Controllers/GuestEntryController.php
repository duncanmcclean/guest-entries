<?php

namespace DuncanMcClean\GuestEntries\Http\Controllers;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use DuncanMcClean\GuestEntries\Events\GuestEntryCreated;
use DuncanMcClean\GuestEntries\Events\GuestEntryDeleted;
use DuncanMcClean\GuestEntries\Events\GuestEntryUpdated;
use DuncanMcClean\GuestEntries\Exceptions\AssetContainerNotSpecified;
use DuncanMcClean\GuestEntries\Http\Requests\DestroyRequest;
use DuncanMcClean\GuestEntries\Http\Requests\StoreRequest;
use DuncanMcClean\GuestEntries\Http\Requests\UpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Statamic\Contracts\Assets\AssetContainer as AssetContainerContract;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site as SiteFacade;
use Statamic\Facades\Stache;
use Statamic\Facades\User;
use Statamic\Fields\Field;
use Statamic\Fieldtypes\Assets\Assets as AssetFieldtype;
use Statamic\Fieldtypes\Date as DateFieldtype;
use Statamic\Fieldtypes\Grid;
use Statamic\Fieldtypes\Replicator;
use Statamic\Revisions\Revision;
use Statamic\Rules\AllowedFile;
use Statamic\Sites\Site;
use Statamic\Support\Svg;
use TypeError;

class GuestEntryController extends Controller
{
    protected $ignoredParameters = ['_token', '_collection', '_id', '_redirect', '_error_redirect', '_request', 'slug', 'published'];

    protected $reservedParameters = ['id', 'origin', 'blueprint', 'template', 'layout', 'redirect', 'protect', 'author', 'order', 'updated_by', 'updated_at'];

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
        if (config('statamic.eloquent-driver.entries.driver', 'file') === 'file') {
            $entry->id(Stache::generateId());
        }

        if ($collection->dated()) {
            $this->ignoredParameters[] = 'date';
            $this->setEntryDate($entry, $request->get('date') ?? now());
        }

        if ($request->has('published')) {
            $entry->published($request->get('published') == '1' || $request->get('published') == 'true' ? true : false);
        }

        foreach (Arr::except($request->all(), $this->ignoredParameters) as $key => $value) {
            /** @var Field $blueprintField */
            $field = $collection->entryBlueprint()->field($key);

            if (! $field && in_array($key, $this->reservedParameters)) {
                continue;
            }

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

    private function setEntryDate($entry, mixed $date): void
    {
        try {
            $entry->date($date);
        } catch (InvalidFormatException|TypeError) {
            throw ValidationException::withMessages([
                'date' => __('validation.date', ['attribute' => 'date']),
            ]);
        }
    }

    public function update(UpdateRequest $request)
    {
        if (! $this->honeypotPassed($request)) {
            return $this->withSuccess($request);
        }

        /** @var \Statamic\Entries\Entry $entry */
        $entry = $request->entry();

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
            /** @var Field $blueprintField */
            $field = $entry->blueprint()->field($key);

            if (! $field && in_array($key, $this->reservedParameters)) {
                continue;
            }

            $data[$key] = $field
                ? $this->processField($entry, $field, $key, $value, $request)
                : $value;
        }

        if ($entry->revisionsEnabled()) {
            /** @var Revision $revision */
            $revision = $entry->makeWorkingCopy();

            $revision->attributes([
                'title' => $entry->get('title'),
                'slug' => $entry->slug(),
                'published' => $entry->published(),
                'data' => $data,
            ]);

            if ($entry->collection()->dated() && $request->has('date')) {
                $revision->date($this->parseDate('date', $request->get('date')));
            }

            if ($request->user()) {
                $revision->user($revision->user());
            }

            $revision->message(__('Guest Entry Updated'));

            $revision->save();
            $entry->save();
        } else {
            if ($entry->collection()->dated() && $request->has('date')) {
                $this->setEntryDate($entry, $request->get('date'));
            }

            $entry->data($data);
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

        $entry = $request->entry();

        $entry->delete();

        event(new GuestEntryDeleted($entry));

        return $this->withSuccess($request);
    }

    protected function processField($entry, Field $field, $key, $value, $request): mixed
    {
        if ($field && $field->fieldtype() instanceof Replicator) {
            $replicatorField = $field;
            $sets = $replicatorField->fieldtype()->flattenedSetsConfig();

            return collect($value)
                ->map(function ($item, $index) use ($entry, $replicatorField, $sets, $request) {
                    $set = $item['type'] ?? $sets->keys()->first();

                    if (! is_string($set) || ! $sets->has($set)) {
                        $key = "{$replicatorField->handle()}.{$index}.type";

                        throw ValidationException::withMessages([
                            $key => __('validation.in', ['attribute' => $key]),
                        ]);
                    }

                    $setFields = $replicatorField->fieldtype()->fields($set, $index);

                    return collect($item)
                        ->reject(function ($value, $fieldHandle) {
                            return $fieldHandle === 'type';
                        })
                        ->map(function ($value, $fieldHandle) use ($entry, $replicatorField, $index, $setFields, $request) {
                            $field = $setFields->get($fieldHandle);

                            $key = "{$replicatorField->handle()}.{$index}.{$fieldHandle}";

                            return $field
                                ? $this->processField($entry, $field, $key, $value, $request)
                                : $value;
                        })
                        ->merge(['type' => $set])
                        ->toArray();
                })
                ->toArray();
        }

        if ($field && $field->fieldtype() instanceof Grid) {
            $gridField = $field;

            return collect($value)
                ->map(function ($rowValue, $index) use ($entry, $gridField, $request) {
                    return collect($rowValue)
                        ->map(function ($value, $fieldHandle) use ($entry, $gridField, $request, $index) {
                            $field = $gridField->fieldtype()->fields()->get($fieldHandle);

                            $key = "{$gridField->handle()}.{$index}.{$fieldHandle}";

                            return $field
                                ? $this->processField($entry, $field, $key, $value, $request)
                                : $value;
                        })
                        ->toArray();
                })
                ->toArray();
        }

        if ($field && $field->fieldtype() instanceof AssetFieldtype) {
            $value = $this->uploadFile($entry, $key, $field, $request);
        }

        if ($value && $field && $field->fieldtype() instanceof DateFieldtype) {
            if (is_array($value) && isset($value['start']) && isset($value['end'])) {
                $start = $this->parseDate("{$key}.start", $value['start']);
                $end = $this->parseDate("{$key}.end", $value['end']);

                $format = $field->fieldtype()->config(
                    'format',
                    strlen($value['start']) > 10 ? $field->fieldtype()::DEFAULT_DATETIME_FORMAT : $field->fieldtype()::DEFAULT_DATE_FORMAT
                );

                $value = [
                    'start' => $start->format($format),
                    'end' => $end->format($format),
                ];
            } else {
                // Handle single mode (value is a string)
                $date = $this->parseDate($key, $value);

                $format = $field->fieldtype()->config(
                    'format',
                    strlen($value) > 10 ? $field->fieldtype()::DEFAULT_DATETIME_FORMAT : $field->fieldtype()::DEFAULT_DATE_FORMAT
                );

                $value = $date->format($format);
            }
        }

        return $value;
    }

    private function parseDate(string $key, mixed $value): Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (InvalidFormatException|TypeError) {
            throw ValidationException::withMessages([
                $key => __('validation.date', ['attribute' => $key]),
            ]);
        }
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
            if ($this->isSvg($uploadedFile)) {
                File::put($uploadedFile->getPathname(), Svg::sanitize(File::get($uploadedFile->getPathname())));
            }

            $folder = match (true) {
                ! is_null($field->get('folder')) => $field->get('folder'),
                $field->get('dynamic') === 'id' => $entry->id(),
                $field->get('dynamic') === 'slug' => Str::slug($entry->slug() ?? $request->get('slug') ?? $request->get('title'), '-', $entry->site()->lang()),
                $field->get('dynamic') === 'author' => $this->authorFolder($entry, $request),
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

        foreach ($this->existingFiles($entry, $key, $field, $assetContainer, $request) as $existingFile) {
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

    private function isSvg(UploadedFile $file): bool
    {
        return Str::lower(trim($file->getClientOriginalExtension())) === 'svg'
            || $file->getMimeType() === 'image/svg+xml';
    }

    private function authorFolder(EntryContract $entry, Request $request): ?string
    {
        $author = SupportCollection::wrap($entry->author ?? $request->get('author'))->first();

        if (is_object($author)) {
            return $author->id();
        }

        return User::find($author)?->id();
    }

    private function existingFiles(EntryContract $entry, string $key, Field $field, AssetContainerContract $assetContainer, Request $request): array
    {
        $filesOnEntry = Arr::flatten(Arr::wrap($entry->value(Str::before($key, '.'))));

        return collect(Arr::wrap($request->get($key)))
            ->filter(fn ($path) => is_string($path) && $assetContainer->asset($path))
            ->filter(fn (string $path) => in_array($path, $filesOnEntry) || $this->isWithinFolder($path, $field->get('folder')))
            ->values()
            ->all();
    }

    private function isWithinFolder(string $path, ?string $folder): bool
    {
        $folder = trim((string) $folder, '/');

        if ($folder === '') {
            return true;
        }

        return Str::startsWith($path, "{$folder}/");
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
        $site = $request->get('site');

        if (is_string($site) && SiteFacade::get($site)) {
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
