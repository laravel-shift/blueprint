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
use function Pest\Laravel\get;

test('invoke displays view', function (): void {
    Queue::fake();
    Event::fake();
    Notification::fake();
    Mail::fake();

    $response = get(route('reports.__invoke'));

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
});
