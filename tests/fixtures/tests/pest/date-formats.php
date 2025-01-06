<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Date;
use Illuminate\Support\Carbon;
use function Pest\Faker\fake;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

test('index displays view', function (): void {
    $dates = Date::factory()->count(3)->create();

    $response = get(route('dates.index'));

    $response->assertOk();
    $response->assertViewIs('date.index');
    $response->assertViewHas('dates');
});


test('create displays view', function (): void {
    $response = get(route('dates.create'));

    $response->assertOk();
    $response->assertViewIs('date.create');
});


test('store uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\DateController::class,
        'store',
        \App\Http\Requests\DateStoreRequest::class
    );

test('store saves and redirects', function (): void {
    $born_at = Carbon::parse(fake()->date());
    $expires_at = Carbon::parse(fake()->dateTime());
    $published_at = Carbon::parse(fake()->dateTime());

    $response = post(route('dates.store'), [
        'born_at' => $born_at->toDateString(),
        'expires_at' => $expires_at->toDateTimeString(),
        'published_at' => $published_at->toDateTimeString(),
    ]);

    $dates = Date::query()
        ->where('born_at', $born_at)
        ->where('expires_at', $expires_at)
        ->where('published_at', $published_at)
        ->get();
    expect($dates)->toHaveCount(1);
    $date = $dates->first();

    $response->assertRedirect(route('dates.index'));
    $response->assertSessionHas('date.id', $date->id);
});


test('show displays view', function (): void {
    $date = Date::factory()->create();

    $response = get(route('dates.show', $date));

    $response->assertOk();
    $response->assertViewIs('date.show');
    $response->assertViewHas('date');
});


test('edit displays view', function (): void {
    $date = Date::factory()->create();

    $response = get(route('dates.edit', $date));

    $response->assertOk();
    $response->assertViewIs('date.edit');
    $response->assertViewHas('date');
});


test('update uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\DateController::class,
        'update',
        \App\Http\Requests\DateUpdateRequest::class
    );

test('update redirects', function (): void {
    $date = Date::factory()->create();
    $born_at = Carbon::parse(fake()->date());
    $expires_at = Carbon::parse(fake()->dateTime());
    $published_at = Carbon::parse(fake()->dateTime());

    $response = put(route('dates.update', $date), [
        'born_at' => $born_at->toDateString(),
        'expires_at' => $expires_at->toDateTimeString(),
        'published_at' => $published_at->toDateTimeString(),
    ]);

    $date->refresh();

    $response->assertRedirect(route('dates.index'));
    $response->assertSessionHas('date.id', $date->id);

    expect($born_at)->toEqual($date->born_at);
    expect($expires_at)->toEqual($date->expires_at);
    expect($published_at->timestamp)->toEqual($date->published_at);
});


test('destroy deletes and redirects', function (): void {
    $date = Date::factory()->create();

    $response = delete(route('dates.destroy', $date));

    $response->assertRedirect(route('dates.index'));

    assertModelMissing($date);
});
