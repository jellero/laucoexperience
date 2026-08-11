<?php
declare(strict_types=1);

require_once __DIR__ . '/translations.php';
require_once __DIR__ . '/env.php';

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
            '/barbecue' => 'Aree barbecue', '/gestione-sentieri' => 'Gestione dei sentieri',
            '/luoghi' => 'Luoghi e frazioni', '/storia' => 'Storia di Lauco', '/natura' => 'Natura di Lauco',
            '/come-arrivare' => 'Come arrivare a Lauco', '/eventi' => 'Eventi a Lauco',
            '/contatti' => 'Contatti', '/contribuisci' => 'Contribuisci', '/segnala-problema' => 'Segnala un problema',
            '/privacy' => 'Privacy Policy', '/cookie' => 'Cookie Policy', '/login' => 'Accesso',
            '/crea-account' => 'Crea account', '/400' => 'Pagina non trovata',
        ];
        $translations = [
            'en' => ['Mappa dei sentieri'=>'Trail map','Segnaletica dei sentieri'=>'Trail signage','Consigli escursionistici'=>'Hiking advice','Itinerari a piedi'=>'Hiking routes','Itinerari MTB'=>'MTB routes','Itinerari speciali'=>'Special routes','Forra del Vinadia'=>'Vinadia Gorge','Aree barbecue'=>'Barbecue areas','Gestione dei sentieri'=>'Trail management','Luoghi e frazioni'=>'Places and villages','Storia di Lauco'=>'History of Lauco','Natura di Lauco'=>'Nature of Lauco','Come arrivare a Lauco'=>'How to reach Lauco','Eventi a Lauco'=>'Events in Lauco','Contatti'=>'Contact','Contribuisci'=>'Contribute','Segnala un problema'=>'Report a problem','Accesso'=>'Login','Crea account'=>'Create account','Pagina non trovata'=>'Page not found'],
            'de' => ['Mappa dei sentieri'=>'Wanderkarte','Segnaletica dei sentieri'=>'Wegemarkierung','Consigli escursionistici'=>'Wandertipps','Itinerari a piedi'=>'Wanderrouten','Itinerari MTB'=>'MTB-Routen','Itinerari speciali'=>'Besondere Routen','Forra del Vinadia'=>'Vinadia-Schlucht','Aree barbecue'=>'Grillplätze','Gestione dei sentieri'=>'Wegemanagement','Luoghi e frazioni'=>'Orte und Dörfer','Storia di Lauco'=>'Geschichte von Lauco','Natura di Lauco'=>'Natur in Lauco','Come arrivare a Lauco'=>'Anreise nach Lauco','Eventi a Lauco'=>'Veranstaltungen in Lauco','Contatti'=>'Kontakt','Contribuisci'=>'Mitmachen','Segnala un problema'=>'Problem melden','Accesso'=>'Anmeldung','Crea account'=>'Konto erstellen','Pagina non trovata'=>'Seite nicht gefunden'],
            'sl' => ['Mappa dei sentieri'=>'Zemljevid poti','Segnaletica dei sentieri'=>'Označevanje poti','Consigli escursionistici'=>'Pohodniški nasveti','Itinerari a piedi'=>'Pohodniške poti','Itinerari MTB'=>'MTB-poti','Itinerari speciali'=>'Posebne poti','Forra del Vinadia'=>'Soteska Vinadia','Aree barbecue'=>'Prostori za žar','Gestione dei sentieri'=>'Upravljanje poti','Luoghi e frazioni'=>'Kraji in vasi','Storia di Lauco'=>'Zgodovina Lauca','Natura di Lauco'=>'Narava Lauca','Come arrivare a Lauco'=>'Kako do Lauca','Eventi a Lauco'=>'Dogodki v Laucu','Contatti'=>'Kontakt','Contribuisci'=>'Prispevajte','Segnala un problema'=>'Prijavite težavo','Accesso'=>'Prijava','Crea account'=>'Ustvari račun','Pagina non trovata'=>'Stran ni najdena'],
        ];
        if ($locale === 'it') return $it;
        foreach ($it as $path => $label) $it[$path] = $translations[$locale][$label] ?? $label;
        return $it;
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
        $description = $path === '/' ? $generic : $label . '. ' . $generic;

        $percorso = $GLOBALS['percorso'] ?? null;
        $luogo = $GLOBALS['luogo'] ?? null;
        $evento = $GLOBALS['evento'] ?? null;
        if ($path === '/percorso' && is_array($percorso)) {
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
