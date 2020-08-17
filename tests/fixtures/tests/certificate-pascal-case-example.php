<?php

namespace Tests\Feature\Http\Controllers;

use App\Certificate;
use App\CertificateType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\CertificateController
 */
class CertificateControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function index_behaves_as_expected()
    {
        $certificates = factory(Certificate::class, 3)->create();

        $response = $this->get(route('certificate.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    /**
     * @test
     */
    public function store_uses_form_request_validation()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\CertificateController::class,
            'store',
            \App\Http\Requests\CertificateStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves()
    {
        $name = $this->faker->name;
        $certificate_type = factory(CertificateType::class)->create();
        $reference = $this->faker->word;
        $document = $this->faker->word;
        $expiry_date = $this->faker->date();

        $response = $this->post(route('certificate.store'), [
            'name' => $name,
            'certificate_type_id' => $certificate_type->id,
            'reference' => $reference,
            'document' => $document,
            'expiry_date' => $expiry_date,
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


    /**
     * @test
     */
    public function show_behaves_as_expected()
    {
        $certificate = factory(Certificate::class)->create();

        $response = $this->get(route('certificate.show', $certificate));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    /**
     * @test
     */
    public function update_uses_form_request_validation()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\CertificateController::class,
            'update',
            \App\Http\Requests\CertificateUpdateRequest::class
        );
    }

    /**
     * @test
     */
    public function update_behaves_as_expected()
    {
        $certificate = factory(Certificate::class)->create();
        $name = $this->faker->name;
        $certificate_type = factory(CertificateType::class)->create();
        $reference = $this->faker->word;
        $document = $this->faker->word;
        $expiry_date = $this->faker->date();

        $response = $this->put(route('certificate.update', $certificate), [
            'name' => $name,
            'certificate_type_id' => $certificate_type->id,
            'reference' => $reference,
            'document' => $document,
            'expiry_date' => $expiry_date,
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


    /**
     * @test
     */
    public function destroy_deletes_and_responds_with()
    {
        $certificate = factory(Certificate::class)->create();

        $response = $this->delete(route('certificate.destroy', $certificate));

        $response->assertNoContent();

        $this->assertDeleted($certificate);
    }
}
