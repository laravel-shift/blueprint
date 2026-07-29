<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Api\OrderController
 */
final class OrderControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $orders = Order::factory()->count(3)->create();

        $response = $this->get(route('orders.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Api\OrderController::class,
            'store',
            \App\Http\Requests\Api\OrderStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $reference = fake()->word();
        $product = Product::factory()->create();
        $quantity = fake()->randomNumber();

        $response = $this->post(route('orders.store'), [
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
        $this->assertCount(1, $orders);
        $order = $orders->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);

        $this->assertDatabaseHas('items', ['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => $quantity]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $order = Order::factory()->create();

        $response = $this->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Api\OrderController::class,
            'update',
            \App\Http\Requests\Api\OrderUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $order = Order::factory()->create();
        $reference = fake()->word();

        $response = $this->put(route('orders.update', $order), [
            'reference' => $reference,
        ]);

        $order->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($reference, $order->reference);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $order = Order::factory()->create();

        $response = $this->delete(route('orders.destroy', $order));

        $response->assertNoContent();

        $this->assertModelMissing($order);
    }
}
