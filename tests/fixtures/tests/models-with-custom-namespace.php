<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\CategoryController
 */
class CategoryControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function index_behaves_as_expected()
    {
        $categories = factory(Category::class, 3)->create();

        $response = $this->get(route('category.index'));
    }


    /**
     * @test
     */
    public function store_uses_form_request_validation()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\CategoryController::class,
            'store',
            \App\Http\Requests\CategoryStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves()
    {
        $category = $this->faker->word;

        $response = $this->post(route('category.store'), [
            'category' => $category,
        ]);

        $categories = Category::query()
            ->where('category', $category)
            ->get();
        $this->assertCount(1, $categories);
        $category = $categories->first();
    }


    /**
     * @test
     */
    public function show_behaves_as_expected()
    {
        $category = factory(Category::class)->create();

        $response = $this->get(route('category.show', $category));
    }


    /**
     * @test
     */
    public function update_uses_form_request_validation()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\CategoryController::class,
            'update',
            \App\Http\Requests\CategoryUpdateRequest::class
        );
    }

    /**
     * @test
     */
    public function update_behaves_as_expected()
    {
        $category = factory(Category::class)->create();
        $category = $this->faker->word;

        $response = $this->put(route('category.update', $category), [
            'category' => $category,
        ]);
    }


    /**
     * @test
     */
    public function destroy_deletes_and_responds_with()
    {
        $category = factory(Category::class)->create();

        $response = $this->delete(route('category.destroy', $category));

        $response->assertOk();

        $this->assertDeleted($category);
    }
}
