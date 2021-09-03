<?php

namespace DoubleThreeDigital\GuestEntries\Http\Controllers;

use DoubleThreeDigital\GuestEntries\Http\Requests\DestroyRequest;
use DoubleThreeDigital\GuestEntries\Http\Requests\StoreRequest;
use DoubleThreeDigital\GuestEntries\Http\Requests\UpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Statamic\Facades\Entry;

class GuestEntryController extends Controller
{
    protected $ignoredParameters = ['_collection', '_id', '_redirect', '_error_redirect', '_request', 'slug'];

    public function store(StoreRequest $request)
    {
        $entry = Entry::make()
            ->collection($request->get('_collection'));

        if ($request->has('slug')) {
            $entry->slug($request->get('slug'));
        } else {
            $entry->slug(Str::slug($request->get('title')));
        }

        foreach (Arr::except($request->all(), $this->ignoredParameters) as $key => $value) {
            $entry->set($key, $value);
        }

        $entry->save();

        return $this->withSuccess($request);
    }

    public function update(UpdateRequest $request)
    {
        $entry = Entry::find($request->get('_id'));

        if ($request->has('slug')) {
            $entry->slug($request->get('slug'));
        }

        foreach (Arr::except($request->all(), $this->ignoredParameters) as $key => $value) {
            $entry->set($key, $value);
        }

        $entry->save();

        return $this->withSuccess($request);
    }

    public function destroy(DestroyRequest $request)
    {
        $entry = Entry::find($request->get('_id'));

        $entry->delete();

        return $this->withSuccess($request);
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
