<?php

namespace Md4Ai\Tests\PhpUnit\Tests;

use Md4Ai\Md4Ai_Access_Handler;
use Md4Ai\Md4Ai_Cache;
use Md4Ai\Md4Ai_Markdown;

class Md4Ai_Access_HandlerTest extends \WP_UnitTestCase {

	private $access_handler;
	private $cache;
	private $markdown;
	private $original_server;

	public function setUp(): void {
		parent::setUp();
		$this->original_server = $_SERVER;
		$this->cache = new Md4Ai_Cache();
		$this->markdown = new Md4Ai_Markdown( $this->cache );
		$this->access_handler = new Md4Ai_Access_Handler( $this->cache, $this->markdown );
	}

	public function tearDown(): void {
		$_SERVER = $this->original_server;
		parent::tearDown();
	}

	public function test_is_ai_bot_detects_bots() {
		$bots = [
			'GPTBot/1.0 (+https://openai.com/gptbot)',
			'Claude-User/1.0; +https://openai.com/bot',
			'PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)',
		];

		foreach ( $bots as $bot_agent ) {
			$_SERVER['HTTP_USER_AGENT'] = $bot_agent;
			$this->assertTrue( $this->access_handler->is_ai_bot(), "Failed to detect bot: $bot_agent" );
		}
	}

	public function test_is_ai_bot_ignores_browsers() {
		$browsers = [
			'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
		];

		foreach ( $browsers as $browser_agent ) {
			$_SERVER['HTTP_USER_AGENT'] = $browser_agent;
			$this->assertFalse( $this->access_handler->is_ai_bot(), "Incorrectly detected browser as bot: $browser_agent" );
		}
	}
}
