<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Date;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\DateController
 */
final class DateControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_displays_view(): void
    {
        $dates = Date::factory()->count(3)->create();

        $response = $this->get(route('dates.index'));

        $response->assertOk();
        $response->assertViewIs('date.index');
        $response->assertViewHas('dates');
    }


    #[Test]
    public function create_displays_view(): void
    {
        $response = $this->get(route('dates.create'));

        $response->assertOk();
        $response->assertViewIs('date.create');
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\DateController::class,
            'store',
            \App\Http\Requests\DateStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_redirects(): void
    {
        $born_at = Carbon::parse(fake()->date());
        $expires_at = Carbon::parse(fake()->dateTime());
        $published_at = Carbon::parse(fake()->dateTime());

        $response = $this->post(route('dates.store'), [
            'born_at' => $born_at->toDateString(),
            'expires_at' => $expires_at->toDateTimeString(),
            'published_at' => $published_at->toDateTimeString(),
        ]);

        $dates = Date::query()
            ->where('born_at', $born_at)
            ->where('expires_at', $expires_at)
            ->where('published_at', $published_at)
            ->get();
        $this->assertCount(1, $dates);
        $date = $dates->first();

        $response->assertRedirect(route('dates.index'));
        $response->assertSessionHas('date.id', $date->id);
    }


    #[Test]
    public function show_displays_view(): void
    {
        $date = Date::factory()->create();

        $response = $this->get(route('dates.show', $date));

        $response->assertOk();
        $response->assertViewIs('date.show');
        $response->assertViewHas('date');
    }


    #[Test]
    public function edit_displays_view(): void
    {
        $date = Date::factory()->create();

        $response = $this->get(route('dates.edit', $date));

        $response->assertOk();
        $response->assertViewIs('date.edit');
        $response->assertViewHas('date');
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\DateController::class,
            'update',
            \App\Http\Requests\DateUpdateRequest::class
        );
    }

    #[Test]
    public function update_redirects(): void
    {
        $date = Date::factory()->create();
        $born_at = Carbon::parse(fake()->date());
        $expires_at = Carbon::parse(fake()->dateTime());
        $published_at = Carbon::parse(fake()->dateTime());

        $response = $this->put(route('dates.update', $date), [
            'born_at' => $born_at->toDateString(),
            'expires_at' => $expires_at->toDateTimeString(),
            'published_at' => $published_at->toDateTimeString(),
        ]);

        $date->refresh();

        $response->assertRedirect(route('dates.index'));
        $response->assertSessionHas('date.id', $date->id);

        $this->assertEquals($born_at, $date->born_at);
        $this->assertEquals($expires_at, $date->expires_at);
        $this->assertEquals($published_at->timestamp, $date->published_at);
    }


    #[Test]
    public function destroy_deletes_and_redirects(): void
    {
        $date = Date::factory()->create();

        $response = $this->delete(route('dates.destroy', $date));

        $response->assertRedirect(route('dates.index'));

        $this->assertModelMissing($date);
    }
}
