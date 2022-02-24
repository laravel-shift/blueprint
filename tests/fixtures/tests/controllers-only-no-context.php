<?php

namespace Tests\Feature\Http\Controllers;

use App\Events\ExportReport;
use App\Jobs\GenerateReport;
use App\Mail\SendReport;
use App\Notification\ReportGenerated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ReportController
 */
class ReportControllerTest extends TestCase
{
    /**
     * @test
     */
    public function __invoke_displays_view()
    {
        Queue::fake();
        Event::fake();
        Notification::fake();
        Mail::fake();

        $response = $this->get(route('report.__invoke'));

        $response->assertOk();
        $response->assertViewIs('report');

        Queue::assertPushed(GenerateReport::class, function ($job) use ($event) {
            return $job->event->is($event);
        });
        Event::assertDispatched(ExportReport::class, function ($event) use ($event) {
            return $event->event->is($event);
        });
        Notification::assertSentTo($auth->user, ReportGenerated::class, function ($notification) use ($event) {
            return $notification->event->is($event);
        });
        Mail::assertSent(SendReport::class, function ($mail) use ($event) {
            return $mail->event->is($event);
        });
    }
}
