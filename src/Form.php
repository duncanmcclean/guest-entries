<?php

namespace DuncanMcClean\GuestEntries;

use Illuminate\Support\Facades\Gate;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Exceptions\BlueprintNotFoundException;
use Statamic\Exceptions\CollectionNotFoundException;
use Statamic\Facades\Collection;
use Statamic\Facades\User;
use Statamic\Fields\Blueprint;

class Form
{
    public function __construct(private string $handle, private array $config)
    {
        //
    }

    public static function find(string $handle): ?self
    {
        $config = config('guest-entries.forms', [])[$handle] ?? null;

        return is_array($config) ? new self($handle, $config) : null;
    }

    public function handle(): string
    {
        return $this->handle;
    }

    public function action(): string
    {
        return $this->config['action'] ?? throw new \Exception("Guest Entries: The [{$this->handle}] form is missing an `action`.");
    }

    public function collection(): CollectionContract
    {
        $handle = $this->config['collection'] ?? throw new \Exception("Guest Entries: The [{$this->handle}] form is missing a `collection`.");

        return Collection::find($handle) ?? throw new CollectionNotFoundException($handle);
    }

    public function blueprint(): Blueprint
    {
        $handle = $this->config['blueprint'] ?? null;

        return $this->collection()->entryBlueprint($handle) ?? throw new BlueprintNotFoundException($handle);
    }

    public function published(): ?bool
    {
        return $this->config['published'] ?? null;
    }

    public function formRequest(): ?string
    {
        return $this->config['request'] ?? null;
    }

    public function fields(): array
    {
        return $this->config['fields'] ?? throw new \Exception("Guest Entries: The [{$this->handle}] form is missing `fields`.");
    }

    public function fillableFields(Blueprint $blueprint): array
    {
        return collect($this->fields())
            ->reject(fn (string $handle) => $blueprint->field($handle)?->get('antlers'))
            ->values()
            ->all();
    }

    public function authorize(?EntryContract $entry = null): bool
    {
        $user = User::current();

        if (($this->config['auth'] ?? false) && ! $user) {
            return false;
        }

        if (! $entry) {
            return true;
        }

        return match ($ability = $this->config['authorize'] ?? null) {
            null => false,
            'author' => $user && $entry->authors()->contains($user->id()),
            default => Gate::allows($ability, $entry),
        };
    }
}
