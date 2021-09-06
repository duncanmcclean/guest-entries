<?php

namespace DoubleThreeDigital\GuestEntries\Http\Controllers;

use DoubleThreeDigital\GuestEntries\Events\GuestEntryCreated;
use DoubleThreeDigital\GuestEntries\Events\GuestEntryDeleted;
use DoubleThreeDigital\GuestEntries\Events\GuestEntryUpdated;
use DoubleThreeDigital\GuestEntries\Http\Requests\DestroyRequest;
use DoubleThreeDigital\GuestEntries\Http\Requests\StoreRequest;
use DoubleThreeDigital\GuestEntries\Http\Requests\UpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;

class GuestEntryController extends Controller
{
    protected $ignoredParameters = ['_collection', '_id', '_redirect', '_error_redirect', '_request', 'slug', 'published'];

    public function store(StoreRequest $request)
    {
        if (! $this->honeypotPassed($request)) {
            return $this->withSuccess($request);
        }

        $collection = Collection::find($request->get('_collection'));

        $entry = Entry::make()
            ->collection($collection->handle())
            ->published(false);

        if ($request->has('slug')) {
            $entry->slug($request->get('slug'));
        } else {
            $entry->slug(Str::slug($request->get('title')));
        }

        if ($request->has('published')) {
            $entry->published($request->get('published') == '1' || $request->get('published') == 'true' ? true : false);
        }

        foreach (Arr::except($request->all(), $this->ignoredParameters) as $key => $value) {
            $entry->set($key, $value);
        }

        if ($collection->dated() && ! $entry->has('date')) {
            $entry->set('date', now()->timestamp);
        }

        $entry->save();
        $entry->touch();

        event(new GuestEntryCreated($entry));

        return $this->withSuccess($request);
    }

    public function update(UpdateRequest $request)
    {
        if (! $this->honeypotPassed($request)) {
            return $this->withSuccess($request);
        }

        $entry = Entry::find($request->get('_id'));

        if ($request->has('slug')) {
            $entry->slug($request->get('slug'));
        }

        if ($request->has('published')) {
            $entry->published($request->get('published') == 1 || $request->get('published') == 'true' ? true : false);
        }

        foreach (Arr::except($request->all(), $this->ignoredParameters) as $key => $value) {
            $entry->set($key, $value);
        }

        $entry->save();
        $entry->touch();

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

    protected function honeypotPassed(Request $request): ?bool
    {
        $honeypot = config('guest-entries.honeypot');

        if (! $honeypot) {
            return true;
        }

        return empty($request->get($honeypot));
    }

    protected function withSuccess(Request $request, array $data = [])
    {
        if ($request->wantsJson()) {
            $data = array_merge($data, [
                'status'  => 'success',
                'message' => null,
            ]);

            return response()->json($data);
        }

        return $request->_redirect ?
            redirect($request->_redirect)->with($data)
            : back()->with($data);
    }

    protected function withErrors(Request $request, string $errorMessage)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'error',
                'message' => $errorMessage,
            ]);
        }

        return $request->_error_redirect
            ? redirect($request->_error_redirect)->withErrors($errorMessage, 'guest-entries')
            : back()->withErrors($errorMessage, 'guest-entries');
    }
}
