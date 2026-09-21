<?php

namespace Tests\Feature;

use App\Support\Oembed\AutoEmbed;
use PHPUnit\Framework\TestCase;

class AutoEmbedTest extends TestCase
{
    public function test_bare_youtube_paragraph_becomes_responsive_iframe(): void
    {
        $html = AutoEmbed::html('<p>Halo</p><p>https://youtu.be/dQw4w9WgXcQ</p><p>Selesai</p>');

        $this->assertStringContainsString('youtube-nocookie.com/embed/dQw4w9WgXcQ', $html);
        $this->assertStringContainsString('class="oembed"', $html);
        $this->assertStringContainsString('<p>Halo</p>', $html);
        $this->assertStringContainsString('<p>Selesai</p>', $html);
    }

    public function test_anchor_only_paragraph_is_normalised_then_embedded(): void
    {
        $html = AutoEmbed::html(
            '<p><a href="https://www.youtube.com/watch?v=abc123XYZ_-">https://www.youtube.com/watch?v=abc123XYZ_-</a></p>',
        );

        $this->assertStringContainsString('/embed/abc123XYZ_-', $html);
    }

    public function test_vimeo_and_spotify_are_recognised(): void
    {
        $this->assertStringContainsString(
            'player.vimeo.com/video/76979871',
            AutoEmbed::html('<p>https://vimeo.com/76979871</p>'),
        );
        $this->assertStringContainsString(
            'open.spotify.com/embed/track/',
            AutoEmbed::html('<p>https://open.spotify.com/track/4cOdK2wGLETKBW3PvgPWqT</p>'),
        );
    }

    public function test_url_inside_a_sentence_is_left_untouched(): void
    {
        $in = '<p>Tonton di https://youtu.be/dQw4w9WgXcQ ya</p>';

        $this->assertSame($in, AutoEmbed::html($in));
    }

    public function test_unknown_provider_paragraph_is_left_untouched(): void
    {
        $in = '<p>https://example.com/artikel/123</p>';

        $this->assertSame($in, AutoEmbed::html($in));
    }

    public function test_null_and_plain_text_pass_through(): void
    {
        $this->assertNull(AutoEmbed::html(null));
        $this->assertSame('<p>Tanpa tautan.</p>', AutoEmbed::html('<p>Tanpa tautan.</p>'));
    }

    public function test_twitter_becomes_blockquote_embed(): void
    {
        $html = AutoEmbed::html('<p>https://x.com/laravelphp/status/1234567890</p>');

        $this->assertStringContainsString('twitter-tweet', $html);
        $this->assertStringContainsString('platform.twitter.com/widgets.js', $html);
    }
}
