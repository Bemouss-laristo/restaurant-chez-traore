<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le site s'installe comme une application (téléphone et ordinateur).
 */
class InstallableAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_manifest_is_valid_and_its_icons_exist(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('manifest.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Chez Traoré', $manifest['name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/?source=app', $manifest['start_url']);

        $sizes = array_column($manifest['icons'], 'sizes');
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
        $this->assertContains('maskable', array_column($manifest['icons'], 'purpose'));

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_the_service_worker_and_offline_page_exist(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('apple-touch-icon.png'));
        $this->assertGreaterThan(0, filesize(public_path('favicon.ico')));

        // Les pages (ventes, caisse…) ne doivent jamais être mises en cache.
        $sw = (string) file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString("request.method !== 'GET'", $sw);
        $this->assertStringContainsString('fetch(request).catch(() => caches.match(OFFLINE_URL))', $sw);
    }

    public function test_every_layout_links_the_manifest_and_offers_install(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee("Installer l'application", false);

        $this->get(route('login'))->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee("Installer l'application sur cet appareil", false);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee("Installer l'application", false);
    }

    public function test_the_installed_app_sends_the_team_to_their_space(): void
    {
        $this->get('/?source=app')->assertOk();

        $this->actingAs(User::factory()->create())
            ->get('/?source=app')
            ->assertRedirect(route('dashboard'));
    }
}
