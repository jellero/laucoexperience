<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/event-import-v2.php';

final class EventImportTest extends TestCase
{
    public function testListingPrioritizesRealEventLinksAndExcludesTaxonomy(): void
    {
        $config = [
            'listing_url' => 'https://www.turismofvg.it/eventi?_area=1871',
            'allowed_hosts' => ['turismofvg.it', 'www.turismofvg.it'],
            'link_pattern' => '~/(?:eventi|events)/[^/?#]+/?$~i',
            'preferred_link_text_pattern' => '~^(?:vai all.?evento|go to the event)$~iu',
            'exclude_path_patterns' => [
                '~/(?:eventi|events)/(?:music|musica|festival)/?$~i',
            ],
            'raw_link_patterns' => [],
        ];

        $html = <<<'HTML'
        <html><body>
            <a href="/events/music">music</a>
            <a href="/eventi/lauco-celtic-fest">Vai all'evento</a>
            <a href="/events/altro-evento">Altro evento</a>
        </body></html>
        HTML;

        self::assertSame([
            'https://www.turismofvg.it/eventi/lauco-celtic-fest',
            'https://www.turismofvg.it/events/altro-evento',
        ], \event_import_listing_links($html, $config));
    }

    public function testEmbeddedJsonEventIsParsedWithoutJsonLd(): void
    {
        $html = <<<'HTML'
        <html><head>
        <script type="application/json">
        {
            "events": [{
                "id": 91,
                "type": "events",
                "title": "Passeggiata Celtica",
                "abstract": "Evento nel territorio di Lauco.",
                "start_date": "2026-07-11 18:00:00",
                "end_date": "2026-07-11 21:00:00",
                "municipality": "Lauco",
                "image_url": "https://example.test/evento.jpg"
            }]
        }
        </script>
        </head><body></body></html>
        HTML;

        $events = \event_import_page_events($html, 'https://www.comune.lauco.ud.it/it/events/passeggiata-celtica');

        self::assertCount(1, $events);
        self::assertSame('Passeggiata Celtica', $events[0]['title']);
        self::assertSame('2026-07-11 18:00:00', $events[0]['start_at_raw']);
        self::assertSame('Lauco', $events[0]['locality']);
    }

    public function testRawSpaLinksCanBeRecoveredFromEmbeddedState(): void
    {
        $config = [
            'listing_url' => 'https://www.comune.lauco.ud.it/it/events',
            'allowed_hosts' => ['comune.lauco.ud.it', 'www.comune.lauco.ud.it'],
            'link_pattern' => '~/(?:it/)?vivere-il-comune-[0-9]+/eventi-[0-9]+/[^/?#]+/?$~i',
            'raw_link_patterns' => [
                '~((?:https://www\.comune\.lauco\.ud\.it)?/(?:it/)?vivere-il-comune-[0-9]+/eventi-[0-9]+/[a-z0-9][a-z0-9\-]*)~i',
            ],
        ];

        $html = '<script>window.__STATE__={"url":"\/it\/vivere-il-comune-100\/eventi-200\/tramonto-celtico-300"};</script>';

        self::assertSame([
            'https://www.comune.lauco.ud.it/it/vivere-il-comune-100/eventi-200/tramonto-celtico-300',
        ], \event_import_listing_links($html, $config));
    }
}
