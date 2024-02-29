<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Events\NewPayment;
use App\Mail\PaymentCreated;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use function Pest\Faker\fake;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('store uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\Api\PaymentController::class,
        'store',
        \App\Http\Requests\Api\PaymentStoreRequest::class
    );

test('store saves and responds with', function (): void {
    $status = fake()->word();
    $amount = fake()->randomFloat(/** decimal_attributes **/);
    $user = User::factory()->create();

    $response = post(route('payments.store'), [
        'status' => $status,
        'amount' => $amount,
        'user_id' => $user->id,
    ]);

    $payments = Payment::query()
        ->where('status', $status)
        ->where('amount', $amount)
        ->where('user_id', $user->id)
        ->get();
    expect($payments)->toHaveCount(1);
    $payment = $payments->first();

    $response->assertNoContent();
});
