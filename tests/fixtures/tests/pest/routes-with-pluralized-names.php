<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use function Pest\Faker\fake;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

test('index behaves as expected', function (): void {
    $categories = Category::factory()->count(3)->create();

    $response = get(route('categories.index'));

    $response->assertOk();
    $response->assertJsonStructure([]);
});


test('store uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\CategoryController::class,
        'store',
        \App\Http\Requests\CategoryStoreRequest::class
    );

test('store saves', function (): void {
    $name = fake()->name();
    $image = fake()->word();
    $active = fake()->boolean();

    $response = post(route('categories.store'), [
        'name' => $name,
        'image' => $image,
        'active' => $active,
    ]);

    $categories = Category::query()
        ->where('name', $name)
        ->where('image', $image)
        ->where('active', $active)
        ->get();
    expect($categories)->toHaveCount(1);
    $category = $categories->first();

    $response->assertCreated();
    $response->assertJsonStructure([]);
});


test('show behaves as expected', function (): void {
    $category = Category::factory()->create();

    $response = get(route('categories.show', $category));

    $response->assertOk();
    $response->assertJsonStructure([]);
});


test('update uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\CategoryController::class,
        'update',
        \App\Http\Requests\CategoryUpdateRequest::class
    );

test('update behaves as expected', function (): void {
    $category = Category::factory()->create();
    $name = fake()->name();
    $image = fake()->word();
    $active = fake()->boolean();

    $response = put(route('categories.update', $category), [
        'name' => $name,
        'image' => $image,
        'active' => $active,
    ]);

    $category->refresh();

    $response->assertOk();
    $response->assertJsonStructure([]);

    expect($name)->toEqual($category->name);
    expect($image)->toEqual($category->image);
    expect($active)->toEqual($category->active);
});


test('destroy deletes and responds with', function (): void {
    $category = Category::factory()->create();

    $response = delete(route('categories.destroy', $category));

    $response->assertNoContent();

    assertSoftDeleted($category);
});
