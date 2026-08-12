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

if (!function_exists('admin_normalize_role')) {
    function admin_normalize_role(?string $role): string
    {
        $role = strtolower(trim((string) $role));
        if ($role === '') {
            return 'admin';
        }
        return array_key_exists($role, admin_roles()) ? $role : 'collaboratore';
    }
}

if (!function_exists('admin_role_label')) {
    function admin_role_label(?string $role): string
    {
        $role = admin_normalize_role($role);
        return admin_roles()[$role]['label'];
    }
}

if (!function_exists('admin_role_can')) {
    function admin_role_can(?string $role, string $capability): bool
    {
        $role = admin_normalize_role($role);
        if ($role === 'admin') {
            return true;
        }

        $capabilities = [
            'collaboratore' => ['dashboard.access', 'communications.respond'],
            'whatsapp' => ['dashboard.access', 'whatsapp.manage'],
        ];

        return in_array($capability, $capabilities[$role] ?? [], true);
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
    function admin_whatsapp_allowed_views(?string $role): array
    {
        return admin_normalize_role($role) === 'admin'
            ? ['overview', 'volontari', 'gruppi', 'chat', 'attivita', 'sentieri']
            : ['gruppi', 'chat'];
    }
}

if (!function_exists('admin_whatsapp_action_allowed')) {
    function admin_whatsapp_action_allowed(?string $role, string $action): bool
    {
        if (admin_normalize_role($role) === 'admin') {
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
