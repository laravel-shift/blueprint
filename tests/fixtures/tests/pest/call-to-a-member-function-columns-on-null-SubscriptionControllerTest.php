<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Subscription;
use function Pest\Laravel\get;

test('index displays view', function (): void {
    $subscriptions = Subscription::factory()->count(3)->create();

    $response = get(route('subscription.index'));

    $response->assertOk();
    $response->assertViewIs('subscription.index');
    $response->assertViewHas('subscriptions');
});


test('show displays view', function (): void {
    $subscription = Subscription::factory()->create();

    $response = get(route('subscription.show', $subscription));

    $response->assertOk();
    $response->assertViewIs('subscription.show');
    $response->assertViewHas('subscription');
});
