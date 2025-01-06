<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateType;
use Illuminate\Support\Carbon;
use function Pest\Faker\fake;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

test('index behaves as expected', function (): void {
    $certificates = Certificate::factory()->count(3)->create();

    $response = get(route('certificates.index'));

    $response->assertOk();
    $response->assertJsonStructure([]);
});


test('store uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\CertificateController::class,
        'store',
        \App\Http\Requests\CertificateStoreRequest::class
    );

test('store saves', function (): void {
    $name = fake()->name();
    $certificate_type = CertificateType::factory()->create();
    $reference = fake()->word();
    $document = fake()->word();
    $expiry_date = Carbon::parse(fake()->date());

    $response = post(route('certificates.store'), [
        'name' => $name,
        'certificate_type_id' => $certificate_type->id,
        'reference' => $reference,
        'document' => $document,
        'expiry_date' => $expiry_date->toDateString(),
    ]);

    $certificates = Certificate::query()
        ->where('name', $name)
        ->where('certificate_type_id', $certificate_type->id)
        ->where('reference', $reference)
        ->where('document', $document)
        ->where('expiry_date', $expiry_date)
        ->get();
    expect($certificates)->toHaveCount(1);
    $certificate = $certificates->first();

    $response->assertCreated();
    $response->assertJsonStructure([]);
});


test('show behaves as expected', function (): void {
    $certificate = Certificate::factory()->create();

    $response = get(route('certificates.show', $certificate));

    $response->assertOk();
    $response->assertJsonStructure([]);
});


test('update uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\CertificateController::class,
        'update',
        \App\Http\Requests\CertificateUpdateRequest::class
    );

test('update behaves as expected', function (): void {
    $certificate = Certificate::factory()->create();
    $name = fake()->name();
    $certificate_type = CertificateType::factory()->create();
    $reference = fake()->word();
    $document = fake()->word();
    $expiry_date = Carbon::parse(fake()->date());

    $response = put(route('certificates.update', $certificate), [
        'name' => $name,
        'certificate_type_id' => $certificate_type->id,
        'reference' => $reference,
        'document' => $document,
        'expiry_date' => $expiry_date->toDateString(),
    ]);

    $certificate->refresh();

    $response->assertOk();
    $response->assertJsonStructure([]);

    expect($name)->toEqual($certificate->name);
    expect($certificate_type->id)->toEqual($certificate->certificate_type_id);
    expect($reference)->toEqual($certificate->reference);
    expect($document)->toEqual($certificate->document);
    expect($expiry_date)->toEqual($certificate->expiry_date);
});


test('destroy deletes and responds with', function (): void {
    $certificate = Certificate::factory()->create();

    $response = delete(route('certificates.destroy', $certificate));

    $response->assertNoContent();

    assertModelMissing($certificate);
});
