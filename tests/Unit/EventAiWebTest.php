<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/event-import-v2.php';
require_once dirname(__DIR__, 2) . '/inc/event-ai-web.php';

final class EventAiWebTest extends TestCase
{
    public function testCanonicalSourceDropsTrackingParameters(): void
    {
        self::assertSame(
            'https://www.turismofvg.it/eventi/festa-del-miele',
            \event_ai_web_canonical_url(
                'https://www.turismofvg.it/eventi/festa-del-miele/?utm_source=chatgpt.com&fbclid=abc'
            )
        );
    }

    public function testImageUrlAcceptsOnlyPublicHttpsStyleUrls(): void
    {
        self::assertSame(
            'https://www.turismofvg.it/media/eventi/festa.jpg',
            \event_ai_web_image_url('https://www.turismofvg.it/media/eventi/festa.jpg')
        );
        self::assertNull(\event_ai_web_image_url('http://www.turismofvg.it/media/eventi/festa.jpg'));
        self::assertNull(\event_ai_web_image_url('https://127.0.0.1/festa.jpg'));
        self::assertNull(\event_ai_web_image_url('https://localhost/festa.jpg'));
    }

    public function testNormalizationKeepsOnlyFutureLocalEventsBackedByActualWebSources(): void
    {
        $source = 'https://www.turismofvg.it/eventi/festa-del-miele-di-montagna';
        $image = 'https://www.turismofvg.it/media/eventi/festa-del-miele.jpg';
        $webSources = [
            $source . '?utm_source=chatgpt.com',
            'https://www.comune.lauco.ud.it/it/events',
        ];

        $valid = [
            'title' => 'Festa del miele di montagna',
            'description' => 'Evento dedicato al miele di montagna nel territorio di Lauco.',
            'start_at_raw' => '2026-08-23',
            'end_at_raw' => '2026-08-23',
            'location_name' => 'Lauco',
            'locality' => 'Lauco',
            'organizer' => '',
            'source_url' => $source,
            'secondary_source_url' => '',
            'image_url' => $image,
            'evidence' => 'La fonte riporta titolo, data e località Lauco.',
        ];

        $payload = [
            'events' => [
                $valid,
                array_replace($valid, [
                    'title' => 'Fonte inventata',
                    'source_url' => 'https://example.invalid/evento',
                ]),
                array_replace($valid, [
                    'title' => 'Evento fuori comune',
                    'locality' => 'Tolmezzo',
                    'location_name' => 'Tolmezzo',
                ]),
                array_replace($valid, [
                    'title' => 'Evento passato',
                    'start_at_raw' => '2026-07-01',
                    'end_at_raw' => '2026-07-01',
                ]),
            ],
        ];

        $events = \event_ai_web_normalize_events(
            $payload,
            $webSources,
            ['localities' => ['Lauco', 'Vinaio', 'Buttea']],
            new DateTimeImmutable('2026-08-10'),
            new DateTimeImmutable('2027-08-10')
        );

        self::assertCount(1, $events);
        self::assertSame('Festa del miele di montagna', $events[0]['title']);
        self::assertSame($source, $events[0]['source_url']);
        self::assertSame($image, $events[0]['image_url']);
        self::assertSame('openai_web_search', $events[0]['raw']['origin']);
    }

    public function testSearchPassesSeparateLaucoFromNearbyCarnia(): void
    {
        $passes = \event_ai_web_search_passes([
            'localities' => ['Lauco', 'Vinaio', 'Tolmezzo', 'Preone'],
            'primary_localities' => ['Lauco', 'Vinaio'],
            'nearby_localities' => ['Tolmezzo', 'Preone'],
        ]);

        self::assertCount(2, $passes);
        self::assertSame('lauco', $passes[0]['name']);
        self::assertSame(['Lauco', 'Vinaio'], $passes[0]['localities']);
        self::assertSame('carnia-vicina', $passes[1]['name']);
        self::assertSame(['Tolmezzo', 'Preone'], $passes[1]['localities']);
    }
}
