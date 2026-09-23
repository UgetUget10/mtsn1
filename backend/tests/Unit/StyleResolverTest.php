<?php

namespace Tests\Unit;

use App\Support\Blocks\StyleResolver;
use PHPUnit\Framework\TestCase;

class StyleResolverTest extends TestCase
{
    public function test_sanitize_keeps_known_properties(): void
    {
        $result = StyleResolver::sanitize([
            'base' => ['width' => 6, 'paddingY' => 'lg', 'background' => 'brand-light', 'textAlign' => 'center'],
        ]);

        $this->assertSame(
            ['base' => ['width' => 6, 'paddingY' => 'lg', 'background' => 'brand-light', 'textAlign' => 'center']],
            $result,
        );
    }

    public function test_sanitize_accepts_hex_colors(): void
    {
        $result = StyleResolver::sanitize(['base' => ['background' => '#ff00aa']]);

        $this->assertSame(['base' => ['background' => '#ff00aa']], $result);
    }

    public function test_sanitize_rejects_unknown_breakpoint(): void
    {
        $result = StyleResolver::sanitize(['huge' => ['width' => 6]]);

        $this->assertSame([], $result);
    }

    public function test_sanitize_rejects_unknown_property(): void
    {
        $result = StyleResolver::sanitize(['base' => ['fontSize' => '999px']]);

        $this->assertSame([], $result);
    }

    public function test_sanitize_rejects_invalid_width(): void
    {
        $result = StyleResolver::sanitize(['base' => ['width' => 7]]);

        $this->assertSame([], $result);
    }

    public function test_sanitize_rejects_css_injection_attempt_in_background(): void
    {
        $result = StyleResolver::sanitize([
            'base' => ['background' => 'red; } body { display:none'],
        ]);

        $this->assertSame([], $result);
    }

    public function test_sanitize_rejects_url_based_background(): void
    {
        $result = StyleResolver::sanitize([
            'base' => ['background' => 'url(javascript:alert(1))'],
        ]);

        $this->assertSame([], $result);
    }

    public function test_sanitize_handles_non_array_input(): void
    {
        $this->assertSame([], StyleResolver::sanitize(null));
        $this->assertSame([], StyleResolver::sanitize('not-an-array'));
    }

    public function test_sanitize_drops_empty_breakpoints(): void
    {
        $result = StyleResolver::sanitize(['base' => ['fontSize' => 'invalid'], 'md' => []]);

        $this->assertSame([], $result);
    }

    public function test_is_valid_color_accepts_short_and_long_hex(): void
    {
        $this->assertTrue(StyleResolver::isValidColor('#abc'));
        $this->assertTrue(StyleResolver::isValidColor('#aabbcc'));
        $this->assertFalse(StyleResolver::isValidColor('#abcd'));
        $this->assertFalse(StyleResolver::isValidColor('not-a-color'));
    }
}
