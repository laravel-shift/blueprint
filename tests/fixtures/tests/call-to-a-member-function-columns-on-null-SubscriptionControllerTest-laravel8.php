<?php

namespace Tests\Feature\Http\Controllers;

use App\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\SubscriptionController
 */
class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function index_displays_view()
    {
        $subscriptions = Subscription::factory()->count(3)->create();

        $response = $this->get(route('subscription.index'));

        $response->assertOk();
        $response->assertViewIs('subscription.index');
        $response->assertViewHas('subscriptions');
    }


    /**
     * @test
     */
    public function show_displays_view()
    {
        $subscription = Subscription::factory()->create();

        $response = $this->get(route('subscription.show', $subscription));

        $response->assertOk();
        $response->assertViewIs('subscription.show');
        $response->assertViewHas('subscription');
    }
}
