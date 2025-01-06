<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\CertificateType;
use function Pest\Faker\fake;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

test('index behaves as expected', function (): void {
    $certificateTypes = CertificateType::factory()->count(3)->create();

    $response = get(route('certificate-types.index'));

    $response->assertOk();
    $response->assertJsonStructure([]);
});


test('store uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\CertificateTypeController::class,
        'store',
        \App\Http\Requests\CertificateTypeStoreRequest::class
    );

test('store saves', function (): void {
    $name = fake()->name();

    $response = post(route('certificate-types.store'), [
        'name' => $name,
    ]);

    $certificateTypes = CertificateType::query()
        ->where('name', $name)
        ->get();
    expect($certificateTypes)->toHaveCount(1);
    $certificateType = $certificateTypes->first();

    $response->assertCreated();
    $response->assertJsonStructure([]);
});


test('show behaves as expected', function (): void {
    $certificateType = CertificateType::factory()->create();

    $response = get(route('certificate-types.show', $certificateType));

    $response->assertOk();
    $response->assertJsonStructure([]);
});


test('update uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\CertificateTypeController::class,
        'update',
        \App\Http\Requests\CertificateTypeUpdateRequest::class
    );

test('update behaves as expected', function (): void {
    $certificateType = CertificateType::factory()->create();
    $name = fake()->name();

    $response = put(route('certificate-types.update', $certificateType), [
        'name' => $name,
    ]);

    $certificateType->refresh();

    $response->assertOk();
    $response->assertJsonStructure([]);

    expect($name)->toEqual($certificateType->name);
});


test('destroy deletes and responds with', function (): void {
    $certificateType = CertificateType::factory()->create();

    $response = delete(route('certificate-types.destroy', $certificateType));

    $response->assertNoContent();

    assertModelMissing($certificateType);
});
