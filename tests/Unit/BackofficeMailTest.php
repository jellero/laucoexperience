<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/backoffice-mail.php';

final class BackofficeMailTest extends TestCase
{
    public function testDefaultServersUseVerifiedSslEndpoints(): void
    {
        $config = backoffice_mail_config();

        self::assertSame('in.postassl.it', $config['imap_host']);
        self::assertSame(993, $config['imap_port']);
        self::assertTrue($config['imap_validate_cert']);
        self::assertSame('out.postassl.it', $config['smtp_host']);
        self::assertSame(465, $config['smtp_port']);
    }

    public function testRecipientParserSupportsMultipleAddresses(): void
    {
        $recipients = backoffice_mail_parse_recipients('Mario Rossi <mario@example.test>; staff@example.test');

        self::assertCount(2, $recipients);
        self::assertSame('mario@example.test', $recipients[0]->getAddress());
        self::assertSame('staff@example.test', $recipients[1]->getAddress());
    }

    public function testIncomingHtmlRemovesActiveAndRemoteContent(): void
    {
        $html = '<p style="color:red" onclick="alert(1)">Ciao <strong>mondo</strong></p>'
            . '<script>alert(2)</script><img src="https://tracker.example/pixel">'
            . '<a href="javascript:alert(3)">male</a><a href="https://example.test/path">bene</a>';

        $safe = backoffice_mail_sanitize_html($html);

        self::assertStringContainsString('<strong>mondo</strong>', $safe);
        self::assertStringContainsString('href="https://example.test/path"', $safe);
        self::assertStringNotContainsString('script', $safe);
        self::assertStringNotContainsString('<img', $safe);
        self::assertStringNotContainsString('javascript:', $safe);
        self::assertStringNotContainsString('onclick', $safe);
        self::assertStringNotContainsString('style=', $safe);
    }

    public function testHtmlComposerAlwaysProducesPlainTextAlternative(): void
    {
        $plain = backoffice_mail_html_to_text(
            '<h1>Titolo</h1><p>Ciao <strong>mondo</strong></p><ul><li>Uno</li><li>Due</li></ul>'
        );

        self::assertStringContainsString('Titolo', $plain);
        self::assertStringContainsString('Ciao mondo', $plain);
        self::assertStringContainsString('- Uno', $plain);
        self::assertStringContainsString('- Due', $plain);
        self::assertStringNotContainsString('<', $plain);
    }
}
