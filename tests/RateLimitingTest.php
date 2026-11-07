<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    Cache::flush();
});

it('rate limits the store endpoint', function () {
    collect(range(1, 10))->each(fn () => expect($this->post(route('statamic.guest-entries.store', 'create_comments'))->getStatusCode())->not->toBe(429));
    $this->post(route('statamic.guest-entries.store', 'create_comments'))->assertStatus(429);
});

it('rate limits the update endpoint', function () {
    collect(range(1, 10))->each(fn () => expect($this->patch(route('statamic.guest-entries.update', ['update_comments', 'some-id']))->getStatusCode())->not->toBe(429));
    $this->patch(route('statamic.guest-entries.update', ['update_comments', 'some-id']))->assertStatus(429);
});

it('rate limits the delete endpoint', function () {
    collect(range(1, 10))->each(fn () => expect($this->delete(route('statamic.guest-entries.destroy', ['delete_comments', 'some-id']))->getStatusCode())->not->toBe(429));
    $this->delete(route('statamic.guest-entries.destroy', ['delete_comments', 'some-id']))->assertStatus(429);
});

it('allows the rate limiter to be overridden', function () {
    RateLimiter::for('guest-entries', fn ($request) => Limit::perMinute(2)->by($request->ip()));

    $this->post(route('statamic.guest-entries.store', 'create_comments'));
    $this->post(route('statamic.guest-entries.store', 'create_comments'));
    $this->post(route('statamic.guest-entries.store', 'create_comments'))->assertStatus(429);
});
