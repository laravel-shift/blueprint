<?php

namespace Tests\Feature\Http\Controllers;

use App\Events\NewPayment;
use App\Mail\PaymentCreated;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use function Pest\Faker\fake;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('create displays view', function (): void {
    $response = get(route('payments.create'));

    $response->assertOk();
    $response->assertViewIs('payment.create');
});


test('store uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\PaymentController::class,
        'store',
        \App\Http\Requests\PaymentStoreRequest::class
    );

test('store saves and redirects', function (): void {
    $status = fake()->word();
    $amount = fake()->randomFloat(/** decimal_attributes **/);
    $user = User::factory()->create();

    Event::fake();
    Mail::fake();

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

    $response->assertRedirect(route('payments.create'));
    $response->assertSessionHas('message', $message);

    Event::assertDispatched(NewPayment::class, function ($event) use ($payment) {
        return $event->payment->is($payment);
    });
    Mail::assertSent(PaymentCreated::class, function ($mail) use ($payment) {
        return $mail->hasTo($payment->user) && $mail->payment->is($payment);
    });
});
