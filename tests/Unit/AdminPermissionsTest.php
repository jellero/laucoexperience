<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminPermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/inc/admin-permissions.php';
    }

    public function testTheThreeExpectedRolesAreAvailable(): void
    {
        self::assertSame(['admin', 'collaboratore', 'whatsapp'], array_keys(admin_roles()));
    }

    public function testAdminCanUseEveryBackofficeCapability(): void
    {
        self::assertTrue(admin_role_can('admin', 'admin.all'));
        self::assertTrue(admin_role_can('admin', 'communications.respond'));
        self::assertTrue(admin_role_can('admin', 'whatsapp.manage'));
    }

    public function testCollaboratorCanOnlyUseCommunicationAreas(): void
    {
        self::assertTrue(admin_role_can('collaboratore', 'dashboard.access'));
        self::assertTrue(admin_role_can('collaboratore', 'communications.respond'));
        self::assertFalse(admin_role_can('collaboratore', 'whatsapp.manage'));
        self::assertFalse(admin_role_can('collaboratore', 'admin.all'));
    }

    public function testWhatsappOperatorCanOnlyUseWhatsappAreas(): void
    {
        self::assertTrue(admin_role_can('whatsapp', 'dashboard.access'));
        self::assertTrue(admin_role_can('whatsapp', 'whatsapp.manage'));
        self::assertFalse(admin_role_can('whatsapp', 'communications.respond'));
        self::assertSame(['gruppi', 'chat'], admin_whatsapp_allowed_views('whatsapp'));
        self::assertTrue(admin_whatsapp_action_allowed('whatsapp', 'send_direct'));
        self::assertFalse(admin_whatsapp_action_allowed('whatsapp', 'save_activity'));
    }

    public function testEndpointPermissionsDefaultToAdminOnly(): void
    {
        self::assertSame('dashboard.access', admin_script_capability('/admin/index.php'));
        self::assertSame('communications.respond', admin_script_capability('/admin/posta-scrivi.php'));
        self::assertSame('whatsapp.manage', admin_script_capability('/admin/volontariato.php'));
        self::assertSame('admin.all', admin_script_capability('/admin/percorso-form.php'));
        self::assertSame('admin.all', admin_script_capability('/admin/future-feature.php'));
    }

    public function testUnknownNonEmptyRoleFailsClosed(): void
    {
        self::assertSame('collaboratore', admin_normalize_role('unexpected-role'));
        self::assertFalse(admin_role_can('unexpected-role', 'admin.all'));
    }
}
