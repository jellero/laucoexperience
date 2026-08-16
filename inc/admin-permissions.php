<?php
declare(strict_types=1);

if (!function_exists('admin_roles')) {
    /** @return array<string,array{label:string,description:string}> */
    function admin_roles(): array
    {
        return [
            'admin' => [
                'label' => 'Amministratore',
                'description' => 'Accesso completo a contenuti, comunicazioni, WhatsApp, configurazione e account.',
            ],
            'collaboratore' => [
                'label' => 'Collaboratore',
                'description' => 'Può leggere e rispondere a messaggi, email, segnalazioni e contributi.',
            ],
            'whatsapp' => [
                'label' => 'Operatore WhatsApp',
                'description' => 'Può gestire gruppi, inviti e conversazioni WhatsApp.',
            ],
        ];
    }
}

if (!function_exists('admin_filter_roles')) {
    /** @return list<string> */
    function admin_filter_roles(array|string|null $roles): array
    {
        $values = is_array($roles) ? $roles : explode(',', (string) $roles);
        $selected = [];
        foreach ($values as $role) {
            if (!is_scalar($role)) {
                continue;
            }
            $role = strtolower(trim((string) $role));
            if (array_key_exists($role, admin_roles())) {
                $selected[$role] = true;
            }
        }

        return array_values(array_filter(
            array_keys(admin_roles()),
            static fn (string $role): bool => isset($selected[$role])
        ));
    }
}

if (!function_exists('admin_normalize_roles')) {
    /** @return list<string> */
    function admin_normalize_roles(array|string|null $roles): array
    {
        $selected = admin_filter_roles($roles);
        if ($selected === []) {
            $hasInput = is_array($roles) ? $roles !== [] : trim((string) $roles) !== '';
            return $hasInput ? ['collaboratore'] : ['admin'];
        }
        return in_array('admin', $selected, true) ? ['admin'] : $selected;
    }
}

if (!function_exists('admin_roles_value')) {
    function admin_roles_value(array|string|null $roles): string
    {
        $selected = admin_filter_roles($roles);
        if ($selected === []) {
            return '';
        }
        return implode(',', in_array('admin', $selected, true) ? ['admin'] : $selected);
    }
}

if (!function_exists('admin_normalize_role')) {
    function admin_normalize_role(?string $role): string
    {
        return implode(',', admin_normalize_roles($role));
    }
}

if (!function_exists('admin_role_has')) {
    function admin_role_has(array|string|null $roles, string $required): bool
    {
        return in_array($required, admin_normalize_roles($roles), true);
    }
}

if (!function_exists('admin_role_label')) {
    function admin_role_label(?string $role): string
    {
        $labels = [];
        foreach (admin_normalize_roles($role) as $selected) {
            $labels[] = admin_roles()[$selected]['label'];
        }
        return implode(' + ', $labels);
    }
}

if (!function_exists('admin_role_can')) {
    function admin_role_can(array|string|null $role, string $capability): bool
    {
        $roles = admin_normalize_roles($role);
        if (in_array('admin', $roles, true)) {
            return true;
        }
        if ($capability === 'dashboard.access') {
            return $roles !== [];
        }
        return ($capability === 'communications.respond' && in_array('collaboratore', $roles, true))
            || ($capability === 'whatsapp.manage' && in_array('whatsapp', $roles, true));
    }
}

if (!function_exists('admin_script_capability')) {
    function admin_script_capability(string $script): string
    {
        $script = strtolower(basename($script));
        if ($script === 'index.php') {
            return 'dashboard.access';
        }

        $communications = [
            'contatti-messaggi.php',
            'contatto-messaggio.php',
            'contributi.php',
            'contributo.php',
            'segnalazioni.php',
            'segnalazione.php',
            'posta.php',
            'posta-messaggio.php',
            'posta-scrivi.php',
            'posta-azione.php',
            'posta-allegato.php',
            'posta-count.php',
            'newsletter-image-upload.php',
        ];
        if (in_array($script, $communications, true)) {
            return 'communications.respond';
        }

        if ($script === 'volontariato.php') {
            return 'whatsapp.manage';
        }

        return 'admin.all';
    }
}

if (!function_exists('admin_whatsapp_allowed_views')) {
    /** @return list<string> */
    function admin_whatsapp_allowed_views(array|string|null $role): array
    {
        return admin_role_can($role, 'admin.all')
            ? ['overview', 'volontari', 'gruppi', 'chat', 'attivita']
            : ['gruppi', 'chat'];
    }
}

if (!function_exists('admin_whatsapp_action_allowed')) {
    function admin_whatsapp_action_allowed(array|string|null $role, string $action): bool
    {
        if (admin_role_can($role, 'admin.all')) {
            return true;
        }

        return in_array($action, [
            'save_group',
            'assign_member',
            'membership_status',
            'send_group',
            'send_direct',
            'mark_read',
            'process_queue',
        ], true);
    }
}
