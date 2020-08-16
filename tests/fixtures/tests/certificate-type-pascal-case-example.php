<?php

namespace Tests\Feature\Http\Controllers;

use App\CertificateType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\CertificateTypeController
 */
class CertificateTypeControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function index_behaves_as_expected()
    {
        $certificateTypes = factory(CertificateType::class, 3)->create();

        $response = $this->get(route('certificate-type.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    /**
     * @test
     */
    public function store_uses_form_request_validation()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\CertificateTypeController::class,
            'store',
            \App\Http\Requests\CertificateTypeStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves()
    {
        $name = $this->faker->name;

        $response = $this->post(route('certificate-type.store'), [
            'name' => $name,
        ]);

        $certificateTypes = CertificateType::query()
            ->where('name', $name)
            ->get();
        $this->assertCount(1, $certificateTypes);
        $certificateType = $certificateTypes->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    /**
     * @test
     */
    public function show_behaves_as_expected()
    {
        $certificateType = factory(CertificateType::class)->create();

        $response = $this->get(route('certificate-type.show', $certificateType));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    /**
     * @test
     */
    public function update_uses_form_request_validation()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\CertificateTypeController::class,
            'update',
            \App\Http\Requests\CertificateTypeUpdateRequest::class
        );
    }

    /**
     * @test
     */
    public function update_behaves_as_expected()
    {
        $certificateType = factory(CertificateType::class)->create();
        $name = $this->faker->name;

        $response = $this->put(route('certificate-type.update', $certificateType), [
            'name' => $name,
        ]);

        $certificateType->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($name, $certificateType->name);
    }


    /**
     * @test
     */
    public function destroy_deletes_and_responds_with()
    {
        $certificateType = factory(CertificateType::class)->create();

        $response = $this->delete(route('certificate-type.destroy', $certificateType));

        $response->assertNoContent();

        $this->assertDeleted($certificateType);
    }
}
