<?php

namespace Tests\Feature;

use Tests\TestCase;

class FrontendRenderingTest extends TestCase
{
    public function test_login_preloads_the_declared_font_without_a_late_font_swap(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/<link(?=[^>]*rel="preload")(?=[^>]*as="font")[^>]*>/i',
            $html,
            'The primary UI font must be requested before the first paint.',
        );
        $this->assertStringContainsString('font-display: optional', $html);
    }
}
