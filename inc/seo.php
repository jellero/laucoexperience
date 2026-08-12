<?php
declare(strict_types=1);

require_once __DIR__ . '/translations.php';
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/fractions-content.php';

if (!function_exists('seo_base_url')) {
    function seo_base_url(): string
    {
        $url = trim((string) lauco_env('APP_URL', 'https://laucoexperience.it'));
        return preg_match('~^https?://~i', $url) ? rtrim($url, '/') : 'https://laucoexperience.it';
    }
}

if (!function_exists('seo_path')) {
    function seo_path(): string
    {
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
        if ($path === '/index' || $path === '/index.php') return '/';
        if (str_ends_with(strtolower($path), '.php')) $path = substr($path, 0, -4);
        return $path === '/' ? '/' : '/' . trim($path, '/');
    }
}

if (!function_exists('seo_abs')) {
    function seo_abs(string $url): string
    {
        if (preg_match('~^https?://~i', $url)) return $url;
        return seo_base_url() . '/' . ltrim($url, '/');
    }
}

if (!function_exists('seo_url')) {
    function seo_url(string $path, string $locale, ?string $slug = null): string
    {
        $query = [];
        if ($slug !== null && $slug !== '') $query['slug'] = $slug;
        if ($locale !== 'it') $query['lang'] = $locale;
        return seo_base_url() . ($path === '/' ? '/' : $path) . ($query ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');
    }
}

if (!function_exists('seo_text')) {
    function seo_text(string $value, int $limit = 160): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
        if (mb_strlen($value) <= $limit) return $value;
        return rtrim(mb_substr($value, 0, $limit - 1)) . '…';
    }
}

if (!function_exists('seo_labels')) {
    /** @return array<string,string> */
    function seo_labels(string $locale): array
    {
        $it = [
            '/' => 'Lauco Experience', '/map' => 'Mappa dei sentieri', '/mappa1' => 'Mappa',
            '/segnaletica' => 'Segnaletica dei sentieri', '/consigli' => 'Consigli escursionistici',
            '/itinerari-piedi' => 'Itinerari a piedi', '/itinerari-mtb' => 'Itinerari MTB',
            '/itinerari-speciali' => 'Itinerari speciali', '/forra' => 'Forra del Vinadia',
            '/barbecue' => 'Aree barbecue', '/gestione-sentieri' => 'Gestione dei sentieri', '/stato-sentieri' => 'Stato dei sentieri',
            '/luoghi' => 'Luoghi e frazioni', '/frazioni' => 'Frazioni e borgate di Lauco',
            '/storia' => 'Storia di Lauco', '/natura' => 'Natura di Lauco',
            '/come-arrivare' => 'Come arrivare a Lauco', '/eventi' => 'Eventi a Lauco',
            '/eventi/archivio' => 'Archivio eventi di Lauco',
            '/contatti' => 'Contatti', '/contribuisci' => 'Contribuisci', '/segnala-problema' => 'Segnala un problema',
            '/privacy' => 'Privacy Policy', '/cookie' => 'Cookie Policy', '/login' => 'Accesso',
            '/crea-account' => 'Crea account', '/400' => 'Pagina non trovata',
        ];
        $translations = [
            'en' => ['Mappa dei sentieri'=>'Trail map','Segnaletica dei sentieri'=>'Trail signage','Consigli escursionistici'=>'Hiking advice','Itinerari a piedi'=>'Hiking routes','Itinerari MTB'=>'MTB routes','Itinerari speciali'=>'Special routes','Forra del Vinadia'=>'Vinadia Gorge','Aree barbecue'=>'Barbecue areas','Gestione dei sentieri'=>'Trail management','Stato dei sentieri'=>'Trail status','Luoghi e frazioni'=>'Places and villages','Frazioni e borgate di Lauco'=>'Villages and hamlets of Lauco','Storia di Lauco'=>'History of Lauco','Natura di Lauco'=>'Nature of Lauco','Come arrivare a Lauco'=>'How to reach Lauco','Eventi a Lauco'=>'Events in Lauco','Archivio eventi di Lauco'=>'Lauco events archive','Contatti'=>'Contact','Contribuisci'=>'Contribute','Segnala un problema'=>'Report a problem','Accesso'=>'Login','Crea account'=>'Create account','Pagina non trovata'=>'Page not found'],
            'de' => ['Mappa dei sentieri'=>'Wanderkarte','Segnaletica dei sentieri'=>'Wegemarkierung','Consigli escursionistici'=>'Wandertipps','Itinerari a piedi'=>'Wanderrouten','Itinerari MTB'=>'MTB-Routen','Itinerari speciali'=>'Besondere Routen','Forra del Vinadia'=>'Vinadia-Schlucht','Aree barbecue'=>'Grillplätze','Gestione dei sentieri'=>'Wegemanagement','Stato dei sentieri'=>'Zustand der Wege','Luoghi e frazioni'=>'Orte und Dörfer','Frazioni e borgate di Lauco'=>'Fraktionen und Weiler von Lauco','Storia di Lauco'=>'Geschichte von Lauco','Natura di Lauco'=>'Natur in Lauco','Come arrivare a Lauco'=>'Anreise nach Lauco','Eventi a Lauco'=>'Veranstaltungen in Lauco','Archivio eventi di Lauco'=>'Veranstaltungsarchiv von Lauco','Contatti'=>'Kontakt','Contribuisci'=>'Mitmachen','Segnala un problema'=>'Problem melden','Accesso'=>'Anmeldung','Crea account'=>'Konto erstellen','Pagina non trovata'=>'Seite nicht gefunden'],
            'sl' => ['Mappa dei sentieri'=>'Zemljevid poti','Segnaletica dei sentieri'=>'Označevanje poti','Consigli escursionistici'=>'Pohodniški nasveti','Itinerari a piedi'=>'Pohodniške poti','Itinerari MTB'=>'MTB-poti','Itinerari speciali'=>'Posebne poti','Forra del Vinadia'=>'Soteska Vinadia','Aree barbecue'=>'Prostori za žar','Gestione dei sentieri'=>'Upravljanje poti','Stato dei sentieri'=>'Stanje poti','Luoghi e frazioni'=>'Kraji in vasi','Frazioni e borgate di Lauco'=>'Vasi in zaselki Lauca','Storia di Lauco'=>'Zgodovina Lauca','Natura di Lauco'=>'Narava Lauca','Come arrivare a Lauco'=>'Kako do Lauca','Eventi a Lauco'=>'Dogodki v Laucu','Archivio eventi di Lauco'=>'Arhiv dogodkov v Laucu','Contatti'=>'Kontakt','Contribuisci'=>'Prispevajte','Segnala un problema'=>'Prijavite težavo','Accesso'=>'Prijava','Crea account'=>'Ustvari račun','Pagina non trovata'=>'Stran ni najdena'],
        ];
        if ($locale === 'it') return $it;
        foreach ($it as $path => $label) $it[$path] = $translations[$locale][$label] ?? $label;
        return $it;
    }
}

if (!function_exists('seo_static_description')) {
    function seo_static_description(string $path, string $locale, string $fallback): string
    {
        $descriptions = [
            'it' => [
                '/' => 'Scopri l’Altopiano di Lauco in Carnia: sentieri, mappa GPX, storia, natura, frazioni, luoghi ed eventi del territorio.',
                '/map' => 'Mappa interattiva dei sentieri di Lauco con tracce GPX, profili altimetrici e informazioni utili per conoscere la rete escursionistica locale.',
                '/itinerari-piedi' => 'Itinerari e sentieri a piedi nel territorio di Lauco, con informazioni sui percorsi e collegamenti alla mappa e alle tracce GPX.',
                '/itinerari-mtb' => 'Percorsi MTB nel territorio di Lauco, sull’altopiano e nei dintorni, con informazioni e tracce per conoscere gli itinerari disponibili.',
                '/forra' => 'La Forra del Vinadia a Lauco: ambiente naturale, caratteristiche della gola, accessi e informazioni per conoscere uno dei luoghi più significativi della Carnia.',
                '/luoghi' => 'Luoghi, frazioni e punti di interesse del territorio di Lauco: patrimonio storico, paesaggio, borghi e testimonianze locali.',
                '/frazioni' => 'Avaglio, Trava, Chiassis, Allegnidis, Vinaio e Buttea: scopri frazioni, borgate, storia, patrimonio e tradizioni del territorio di Lauco.',
                '/storia' => 'Storia di Lauco dalle prime testimonianze al paesaggio rurale: archeologia, borghi, chiese e trasformazioni storiche dell’altopiano.',
                '/natura' => 'Natura di Lauco: boschi, prati, geologia, corsi d’acqua, Forra del Vinadia, flora e fauna dell’altopiano e dei suoi versanti.',
                '/come-arrivare' => 'Come arrivare a Lauco e raggiungere l’altopiano della Carnia: indicazioni e informazioni utili per orientarsi nel territorio.',
                '/eventi' => 'Eventi e appuntamenti nel territorio di Lauco: iniziative culturali, tradizioni, manifestazioni e attività locali in programma.',
                '/eventi/archivio' => 'Archivio degli eventi pubblicati da Lauco Experience: manifestazioni, tradizioni e iniziative svolte nel territorio di Lauco.',
            ],
            'en' => [
                '/' => 'Discover the Lauco Plateau in Carnia: trails, GPX map, history, nature, villages, places and local events.',
                '/map' => 'Interactive map of Lauco trails with GPX tracks, elevation profiles and useful information about the local hiking network.',
                '/itinerari-piedi' => 'Walking and hiking routes in Lauco, with trail information and links to the interactive map and GPX tracks.',
                '/itinerari-mtb' => 'MTB routes across Lauco and its plateau, with information and tracks for exploring the local network.',
                '/forra' => 'Vinadia Gorge in Lauco: natural environment, gorge features, access information and context for one of Carnia’s distinctive places.',
                '/luoghi' => 'Places, villages and points of interest in Lauco: historic heritage, landscape, settlements and local evidence.',
                '/frazioni' => 'Avaglio, Trava, Chiassis, Allegnidis, Vinaio and Buttea: villages, hamlets, history, heritage and traditions of Lauco.',
                '/storia' => 'History of Lauco from early evidence to the rural landscape: archaeology, villages, churches and historic transformations of the plateau.',
                '/natura' => 'Nature in Lauco: woods, meadows, geology, waterways, Vinadia Gorge, flora and wildlife of the plateau and its slopes.',
                '/come-arrivare' => 'How to reach Lauco and the Carnia plateau, with practical information for finding and exploring the area.',
                '/eventi' => 'Events in Lauco: cultural initiatives, traditions, local festivals and activities scheduled across the municipality.',
                '/eventi/archivio' => 'Archive of events published by Lauco Experience, preserving past traditions, initiatives and activities in the area.',
            ],
            'de' => [
                '/' => 'Entdecken Sie das Hochplateau von Lauco in Karnien: Wege, GPX-Karte, Geschichte, Natur, Orte und Veranstaltungen.',
                '/map' => 'Interaktive Karte der Wege von Lauco mit GPX-Tracks, Höhenprofilen und Informationen zum örtlichen Wegenetz.',
                '/itinerari-piedi' => 'Wanderwege und Routen im Gebiet von Lauco mit Informationen, Karten und GPX-Tracks.',
                '/itinerari-mtb' => 'MTB-Routen in Lauco und auf dem Hochplateau mit Informationen und Tracks zum lokalen Wegenetz.',
                '/forra' => 'Die Vinadia-Schlucht bei Lauco: Naturraum, Merkmale der Schlucht, Zugänge und Informationen zu einem besonderen Ort Karniens.',
                '/luoghi' => 'Orte, Fraktionen und Sehenswürdigkeiten von Lauco: historisches Erbe, Landschaft, Dörfer und lokale Zeugnisse.',
                '/frazioni' => 'Avaglio, Trava, Chiassis, Allegnidis, Vinaio und Buttea: Fraktionen, Weiler, Geschichte, Erbe und Traditionen von Lauco.',
                '/storia' => 'Geschichte von Lauco von frühen Zeugnissen bis zur Kulturlandschaft: Archäologie, Dörfer, Kirchen und historische Entwicklungen.',
                '/natura' => 'Natur in Lauco: Wälder, Wiesen, Geologie, Gewässer, Vinadia-Schlucht, Flora und Tierwelt des Hochplateaus.',
                '/come-arrivare' => 'Anreise nach Lauco und zum Hochplateau in Karnien mit praktischen Informationen zur Orientierung.',
                '/eventi' => 'Veranstaltungen in Lauco: Kultur, Traditionen, Feste und lokale Aktivitäten im Gemeindegebiet.',
                '/eventi/archivio' => 'Archiv der von Lauco Experience veröffentlichten Veranstaltungen, Traditionen und Initiativen im Gemeindegebiet.',
            ],
            'sl' => [
                '/' => 'Odkrijte planoto Lauco v Karniji: poti, GPX-zemljevid, zgodovino, naravo, vasi, kraje in dogodke.',
                '/map' => 'Interaktivni zemljevid poti v Laucu z GPX-sledmi, višinskimi profili in informacijami o lokalni pohodniški mreži.',
                '/itinerari-piedi' => 'Pohodniške poti v Laucu z informacijami o trasah ter povezavami do zemljevida in GPX-sledi.',
                '/itinerari-mtb' => 'MTB-poti v Laucu in na planoti z informacijami ter sledmi za raziskovanje lokalne mreže.',
                '/forra' => 'Soteska Vinadia pri Laucu: naravno okolje, značilnosti soteske, dostopi in informacije o enem pomembnejših krajev Karnije.',
                '/luoghi' => 'Kraji, vasi in zanimivosti občine Lauco: zgodovinska dediščina, pokrajina, naselja in lokalne sledi.',
                '/frazioni' => 'Avaglio, Trava, Chiassis, Allegnidis, Vinaio in Buttea: vasi, zaselki, zgodovina, dediščina in tradicije Lauca.',
                '/storia' => 'Zgodovina Lauca od najstarejših sledi do podeželske pokrajine: arheologija, vasi, cerkve in zgodovinske spremembe.',
                '/natura' => 'Narava Lauca: gozdovi, travniki, geologija, vode, soteska Vinadia, rastlinstvo in živalstvo planote.',
                '/come-arrivare' => 'Kako priti v Lauco in na karnijsko planoto z uporabnimi informacijami za orientacijo na območju.',
                '/eventi' => 'Dogodki v Laucu: kulturne pobude, tradicije, prireditve in lokalne dejavnosti v občini.',
                '/eventi/archivio' => 'Arhiv dogodkov Lauco Experience z minulimi prireditvami, tradicijami in pobudami na območju.',
            ],
        ];
        return $descriptions[$locale][$path] ?? $fallback;
    }
}

if (!function_exists('seo_metadata')) {
    /** @return array<string,mixed> */
    function seo_metadata(): array
    {
        $locale = content_language_from_request();
        $path = seo_path();
        $labels = seo_labels($locale);
        $label = $labels[$path] ?? 'Lauco Experience';
        $slug = null;
        $image = 'assets/img/radime.jpg';
        $type = 'website';
        $entity = null;
        $parent = null;

        $generic = match ($locale) {
            'en' => 'Trails, nature, history, places and practical information for discovering the Lauco Plateau in Carnia.',
            'de' => 'Wege, Natur, Geschichte, Orte und praktische Informationen zur Entdeckung des Lauco-Plateaus in Karnien.',
            'sl' => 'Poti, narava, zgodovina, kraji in praktične informacije za odkrivanje planote Lauco v Karniji.',
            default => 'Sentieri, natura, storia, luoghi e informazioni utili per scoprire l’Altopiano di Lauco in Carnia.',
        };
        $description = seo_static_description($path, $locale, $path === '/' ? $generic : $label . '. ' . $generic);

        if ($path === '/eventi/archivio') {
            $parent = ['/eventi', $labels['/eventi'] ?? 'Eventi'];
        }

        $percorso = $GLOBALS['percorso'] ?? null;
        $luogo = $GLOBALS['luogo'] ?? null;
        $evento = $GLOBALS['evento'] ?? null;
        if (str_starts_with($path, '/frazioni/')) {
            $fractionSlug = basename($path);
            $fraction = fraction_content($fractionSlug, $locale);
            if (is_array($fraction)) {
                $label = trim((string) ($fraction['name'] ?? $label));
                $description = seo_text((string) ($fraction['summary'] ?? $generic));
                $image = (string) ($fraction['hero'] ?? 'assets/img/radime.jpg');
                $type = 'article';
                $parent = ['/frazioni', $labels['/frazioni'] ?? 'Frazioni'];
                $entity = [
                    '@type' => 'Place',
                    'name' => $label,
                    'description' => $description,
                    'image' => seo_abs($image),
                    'containedInPlace' => ['@type' => 'AdministrativeArea', 'name' => 'Lauco'],
                    'address' => ['@type' => 'PostalAddress', 'addressLocality' => $label, 'addressRegion' => 'Friuli-Venezia Giulia', 'addressCountry' => 'IT'],
                ];
            }
        } elseif ($path === '/percorso' && is_array($percorso)) {
            $slug = trim((string) ($percorso['slug'] ?? $_GET['slug'] ?? ''));
            $label = trim((string) ($percorso['titolo'] ?? $label));
            $description = seo_text((string) ($percorso['excerpt'] ?? $percorso['descrizione'] ?? $generic));
            $image = (string) ($percorso['cover_image'] ?: 'assets/img/trip11.jpg');
            $type = 'article';
            $routeParent = (($percorso['tipo'] ?? '') === 'mtb') ? '/itinerari-mtb' : '/itinerari-piedi';
            $parent = [$routeParent, $labels[$routeParent] ?? 'Itinerari'];
            $entity = ['@type'=>'Article','headline'=>$label,'description'=>$description,'image'=>[seo_abs($image)],'publisher'=>['@id'=>seo_base_url().'/#organization']];
        } elseif ($path === '/luogo' && is_array($luogo)) {
            $slug = trim((string) ($luogo['slug'] ?? $_GET['slug'] ?? ''));
            $label = trim((string) ($luogo['titolo'] ?? $label));
            $description = seo_text((string) ($luogo['excerpt'] ?? $luogo['descrizione'] ?? $generic));
            $image = (string) ($luogo['cover_image'] ?: 'assets/img/trip4.jpg');
            $type = 'article'; $parent = ['/luoghi', $labels['/luoghi'] ?? 'Luoghi'];
            $entity = ['@type'=>'Place','name'=>$label,'description'=>$description,'image'=>seo_abs($image)];
            if (($luogo['lat'] ?? null) !== null && ($luogo['lng'] ?? null) !== null) $entity['geo']=['@type'=>'GeoCoordinates','latitude'=>(float)$luogo['lat'],'longitude'=>(float)$luogo['lng']];
        } elseif ($path === '/evento' && is_array($evento)) {
            $slug = trim((string) ($evento['slug'] ?? $_GET['slug'] ?? ''));
            $label = trim((string) ($evento['titolo'] ?? $label));
            $description = seo_text((string) ($evento['excerpt'] ?? $evento['contenuto'] ?? $generic));
            $image = (string) ($evento['cover_image'] ?: 'assets/img/trip5.jpg');
            $type = 'article'; $parent = ['/eventi', $labels['/eventi'] ?? 'Eventi'];
            if (!empty($evento['data_evento'])) {
                $place = trim((string) ($evento['localita'] ?? '')) ?: 'Lauco';
                $entity=['@type'=>'Event','name'=>$label,'startDate'=>(string)$evento['data_evento'],'eventStatus'=>'https://schema.org/EventScheduled','eventAttendanceMode'=>'https://schema.org/OfflineEventAttendanceMode','description'=>$description,'url'=>seo_url($path,$locale,$slug),'image'=>[seo_abs($image)],'location'=>['@type'=>'Place','name'=>$place,'address'=>['@type'=>'PostalAddress','addressLocality'=>$place,'addressRegion'=>'Friuli-Venezia Giulia','addressCountry'=>'IT']]];
            }
        }

        $canonical = seo_url($path, $locale, $slug);
        $alternates=[];
        foreach (array_keys(content_supported_languages()) as $lang) $alternates[$lang]=seo_url($path,$lang,$slug);
        $alternates['x-default']=seo_url($path,'it',$slug);
        $title = ($path === '/' ? $label : $label . ' | Lauco Experience');

        $graph=[
            ['@type'=>'Organization','@id'=>seo_base_url().'/#organization','name'=>'Lauco Experience','url'=>seo_base_url().'/','logo'=>seo_abs('assets/img/logo.png')],
            ['@type'=>'WebSite','@id'=>seo_base_url().'/#website','url'=>seo_base_url().'/','name'=>'Lauco Experience','inLanguage'=>$locale,'publisher'=>['@id'=>seo_base_url().'/#organization']],
        ];
        if ($path !== '/') {
            $items=[['@type'=>'ListItem','position'=>1,'name'=>'Lauco Experience','item'=>seo_url('/',$locale)]];
            $pos=2;
            if ($parent) $items[]=['@type'=>'ListItem','position'=>$pos++,'name'=>$parent[1],'item'=>seo_url($parent[0],$locale)];
            $items[]=['@type'=>'ListItem','position'=>$pos,'name'=>$label,'item'=>$canonical];
            $graph[]=['@type'=>'BreadcrumbList','itemListElement'=>$items];
        }
        if ($entity) { $entity['url']=$entity['url'] ?? $canonical; $graph[]=$entity; }

        $noindex=['/400','/mappa1','/login','/crea-account'];
        return [
            'locale'=>$locale,'og_locale'=>['it'=>'it_IT','en'=>'en_GB','de'=>'de_DE','sl'=>'sl_SI'][$locale] ?? 'it_IT',
            'title'=>$title,'description'=>seo_text($description),'canonical'=>$canonical,'alternates'=>$alternates,
            'image'=>seo_abs($image),'type'=>$type,'robots'=>in_array($path,$noindex,true)?'noindex,nofollow':'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
            'json_ld'=>['@context'=>'https://schema.org','@graph'=>$graph],
        ];
    }
}
