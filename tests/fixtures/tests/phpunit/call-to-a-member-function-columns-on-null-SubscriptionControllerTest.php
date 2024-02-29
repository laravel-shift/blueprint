<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\SubscriptionController
 */
final class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function index_displays_view(): void
    {
        $subscriptions = Subscription::factory()->count(3)->create();

        $response = $this->get(route('subscriptions.index'));

        $response->assertOk();
        $response->assertViewIs('subscription.index');
        $response->assertViewHas('subscriptions');
    }


    #[Test]
    public function show_displays_view(): void
    {
        $subscription = Subscription::factory()->create();

        $response = $this->get(route('subscriptions.show', $subscription));

        $response->assertOk();
        $response->assertViewIs('subscription.show');
        $response->assertViewHas('subscription');
    }
}
