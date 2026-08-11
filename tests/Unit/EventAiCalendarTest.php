<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/event-import-v2.php';
require_once dirname(__DIR__, 2) . '/inc/event-ai-calendar.php';

final class EventAiCalendarTest extends TestCase
{
    public function testCalendarDocumentsKeepOnlyCanonicalHttpsUrls(): void
    {
        $pdf = 'https://www.comune.lauco.ud.it/media/files/030047/attachment/Locandina_Eventi_Lauco_2026_30x60_colore.pdf';
        $documents = \event_ai_calendar_documents([
            'calendar_documents' => [$pdf, $pdf . '?utm_source=test', 'http://example.com/test.pdf'],
        ]);

        self::assertSame([$pdf], $documents);
    }

    public function testCalendarFileInputUsesOnlyFileUrl(): void
    {
        $pdf = 'https://www.comune.lauco.ud.it/media/files/030047/attachment/Locandina_Eventi_Lauco_2026_30x60_colore.pdf';
        $input = \event_ai_calendar_file_input($pdf);

        self::assertSame([
            'type' => 'input_file',
            'file_url' => $pdf,
        ], $input);
        self::assertArrayNotHasKey('filename', $input);
        self::assertArrayNotHasKey('file_id', $input);
    }

    public function testOfficialCalendarPdfCanBackAnEventWithoutDedicatedWebPage(): void
    {
        $pdf = 'https://www.comune.lauco.ud.it/media/files/030047/attachment/Locandina_Eventi_Lauco_2026_30x60_colore.pdf';
        $payload = [
            'events' => [[
                'title' => 'Evento dal calendario ufficiale',
                'description' => 'Appuntamento riportato nel calendario ufficiale del Comune di Lauco.',
                'start_at_raw' => '2026-08-23',
                'end_at_raw' => '2026-08-23',
                'location_name' => 'Lauco',
                'locality' => 'Lauco',
                'organizer' => 'Comune di Lauco',
                'source_url' => $pdf,
                'secondary_source_url' => '',
                'image_url' => 'https://example.com/evento-1200.jpg',
                'evidence' => 'Titolo, data e località risultano dal calendario PDF ufficiale.',
            ]],
        ];

        $events = \event_ai_web_normalize_events(
            $payload,
            [$pdf],
            ['localities' => ['Lauco']],
            new DateTimeImmutable('2026-08-11'),
            new DateTimeImmutable('2027-08-11')
        );

        self::assertCount(1, $events);
        self::assertSame($pdf, $events[0]['source_url']);
        self::assertSame('https://example.com/evento-1200.jpg', $events[0]['image_url']);
    }
}
