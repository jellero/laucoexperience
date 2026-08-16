<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TrailManagementTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/inc/sentieri.php';
    }

    public function testTrailStatusesAreIndependentDomainValues(): void
    {
        self::assertSame(
            ['verificato', 'attenzione', 'non_percorribile', 'in_verifica'],
            array_keys(sentieri_statuses())
        );
        self::assertSame('Temporaneamente non percorribile', sentieri_status_label('non_percorribile'));
    }

    public function testTrailSlugAndVerificationDateAreNormalized(): void
    {
        self::assertSame('sentiero-cai-165', sentieri_slugify('Sentiero CAI 165'));
        self::assertSame('LAUCO # 7 V', sentieri_name_from_filename('LAUCO_#_7-V.gpx'));
        self::assertSame('2026-08-16 14:30:00', sentieri_normalize_datetime('2026-08-16T14:30'));
        self::assertNull(sentieri_normalize_datetime(''));
    }

    public function testInvalidVerificationDateIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        sentieri_normalize_datetime('16/08/2026');
    }
}
