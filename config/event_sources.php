<?php
declare(strict_types=1);

return [
    'turismofvg_carnia' => [
        'name' => 'PromoTurismoFVG — Carnia',
        'enabled' => true,
        'listing_url' => 'https://www.turismofvg.it/eventi?_area=1871',
        'allowed_hosts' => ['turismofvg.it', 'www.turismofvg.it'],
        'link_pattern' => '~/(?:it/)?(?:eventi|events)/[^?#]+~i',
        'localities' => [
            'Lauco', 'Villa Santina', 'Raveo', 'Verzegnis', 'Tolmezzo', 'Enemonzo',
            'Zuglio', 'Ovaro', 'Ampezzo', 'Socchieve', 'Preone', 'Cavazzo Carnico', 'Arta Terme',
        ],
    ],
    'comune_lauco' => [
        'name' => 'Comune di Lauco',
        'enabled' => false,
        'listing_url' => 'https://www.comune.lauco.ud.it/',
        'allowed_hosts' => ['comune.lauco.ud.it', 'www.comune.lauco.ud.it'],
        'link_pattern' => '~/(?:eventi|novita)/[^?#]+~i',
        'localities' => ['Lauco'],
    ],
];
