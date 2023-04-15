---
title: Events
---

If you need to trigger any custom code when a user does anything with Guest Entries, this addon provides some addons to let you do that.

### GuestEntryCreated

[`DuncanMcClean\GuestEntries\Events\GuestEntryCreated`](https://github.com/duncanmcclean/guest-entries/blob/main/src/Events/GuestEntryCreated.php)

This event is fired whenever an entry is created via `{{ guest-entries:create }}`.

```php
public function handle(GuestEntryCreated $event)
{
	$event->entry;
}
```

### GuestEntryUpdated

[`DuncanMcClean\GuestEntries\Events\GuestEntryUpdated`](https://github.com/duncanmcclean/guest-entries/blob/main/src/Events/GuestEntryUpdated.php)

This event is fired whenever an entry is updated via `{{ guest-entries:update }}`.

```php
public function handle(GuestEntryUpdated $event)
{
	$event->entry;
}
```

### GuestEntryDeleted

[`DuncanMcClean\GuestEntries\Events\GuestEntryDeleted`](https://github.com/duncanmcclean/guest-entries/blob/main/src/Events/GuestEntryDeleted.php)

This event is fired whenever an entry is updated via `{{ guest-entries:delete }}`.

```php
public function handle(GuestEntryDeleted $event)
{
	$event->entry;
}
```
