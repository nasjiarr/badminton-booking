<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaManifestTest extends TestCase
{
    public function test_manifest_is_accessible_and_contains_design_tokens(): void
    {
        $response = $this->get('/manifest.json');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/manifest+json');

        $manifest = json_decode($response->getContent(), true);

        $this->assertIsArray($manifest);
        $this->assertStringContainsString('Smash Arena', $manifest['name']);
        $this->assertEquals('Smash Arena', $manifest['short_name']);
        $this->assertEquals('#0A0F1D', strtoupper($manifest['theme_color']));
        $this->assertEquals('#0A0F1D', strtoupper($manifest['background_color']));
        $this->assertEquals('standalone', $manifest['display']);

        $iconSizes = array_column($manifest['icons'] ?? [], 'sizes');
        $this->assertContains('192x192', $iconSizes);
        $this->assertContains('512x512', $iconSizes);
    }

    public function test_pwa_icons_and_favicon_files_exist_on_disk(): void
    {
        $this->assertFileExists(public_path('favicon.svg'));
        $this->assertFileExists(public_path('icons/icon-192x192.png'));
        $this->assertFileExists(public_path('icons/icon-512x512.png'));
        $this->assertFileExists(public_path('apple-touch-icon.png'));

        // Verify valid image content
        $this->assertGreaterThan(500, filesize(public_path('favicon.svg')));
        $this->assertGreaterThan(500, filesize(public_path('icons/icon-192x192.png')));
        $this->assertGreaterThan(500, filesize(public_path('icons/icon-512x512.png')));
    }

    public function test_service_worker_endpoint_is_accessible(): void
    {
        $response = $this->get('/sw.js');
        $response->assertOk();
        $this->assertStringContainsString('javascript', $response->headers->get('Content-Type'));
        $this->assertNotEmpty($response->getContent());
    }

    public function test_app_blade_renders_pwa_meta_tags(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('<meta name="theme-color" content="#0A0F1D">', $content);
        $this->assertStringContainsString('<meta name="mobile-web-app-capable" content="yes">', $content);
        $this->assertStringContainsString('rel="manifest"', $content);
        $this->assertStringContainsString('rel="icon" type="image/svg+xml" href="/favicon.svg"', $content);
        $this->assertStringContainsString('rel="apple-touch-icon"', $content);
    }
}

