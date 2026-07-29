<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Product;
use function Pest\Faker\fake;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

test('index behaves as expected', function (): void {
    $orders = Order::factory()->count(3)->create();

    $response = get(route('orders.index'));

    $response->assertOk();
    $response->assertJsonStructure([]);
});


test('store uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\Api\OrderController::class,
        'store',
        \App\Http\Requests\Api\OrderStoreRequest::class
    );

test('store saves', function (): void {
    $reference = fake()->word();
    $product = Product::factory()->create();
    $quantity = fake()->randomNumber();

    $response = post(route('orders.store'), [
        'reference' => $reference,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => $quantity,
            ],
        ],
    ]);

    $orders = Order::query()
        ->where('reference', $reference)
        ->get();
    expect($orders)->toHaveCount(1);
    $order = $orders->first();

    $response->assertCreated();
    $response->assertJsonStructure([]);

    assertDatabaseHas('items', ['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => $quantity]);
});


test('show behaves as expected', function (): void {
    $order = Order::factory()->create();

    $response = get(route('orders.show', $order));

    $response->assertOk();
    $response->assertJsonStructure([]);
});


test('update uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\Api\OrderController::class,
        'update',
        \App\Http\Requests\Api\OrderUpdateRequest::class
    );

test('update behaves as expected', function (): void {
    $order = Order::factory()->create();
    $reference = fake()->word();

    $response = put(route('orders.update', $order), [
        'reference' => $reference,
    ]);

    $order->refresh();

    $response->assertOk();
    $response->assertJsonStructure([]);

    expect($reference)->toEqual($order->reference);
});


test('destroy deletes and responds with', function (): void {
    $order = Order::factory()->create();

    $response = delete(route('orders.destroy', $order));

    $response->assertNoContent();

    assertModelMissing($order);
});
