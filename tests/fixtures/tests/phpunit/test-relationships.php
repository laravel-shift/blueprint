<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Conference;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ConferenceController
 */
final class ConferenceControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_displays_view(): void
    {
        $conferences = Conference::factory()->count(3)->create();

        $response = $this->get(route('conferences.index'));

        $response->assertOk();
        $response->assertViewIs('conference.index');
        $response->assertViewHas('conferences');
    }


    #[Test]
    public function create_displays_view(): void
    {
        $response = $this->get(route('conferences.create'));

        $response->assertOk();
        $response->assertViewIs('conference.create');
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ConferenceController::class,
            'store',
            \App\Http\Requests\ConferenceControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_redirects(): void
    {
        $name = fake()->name();
        $starts_at = Carbon::parse(fake()->dateTime());
        $ends_at = Carbon::parse(fake()->dateTime());
        $venue = Venue::factory()->create();

        $response = $this->post(route('conferences.store'), [
            'name' => $name,
            'starts_at' => $starts_at->toDateTimeString(),
            'ends_at' => $ends_at->toDateTimeString(),
            'venue_id' => $venue->id,
        ]);

        $conferences = Conference::query()
            ->where('name', $name)
            ->where('starts_at', $starts_at)
            ->where('ends_at', $ends_at)
            ->where('venue_id', $venue->id)
            ->get();
        $this->assertCount(1, $conferences);
        $conference = $conferences->first();

        $response->assertRedirect(route('conferences.index'));
        $response->assertSessionHas('conference.id', $conference->id);
    }


    #[Test]
    public function show_displays_view(): void
    {
        $conference = Conference::factory()->create();

        $response = $this->get(route('conferences.show', $conference));

        $response->assertOk();
        $response->assertViewIs('conference.show');
        $response->assertViewHas('conference');
    }


    #[Test]
    public function edit_displays_view(): void
    {
        $conference = Conference::factory()->create();

        $response = $this->get(route('conferences.edit', $conference));

        $response->assertOk();
        $response->assertViewIs('conference.edit');
        $response->assertViewHas('conference');
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ConferenceController::class,
            'update',
            \App\Http\Requests\ConferenceControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_redirects(): void
    {
        $conference = Conference::factory()->create();
        $name = fake()->name();
        $starts_at = Carbon::parse(fake()->dateTime());
        $ends_at = Carbon::parse(fake()->dateTime());
        $venue = Venue::factory()->create();

        $response = $this->put(route('conferences.update', $conference), [
            'name' => $name,
            'starts_at' => $starts_at->toDateTimeString(),
            'ends_at' => $ends_at->toDateTimeString(),
            'venue_id' => $venue->id,
        ]);

        $conference->refresh();

        $response->assertRedirect(route('conferences.index'));
        $response->assertSessionHas('conference.id', $conference->id);

        $this->assertEquals($name, $conference->name);
        $this->assertEquals($starts_at, $conference->starts_at);
        $this->assertEquals($ends_at, $conference->ends_at);
        $this->assertEquals($venue->id, $conference->venue_id);
    }


    #[Test]
    public function destroy_deletes_and_redirects(): void
    {
        $conference = Conference::factory()->create();

        $response = $this->delete(route('conferences.destroy', $conference));

        $response->assertRedirect(route('conferences.index'));

        $this->assertModelMissing($conference);
    }
}
