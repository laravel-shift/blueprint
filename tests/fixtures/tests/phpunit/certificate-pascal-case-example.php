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

        $response = $this->get(route('certificate.index'));

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
        $name = $this->faker->name();
        $certificate_type = CertificateType::factory()->create();
        $reference = $this->faker->word();
        $document = $this->faker->word();
        $expiry_date = Carbon::parse($this->faker->date());

        $response = $this->post(route('certificate.store'), [
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

        $response = $this->get(route('certificate.show', $certificate));

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
        $name = $this->faker->name();
        $certificate_type = CertificateType::factory()->create();
        $reference = $this->faker->word();
        $document = $this->faker->word();
        $expiry_date = Carbon::parse($this->faker->date());

        $response = $this->put(route('certificate.update', $certificate), [
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

        $response = $this->delete(route('certificate.destroy', $certificate));

        $response->assertNoContent();

        $this->assertModelMissing($certificate);
    }
}
