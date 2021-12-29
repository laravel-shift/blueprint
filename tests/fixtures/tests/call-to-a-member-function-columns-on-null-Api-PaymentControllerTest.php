<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Events\NewPayment;
use App\Mail\PaymentCreated;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Api\PaymentController
 */
class PaymentControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function store_uses_form_request_validation()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Api\PaymentController::class,
            'store',
            \App\Http\Requests\Api\PaymentStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves_and_responds_with()
    {
        $status = $this->faker->word;
        $amount = $this->faker->randomFloat(/** decimal_attributes **/);
        $user = User::factory()->create();

        $response = $this->post(route('payment.store'), [
            'status' => $status,
            'amount' => $amount,
            'user_id' => $user->id,
        ]);

        $payments = Payment::query()
            ->where('status', $status)
            ->where('amount', $amount)
            ->where('user_id', $user->id)
            ->get();
        $this->assertCount(1, $payments);
        $payment = $payments->first();

        $response->assertNoContent();
    }
}
