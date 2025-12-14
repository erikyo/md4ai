<?php

namespace Md4Ai\Tests\PhpUnit\Tests;

use PHPUnit\Framework\TestCase;

class Md4Ai_MarkdownTest extends \WP_UnitTestCase {

	private $markdown;
	private $cache;

	public function setUp(): void {
		parent::setUp();
		// load the plugin
		$this->cache = new \Md4Ai\Md4Ai_Cache();
		$this->markdown = new \Md4Ai\Md4Ai_Markdown( $this->cache );
	}

	public function test_convert_post_to_markdown() {
		$post_id = self::factory()->post->create( [
			'post_title'   => 'Test Post',
			'post_content' => '<h1>Header</h1><p>Content with <strong>bold</strong> text.</p>',
		] );
		$post = get_post( $post_id );

		$output = $this->markdown->convert_post_to_markdown( $post );

		$this->assertStringContainsString( '# Test Post', $output );
		$this->assertStringContainsString( '# Header', $output );
		$this->assertStringContainsString( '**bold**', $output );
		$this->assertStringContainsString( 'Date:', $output );
	}

	public function test_generate_default_llmstxt() {
		// Create some posts
		self::factory()->post->create( [ 'post_title' => 'Recent Post 1' ] );
		self::factory()->post->create( [ 'post_title' => 'Recent Post 2' ] );

		$output = $this->markdown->generate_default_llmstxt();

		$this->assertStringContainsString( '# ' . get_bloginfo( 'name' ), $output );
		$this->assertStringContainsString( 'Recent Post 1', $output );
		$this->assertStringContainsString( 'Recent Post 2', $output );
	}

	public function test_encoding_chars() {
		$post_id = self::factory()->post->create( [
			'post_title'   => 'Test &quot;Quotes&quot; &amp; &lt;Tags&gt;',
			'post_content' => '<p>Content with &mdash; em dash and &ndash; en dash.</p>',
		] );
		$post = get_post( $post_id );

		$output = $this->markdown->convert_post_to_markdown( $post );

		// Check title decoding
		$this->assertStringContainsString( '# Test "Quotes" & <Tags>', $output );
		// Check content decoding
		$this->assertStringContainsString( 'Content with — em dash and – en dash.', $output );
	}
}
