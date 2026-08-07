<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use LaucoExperience\Localization\HtmlLocalizer;
use LaucoExperience\Localization\SiteCatalogRepository;
use PHPUnit\Framework\TestCase;

final class HtmlLocalizerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/lauco-catalog-' . bin2hex(random_bytes(6));
        mkdir($this->directory . '/defaults', 0770, true);
        mkdir($this->directory . '/runtime', 0770, true);
        foreach (['it' => 'Controlla il meteo © Lauco Experience', 'en' => 'Check the weather © Lauco Experience', 'de' => 'Prüfe das Wetter © Lauco Experience', 'sl' => 'Preveri vreme © Lauco Experience'] as $locale => $value) {
            file_put_contents(
                $this->directory . '/defaults/site.' . $locale . '.json',
                json_encode(['safety.weather' => $value], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            );
        }
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*/*') ?: [] as $path) {
            unlink($path);
        }
        rmdir($this->directory . '/defaults');
        rmdir($this->directory . '/runtime');
        rmdir($this->directory);
    }

    public function testItTranslatesAcrossTemplateWhitespaceWithoutTouchingScripts(): void
    {
        $repository = new SiteCatalogRepository($this->directory . '/defaults', $this->directory . '/runtime');
        $localizer = new HtmlLocalizer($repository);
        $html = "<html lang=\"it\"><body><p>Controlla   il\n meteo &copy; Lauco&nbsp;Experience</p><script>Controlla il meteo © Lauco Experience</script></body></html>";

        $localized = $localizer->localize($html, 'en');

        self::assertStringContainsString('<html lang="en">', $localized);
        self::assertStringContainsString('<p>Check the weather © Lauco Experience</p>', $localized);
        self::assertStringContainsString('<script>Controlla il meteo © Lauco Experience</script>', $localized);
    }

    public function testItalianRuntimeCatalogIsAuthoritative(): void
    {
        $repository = new SiteCatalogRepository($this->directory . '/defaults', $this->directory . '/runtime');
        $repository->save('it', ['safety.weather' => 'Verifica il meteo © Lauco Experience']);

        $localized = (new HtmlLocalizer($repository))->localize('<html lang="en"><p>Controlla il meteo © Lauco Experience</p></html>', 'it');

        self::assertStringContainsString('<html lang="it">', $localized);
        self::assertStringContainsString('Verifica il meteo', $localized);
    }
}
