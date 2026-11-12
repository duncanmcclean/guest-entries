<?php

namespace DuncanMcClean\GuestEntries\Tags;

use DuncanMcClean\GuestEntries\Form;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Facades\Entry;
use Statamic\Tags\Tags;

class GuestEntriesTag extends Tags
{
    use Concerns\FormBuilder;

    protected static $handle = 'guest-entries';

    public function create()
    {
        $form = $this->resolveForm('create');

        return $this->createForm(
            route('statamic.guest-entries.store', $form->handle())
        );
    }

    public function update()
    {
        $form = $this->resolveForm('update');
        $entry = $this->resolveEntry($form);

        if (! $entry || ! $form->authorize($entry)) {
            return null;
        }

        return $this->createForm(
            route('statamic.guest-entries.update', [$form->handle(), $entry->id()]),
            $entry->data()->toArray(),
            'PATCH'
        );
    }

    public function delete()
    {
        $form = $this->resolveForm('delete');
        $entry = $this->resolveEntry($form);

        if (! $entry || ! $form->authorize($entry)) {
            return null;
        }

        return $this->createForm(
            route('statamic.guest-entries.destroy', [$form->handle(), $entry->id()]),
            $entry->data()->toArray(),
            'DELETE'
        );
    }

    private function resolveForm(string $action): Form
    {
        if ($this->params->hasAny(['collection', 'request'])) {
            throw new \Exception('Guest Entries: The `collection` and `request` parameters have been moved to the form in `config/guest-entries.php`.');
        }

        if (! $handle = $this->params->get('form')) {
            throw new \Exception("Guest Entries: The `form` parameter is required when using the `{$action}` tag.");
        }

        if (! $form = Form::find($handle)) {
            throw new \Exception("Guest Entries: The [{$handle}] form hasn't been configured.");
        }

        if ($form->action() !== $action) {
            throw new \Exception("Guest Entries: The [{$handle}] form can't be used with the `{$action}` tag.");
        }

        $form->collection();

        if ($action !== 'delete') {
            $form->fields();
        }

        return $form;
    }

    private function resolveEntry(Form $form): ?EntryContract
    {
        if (! $id = $this->params->get('id')) {
            throw new \Exception("Guest Entries: The `id` parameter is required when using the `{$form->action()}` tag.");
        }

        $entry = Entry::find($id);

        return $entry?->collectionHandle() === $form->collection()->handle() ? $entry : null;
    }

    public function turnstile(): string
    {
        $siteKey = config('guest-entries.turnstile.site_key')
            ?? throw new \Exception('Guest Entries: Please set the `turnstile.site_key` config option before using the `turnstile` tag.');

        $attributes = collect(['class' => 'cf-turnstile', 'data-sitekey' => $siteKey])
            ->merge($this->params->mapWithKeys(fn ($value, $param) => ["data-{$param}" => $value]))
            ->all();

        return '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>'
            .'<div '.$this->renderAttributes($attributes).'></div>';
    }

    public function errors()
    {
        if (! $this->hasErrors()) {
            return null;
        }

        $errors = [];

        foreach (session('errors')->getBag('default')->all() as $error) {
            $errors[]['value'] = e($error);
        }

        return $errors;
    }

    public function hasErrors()
    {
        return session()->has('errors');
    }

    public function success(): bool
    {
        return session()->get('guest-entries.success', false);
    }
}
