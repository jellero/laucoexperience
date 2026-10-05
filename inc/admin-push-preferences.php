<?php
declare(strict_types=1);

require_once __DIR__ . '/admin-push.php';
require_once __DIR__ . '/admin-permissions.php';

if (!function_exists('admin_push_categories')) {
    /** @return array<string,array{label:string,description:string,capability:string}> */
    function admin_push_categories(): array
    {
        return [
            'mail' => [
                'label' => 'Posta',
                'description' => 'Nuove email nella casella condivisa.',
                'capability' => 'communications.respond',
            ],
            'contacts' => [
                'label' => 'Messaggi',
                'description' => 'Nuovi messaggi dal modulo contatti.',
                'capability' => 'communications.respond',
            ],
            'reports' => [
                'label' => 'Segnalazioni',
                'description' => 'Nuove segnalazioni di problemi.',
                'capability' => 'communications.respond',
            ],
            'contributions' => [
                'label' => 'Contributi',
                'description' => 'Nuovi contributi inviati dal sito.',
                'capability' => 'communications.respond',
            ],
            'volunteers' => [
                'label' => 'Volontariato',
                'description' => 'Nuove disponibilità dei volontari.',
                'capability' => 'admin.all',
            ],
            'whatsapp' => [
                'label' => 'WhatsApp',
                'description' => 'Nuovi messaggi WhatsApp ricevuti.',
                'capability' => 'whatsapp.manage',
            ],
            'newsletter' => [
                'label' => 'Newsletter',
                'description' => 'Nuove iscrizioni alla newsletter.',
                'capability' => 'admin.all',
            ],
        ];
    }
}

if (!function_exists('admin_push_allowed_categories')) {
    /** @return array<string,array{label:string,description:string,capability:string}> */
    function admin_push_allowed_categories(array|string|null $role): array
    {
        return array_filter(
            admin_push_categories(),
            static fn (array $category): bool => admin_role_can($role, $category['capability'])
        );
    }
}

if (!function_exists('admin_push_preferences')) {
    /** @return array<string,bool> */
    function admin_push_preferences(PDO $pdo, int $adminId, array|string|null $role): array
    {
        $allowed = admin_push_allowed_categories($role);
        $preferences = array_fill_keys(array_keys($allowed), true);
        if ($adminId <= 0 || $preferences === []) {
            return $preferences;
        }

        try {
            $stmt = $pdo->prepare('SELECT category, enabled FROM admin_push_preferences WHERE admin_id = :admin_id');
            $stmt->execute(['admin_id' => $adminId]);
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $category = (string) ($row['category'] ?? '');
                if (array_key_exists($category, $preferences)) {
                    $preferences[$category] = (bool) ($row['enabled'] ?? false);
                }
            }
        } catch (Throwable $exception) {
            error_log('[Lauco Push preferences] ' . $exception->getMessage());
        }

        return $preferences;
    }
}

if (!function_exists('admin_push_save_preferences')) {
    /**
     * @param array<string,mixed> $submitted
     * @return array<string,bool>
     */
    function admin_push_save_preferences(PDO $pdo, int $adminId, array $submitted, array|string|null $role): array
    {
        if ($adminId <= 0) {
            throw new InvalidArgumentException('Utente amministrativo non valido.');
        }

        $allowed = admin_push_allowed_categories($role);
        $stmt = $pdo->prepare(
            'INSERT INTO admin_push_preferences (admin_id, category, enabled) '
            . 'VALUES (:admin_id, :category, :enabled) '
            . 'ON DUPLICATE KEY UPDATE enabled = VALUES(enabled), updated_at = CURRENT_TIMESTAMP'
        );

        foreach (array_keys($allowed) as $category) {
            $enabled = filter_var($submitted[$category] ?? false, FILTER_VALIDATE_BOOL);
            $stmt->execute([
                'admin_id' => $adminId,
                'category' => $category,
                'enabled' => $enabled ? 1 : 0,
            ]);
        }

        return admin_push_preferences($pdo, $adminId, $role);
    }
}

if (!function_exists('admin_push_preference_enabled')) {
    function admin_push_preference_enabled(PDO $pdo, int $adminId, string $category): bool
    {
        $stmt = $pdo->prepare(
            'SELECT enabled FROM admin_push_preferences WHERE admin_id = :admin_id AND category = :category LIMIT 1'
        );
        $stmt->execute(['admin_id' => $adminId, 'category' => $category]);
        $value = $stmt->fetchColumn();
        return $value === false ? true : (bool) $value;
    }
}

if (!function_exists('admin_push_send_category')) {
    /** @return array{users:int,sent:int,failed:int,removed:int} */
    function admin_push_send_category(PDO $pdo, string $category): array
    {
        $categories = admin_push_categories();
        if (!isset($categories[$category])) {
            throw new InvalidArgumentException('Categoria notifica non valida.');
        }

        $stmt = $pdo->query(
            'SELECT DISTINCT s.admin_id, u.ruolo '
            . 'FROM admin_push_subscriptions s '
            . 'INNER JOIN utenti u ON u.id = s.admin_id '
            . 'ORDER BY s.admin_id'
        );
        $result = ['users' => 0, 'sent' => 0, 'failed' => 0, 'removed' => 0];

        foreach ($stmt->fetchAll() ?: [] as $row) {
            $adminId = (int) ($row['admin_id'] ?? 0);
            $role = (string) ($row['ruolo'] ?? '');
            if ($adminId <= 0 || !admin_role_can($role, $categories[$category]['capability'])) {
                continue;
            }
            if (!admin_push_preference_enabled($pdo, $adminId, $category)) {
                continue;
            }

            $delivery = admin_push_send_to_admin($pdo, $adminId);
            $result['users']++;
            $result['sent'] += $delivery['sent'];
            $result['failed'] += $delivery['failed'];
            $result['removed'] += $delivery['removed'];
        }

        return $result;
    }
}

if (!function_exists('admin_push_notify')) {
    function admin_push_notify(PDO $pdo, string $category): void
    {
        try {
            admin_push_send_category($pdo, $category);
        } catch (Throwable $exception) {
            error_log('[Lauco Push ' . $category . '] ' . $exception->getMessage());
        }
    }
}
