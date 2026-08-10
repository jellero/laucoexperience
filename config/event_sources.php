<?php
declare(strict_types=1);

return [
    'ai_web_lauco' => [
        'name' => 'AI + Web Search — Lauco',
        'enabled' => true,
        'kind' => 'ai_web',
        'localities' => [
            'Lauco', 'Vinaio', 'Porteal', 'Buttea', 'Trava', 'Avaglio', 'Allegnidis',
            'Val di Lauco', 'Chiassis',
        ],
    ],
    'turismofvg_carnia' => [
        'name' => 'PromoTurismoFVG — Carnia',
        'enabled' => true,
        'listing_url' => 'https://www.turismofvg.it/eventi?_area=1871',
        'fallback_listing_urls' => [
            'https://www.turismofvg.it/events?_area=1871',
        ],
        'allowed_hosts' => ['turismofvg.it', 'www.turismofvg.it'],
        'link_pattern' => '~/(?:it/)?(?:eventi|events)/[^/?#]+/?$~i',
        'preferred_link_text_pattern' => '~^(?:vai all.?evento|go to the event)$~iu',
        'exclude_path_patterns' => [
            '~/(?:it/)?(?:eventi|events)/(?:cinema|contest|dance|didactic-workshops|laboratori-didattici|exhibitions|mostre|fair-street-markets|fiere-mercatini|family|famiglia|festival|food-and-wine|enogastronomia|guided-tours|visite-guidate|history|storia|local-interest|interesse-locale|meeting|music|musica|re-enactments|rievocazioni|sailing-event|sporting-event|theatrical-performances|spettacoli-teatrali|traditional-feasts|feste-tradizionali|sustainable|wine-and-dine-route)/?$~i',
        ],
        'raw_link_patterns' => [
            '~((?:https://www\.turismofvg\.it)?/(?:it/)?(?:eventi|events)/[a-z0-9][a-z0-9\-]+)~i',
        ],
        'localities' => [
            'Lauco', 'Villa Santina', 'Raveo', 'Verzegnis', 'Tolmezzo', 'Enemonzo',
            'Zuglio', 'Ovaro', 'Ampezzo', 'Socchieve', 'Preone', 'Cavazzo Carnico', 'Arta Terme',
        ],
    ],
    'comune_lauco' => [
        'name' => 'Comune di Lauco',
        'enabled' => true,
        'listing_url' => 'https://www.comune.lauco.ud.it/it/events',
        'fallback_listing_urls' => [
            'https://www.comune.lauco.ud.it/',
        ],
        'allowed_hosts' => ['comune.lauco.ud.it', 'www.comune.lauco.ud.it'],
        'link_pattern' => '~/(?:it/)?(?:(?:events)/[^/?#]+|vivere-il-comune-[0-9]+/eventi-[0-9]+/[^/?#]+)/?$~i',
        'raw_link_patterns' => [
            '~((?:https://www\.comune\.lauco\.ud\.it)?/(?:it/)?events/[a-z0-9][a-z0-9\-]*)~i',
            '~((?:https://www\.comune\.lauco\.ud\.it)?/(?:it/)?vivere-il-comune-[0-9]+/eventi-[0-9]+/[a-z0-9][a-z0-9\-]*)~i',
        ],
        'localities' => ['Lauco', 'Vinaio', 'Porteal', 'Buttea', 'Trava', 'Avaglio', 'Allegnidis', 'Val di Lauco'],
    ],
];
