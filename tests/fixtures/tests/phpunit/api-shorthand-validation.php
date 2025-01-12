<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\CertificateController
 */
final class CertificateControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $certificates = Certificate::factory()->count(3)->create();

        $response = $this->get(route('certificates.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\CertificateController::class,
            'store',
            \App\Http\Requests\CertificateStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $name = fake()->name();
        $certificate_type = CertificateType::factory()->create();
        $reference = fake()->word();
        $document = fake()->word();
        $expiry_date = Carbon::parse(fake()->date());

        $response = $this->post(route('certificates.store'), [
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
        $this->assertCount(1, $certificates);
        $certificate = $certificates->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $certificate = Certificate::factory()->create();

        $response = $this->get(route('certificates.show', $certificate));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\CertificateController::class,
            'update',
            \App\Http\Requests\CertificateUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $certificate = Certificate::factory()->create();
        $name = fake()->name();
        $certificate_type = CertificateType::factory()->create();
        $reference = fake()->word();
        $document = fake()->word();
        $expiry_date = Carbon::parse(fake()->date());

        $response = $this->put(route('certificates.update', $certificate), [
            'name' => $name,
            'certificate_type_id' => $certificate_type->id,
            'reference' => $reference,
            'document' => $document,
            'expiry_date' => $expiry_date->toDateString(),
        ]);

        $certificate->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($name, $certificate->name);
        $this->assertEquals($certificate_type->id, $certificate->certificate_type_id);
        $this->assertEquals($reference, $certificate->reference);
        $this->assertEquals($document, $certificate->document);
        $this->assertEquals($expiry_date, $certificate->expiry_date);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $certificate = Certificate::factory()->create();

        $response = $this->delete(route('certificates.destroy', $certificate));

        $response->assertNoContent();

        $this->assertModelMissing($certificate);
    }
}
