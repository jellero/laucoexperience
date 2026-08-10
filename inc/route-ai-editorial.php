<?php
declare(strict_types=1);

if (!function_exists('lauco_route_ai_clean')) {
    function lauco_route_ai_clean(mixed $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)) ?? '');
    }
}

if (!function_exists('lauco_route_ai_profile')) {
    /** @param array<string,mixed> $route @return array<string,mixed> */
    function lauco_route_ai_profile(array $route): array
    {
        if (!function_exists('gpx_abs_path') || !function_exists('gpx_extract_points') || !function_exists('gpx_haversine_km')) {
            return [];
        }

        $gpxFile = trim((string) ($route['gpx_file'] ?? ''));
        $absolutePath = $gpxFile !== '' ? gpx_abs_path($gpxFile) : null;
        if (!$absolutePath) {
            return [];
        }

        $points = gpx_extract_points($absolutePath);
        if (count($points) < 2) {
            return [];
        }

        $segments = [];
        $totalKm = 0.0;
        $ascentM = 0.0;
        $descentM = 0.0;
        $elevations = [];
        $highestElevation = null;
        $highestDistanceKm = 0.0;

        foreach ($points as $index => $point) {
            if ($point['ele'] !== null) {
                $elevation = (float) $point['ele'];
                $elevations[] = $elevation;
                if ($highestElevation === null || $elevation > $highestElevation) {
                    $highestElevation = $elevation;
                    $highestDistanceKm = $totalKm;
                }
            }

            if ($index === 0) {
                continue;
            }

            $prev = $points[$index - 1];
            $segmentKm = gpx_haversine_km(
                (float) $prev['lat'],
                (float) $prev['lon'],
                (float) $point['lat'],
                (float) $point['lon']
            );
            $totalKm += $segmentKm;

            $delta = null;
            if ($prev['ele'] !== null && $point['ele'] !== null) {
                $delta = (float) $point['ele'] - (float) $prev['ele'];
                if ($delta > 1.5) {
                    $ascentM += $delta;
                } elseif ($delta < -1.5) {
                    $descentM += abs($delta);
                }
            }

            $segments[] = [
                'end_km' => $totalKm,
                'delta_m' => $delta,
            ];
        }

        $firstHalfAscentM = 0.0;
        $secondHalfAscentM = 0.0;
        $halfKm = $totalKm / 2;
        foreach ($segments as $segment) {
            $delta = $segment['delta_m'];
            if ($delta === null || $delta <= 1.5) {
                continue;
            }
            if ((float) $segment['end_km'] <= $halfKm) {
                $firstHalfAscentM += $delta;
            } else {
                $secondHalfAscentM += $delta;
            }
        }

        $start = $points[0];
        $end = $points[count($points) - 1];
        $startEndM = gpx_haversine_km(
            (float) $start['lat'],
            (float) $start['lon'],
            (float) $end['lat'],
            (float) $end['lon']
        ) * 1000;

        $minElevation = $elevations ? min($elevations) : null;
        $maxElevation = $elevations ? max($elevations) : null;
        $highPointProgress = ($highestElevation !== null && $totalKm > 0)
            ? (int) round(($highestDistanceKm / $totalKm) * 100)
            : null;

        $climbDistribution = 'equilibrato';
        if ($ascentM >= 40) {
            $shareFirst = $firstHalfAscentM / max(1.0, $ascentM);
            if ($shareFirst >= 0.68) {
                $climbDistribution = 'salita prevalentemente nella prima metà';
            } elseif ($shareFirst <= 0.32) {
                $climbDistribution = 'salita prevalentemente nella seconda metà';
            }
        }

        return [
            'source' => 'GPX',
            'track_points' => count($points),
            'track_length_km' => round($totalKm, 2),
            'route_shape' => $startEndM <= 250 ? 'anello_o_ritorno_vicino_alla_partenza' : 'partenza_e_arrivo_distinti',
            'start_end_distance_m' => (int) round($startEndM),
            'elevation_min_m' => $minElevation !== null ? (int) round($minElevation) : null,
            'elevation_max_m' => $maxElevation !== null ? (int) round($maxElevation) : null,
            'elevation_range_m' => ($minElevation !== null && $maxElevation !== null) ? (int) round($maxElevation - $minElevation) : null,
            'ascent_from_profile_m' => (int) round($ascentM),
            'descent_from_profile_m' => (int) round($descentM),
            'highest_point_progress_pct' => $highPointProgress,
            'climb_distribution' => $climbDistribution,
        ];
    }
}

if (!function_exists('lauco_route_ai_sample_points')) {
    /** @param list<array<string,mixed>> $points @return list<array<string,mixed>> */
    function lauco_route_ai_sample_points(array $points, int $maxPoints = 180): array
    {
        if (count($points) <= $maxPoints) {
            return $points;
        }

        $step = max(1, (int) floor(count($points) / $maxPoints));
        $sample = [];
        for ($i = 0, $count = count($points); $i < $count; $i += $step) {
            $sample[] = $points[$i];
        }
        $last = $points[count($points) - 1];
        if ($sample[count($sample) - 1] !== $last) {
            $sample[] = $last;
        }
        return $sample;
    }
}

if (!function_exists('lauco_route_ai_nearby_places')) {
    /** @param array<string,mixed> $route @return list<array<string,mixed>> */
    function lauco_route_ai_nearby_places(PDO $pdo, array $route): array
    {
        if (!function_exists('gpx_abs_path') || !function_exists('gpx_extract_points') || !function_exists('gpx_haversine_km')) {
            return [];
        }

        $gpxFile = trim((string) ($route['gpx_file'] ?? ''));
        $absolutePath = $gpxFile !== '' ? gpx_abs_path($gpxFile) : null;
        if (!$absolutePath) {
            return [];
        }

        $points = lauco_route_ai_sample_points(gpx_extract_points($absolutePath));
        if (!$points) {
            return [];
        }

        try {
            $stmt = $pdo->query(
                "SELECT titolo, categoria, localita, excerpt, lat, lng
                 FROM luoghi
                 WHERE pubblicato = 1 AND lat IS NOT NULL AND lng IS NOT NULL"
            );
            $places = $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            return [];
        }

        $nearby = [];
        foreach ($places as $place) {
            if (!is_array($place) || !is_numeric($place['lat'] ?? null) || !is_numeric($place['lng'] ?? null)) {
                continue;
            }

            $placeLat = (float) $place['lat'];
            $placeLng = (float) $place['lng'];
            $minKm = INF;
            foreach ($points as $point) {
                $distanceKm = gpx_haversine_km(
                    (float) $point['lat'],
                    (float) $point['lon'],
                    $placeLat,
                    $placeLng
                );
                if ($distanceKm < $minKm) {
                    $minKm = $distanceKm;
                }
            }

            if (!is_finite($minKm) || $minKm > 1.5) {
                continue;
            }

            $distanceM = (int) round($minKm * 1000);
            $nearby[] = [
                'title' => lauco_route_ai_clean($place['titolo'] ?? ''),
                'category' => lauco_route_ai_clean($place['categoria'] ?? ''),
                'location' => lauco_route_ai_clean($place['localita'] ?? ''),
                'excerpt' => mb_substr(lauco_route_ai_clean($place['excerpt'] ?? ''), 0, 280),
                'distance_to_track_m_approx' => $distanceM,
                'relation' => $distanceM <= 150
                    ? 'sul_tracciato_o_immediatamente_adiacente'
                    : ($distanceM <= 750 ? 'nelle_vicinanze_del_tracciato' : 'nell_area_del_percorso'),
            ];
        }

        usort($nearby, static fn (array $a, array $b): int => ($a['distance_to_track_m_approx'] ?? PHP_INT_MAX) <=> ($b['distance_to_track_m_approx'] ?? PHP_INT_MAX));
        return array_slice($nearby, 0, 5);
    }
}

if (!function_exists('lauco_route_ai_enrich_request')) {
    /** @return array{developer:string,user:string} */
    function lauco_route_ai_enrich_request(string $schemaName, string $developerInstructions, string $userInput): array
    {
        if (!in_array($schemaName, ['lauco_route_content', 'lauco_route_all_locales'], true)) {
            return ['developer' => $developerInstructions, 'user' => $userInput];
        }

        $developerInstructions .= "\n\nREGOLE EDITORIALI VINCOLANTI PER LE SCHEDE PERCORSO:\n"
            . "- La description deve essere un testo editoriale utile, non la ripetizione del box tecnico. Scrivi 3-5 paragrafi reali e, quando i dati lo consentono, circa 700-1400 caratteri per lingua.\n"
            . "- Non aprire elencando in sequenza distanza, dislivello, durata e difficoltà. Interpreta questi dati per spiegare impegno e andamento del percorso senza duplicarli meccanicamente.\n"
            . "- Usa gpx_profile soltanto per caratteristiche verificabili del tracciato: forma del percorso, quota minima/massima, escursione altimetrica, distribuzione della salita e posizione relativa del punto più alto.\n"
            . "- I verified_nearby_places possono essere citati solo con formule coerenti con relation. La vicinanza geometrica non prova accesso, attraversamento, parcheggio o collegamento diretto.\n"
            . "- Non inventare paesaggi, boschi, panorami, fondo, segnaletica, esposizione, servizi, acqua, parcheggi, accessi o pericoli se non sono presenti nei dati.\n"
            . "- Non scrivere nella description, excerpt, SEO o card frasi come 'non sono disponibili ulteriori informazioni'. Le carenze delle fonti vanno solo in warnings e soltanto se sono specifiche e utili alla revisione.\n"
            . "- Evita tono burocratico, formule autoreferenziali e ripetizioni. Scrivi per un escursionista che deve capire che tipo di esperienza e impegno aspettarsi dai dati disponibili.\n"
            . "- Limiti editoriali: seo_title massimo 70 caratteri, subtitle massimo 130, excerpt massimo 350, seo_description massimo 155, card_text massimo 180.\n"
            . "- Se il contenuto sorgente è scarso, ricava valore editoriale dall'interpretazione prudente dei dati tecnici e del profilo GPX, senza aggiungere fatti non verificati.";

        try {
            $payload = json_decode($userInput, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            return ['developer' => $developerInstructions, 'user' => $userInput];
        }

        if (!is_array($payload)) {
            return ['developer' => $developerInstructions, 'user' => $userInput];
        }

        $context = $payload['existing_route'] ?? null;
        if (!is_array($context)) {
            return ['developer' => $developerInstructions, 'user' => $userInput];
        }

        $context['editorial_brief'] = [
            'description_structure' => '3-5 paragrafi',
            'description_target_chars' => '700-1400 circa quando i dati lo consentono',
            'goal' => 'spiegare carattere tecnico e andamento del percorso senza ripetere il box dati',
            'warnings_policy' => 'solo carenze specifiche e utili alla revisione; nessuna frase generica di mancanza dati nei testi pubblicabili',
        ];

        $routeId = (int) ($context['id'] ?? 0);
        $pdo = $GLOBALS['pdo'] ?? null;
        if ($routeId > 0 && $pdo instanceof PDO) {
            try {
                $stmt = $pdo->prepare('SELECT id, gpx_file, tipo FROM percorsi WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $routeId]);
                $route = $stmt->fetch();
                if (is_array($route)) {
                    $profile = lauco_route_ai_profile($route);
                    if ($profile) {
                        $context['gpx_profile'] = $profile;
                    }
                    $nearbyPlaces = lauco_route_ai_nearby_places($pdo, $route);
                    if ($nearbyPlaces) {
                        $context['verified_nearby_places'] = $nearbyPlaces;
                    }
                }
            } catch (Throwable $e) {
                // L'arricchimento è best effort: la generazione AI deve restare disponibile.
            }
        }

        $payload['existing_route'] = $context;
        try {
            $userInput = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            // Conserva l'input originale se la serializzazione fallisce.
        }

        return ['developer' => $developerInstructions, 'user' => $userInput];
    }
}
