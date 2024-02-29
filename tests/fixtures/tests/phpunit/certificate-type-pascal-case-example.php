<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\CertificateType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\CertificateTypeController
 */
final class CertificateTypeControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $certificateTypes = CertificateType::factory()->count(3)->create();

        $response = $this->get(route('certificate-types.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\CertificateTypeController::class,
            'store',
            \App\Http\Requests\CertificateTypeStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $name = $this->faker->name();

        $response = $this->post(route('certificate-types.store'), [
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


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $certificateType = CertificateType::factory()->create();

        $response = $this->get(route('certificate-types.show', $certificateType));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\CertificateTypeController::class,
            'update',
            \App\Http\Requests\CertificateTypeUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $certificateType = CertificateType::factory()->create();
        $name = $this->faker->name();

        $response = $this->put(route('certificate-types.update', $certificateType), [
            'name' => $name,
        ]);

        $certificateType->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($name, $certificateType->name);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $certificateType = CertificateType::factory()->create();

        $response = $this->delete(route('certificate-types.destroy', $certificateType));

        $response->assertNoContent();

        $this->assertModelMissing($certificateType);
    }
}
