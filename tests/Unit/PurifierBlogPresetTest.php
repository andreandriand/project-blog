<?php

namespace Tests\Unit;

use Mews\Purifier\Facades\Purifier;
use Tests\TestCase;

/*
 * Unit coverage untuk preset 'blog' di config/purifier.php (OPTIMIZATION-REPORT.md item #2).
 * Preset whitelist: h2,h3,h4,p,br,strong,em,b,i,u,s,a,ul,ol,li,blockquote,code,pre,hr,img,figure,figcaption.
 *
 * Per-test sengaja focused (1 attack vector per test) supaya kalau ada regression,
 * pesan failure langsung menunjukkan vector spesifik mana yang lolos.
 */

class PurifierBlogPresetTest extends TestCase
{
    private function clean(string $html): string
    {
        return Purifier::clean($html, 'blog');
    }

    public function test_strips_script_tag(): void
    {
        $this->assertStringNotContainsString('<script', $this->clean('<p>ok</p><script>alert(1)</script>'));
    }

    public function test_strips_event_handler_attributes(): void
    {
        $output = $this->clean('<img src="x" onerror="alert(1)">');
        $this->assertStringNotContainsString('onerror', $output);
    }

    public function test_strips_javascript_url_in_href(): void
    {
        $output = $this->clean('<a href="javascript:alert(1)">click</a>');
        $this->assertStringNotContainsString('javascript:', $output);
    }

    public function test_strips_data_url_in_href(): void
    {
        $output = $this->clean('<a href="data:text/html,<script>alert(1)</script>">click</a>');
        $this->assertStringNotContainsString('data:', $output);
    }

    public function test_strips_iframe(): void
    {
        $output = $this->clean('<iframe src="https://evil.example/x"></iframe>');
        $this->assertStringNotContainsString('<iframe', $output);
    }

    public function test_strips_style_tag_and_inline_style(): void
    {
        $output = $this->clean('<style>body{display:none}</style><p style="color:red">x</p>');
        $this->assertStringNotContainsString('<style', $output);
        $this->assertStringNotContainsString('color:red', $output);
    }

    public function test_strips_object_and_embed(): void
    {
        $output = $this->clean('<object data="evil.swf"></object><embed src="x">');
        $this->assertStringNotContainsString('<object', $output);
        $this->assertStringNotContainsString('<embed', $output);
    }

    public function test_strips_form_inputs(): void
    {
        $output = $this->clean('<form action="evil.example"><input name="csrf"><button>x</button></form>');
        $this->assertStringNotContainsString('<form', $output);
        $this->assertStringNotContainsString('<input', $output);
    }

    public function test_preserves_safe_blog_tags(): void
    {
        $input = '<h2>Heading</h2><p>Para <strong>bold</strong> <em>italic</em></p>'
            .'<ul><li>item</li></ul><blockquote>quote</blockquote>'
            .'<pre><code>code block</code></pre>';

        $output = $this->clean($input);

        $this->assertStringContainsString('<h2>Heading</h2>', $output);
        $this->assertStringContainsString('<strong>bold</strong>', $output);
        $this->assertStringContainsString('<em>italic</em>', $output);
        $this->assertStringContainsString('<ul>', $output);
        $this->assertStringContainsString('<li>item</li>', $output);
        $this->assertStringContainsString('<blockquote>', $output);
        $this->assertStringContainsString('<code>', $output);
    }

    public function test_preserves_https_link(): void
    {
        $output = $this->clean('<a href="https://example.com">link</a>');
        $this->assertStringContainsString('href="https://example.com"', $output);
    }

    public function test_adds_rel_nofollow_to_external_links(): void
    {
        $output = $this->clean('<a href="https://example.com">link</a>');
        $this->assertMatchesRegularExpression('/rel="[^"]*nofollow[^"]*"/', $output);
    }

    public function test_preserves_image_with_safe_attributes(): void
    {
        $output = $this->clean('<img src="https://example.com/x.jpg" alt="desc" width="200" height="100">');
        $this->assertStringContainsString('src="https://example.com/x.jpg"', $output);
        $this->assertStringContainsString('alt="desc"', $output);
    }
}
