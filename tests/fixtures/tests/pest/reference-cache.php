<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use function Pest\Faker\fake;
use function Pest\Laravel\post;

test('store uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\UserController::class,
        'store',
        \App\Http\Requests\UserControllerStoreRequest::class
    );

test('store saves and redirects', function (): void {
    $email = fake()->safeEmail();
    $password = fake()->password();

    $response = post(route('users.store'), [
        'email' => $email,
        'password' => $password,
    ]);

    $users = User::query()
        ->where('email', $email)
        ->where('password', $password)
        ->get();
    expect($users)->toHaveCount(1);
    $user = $users->first();

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('user.email', $user->email);
});
