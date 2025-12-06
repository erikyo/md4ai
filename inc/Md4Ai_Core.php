<?php

namespace Md4Ai;

/**
 * Core class - handles initialization
 */
class Md4Ai_Core {

	/**
	 * @var Md4Ai_Cache
	 */
	private Md4Ai_Cache $cache;
	/**
	 * @var Md4Ai_Markdown
	 */
	private Md4Ai_Markdown $markdown;

	/**
	 * @var array
	 */
	private $options;

	public function __construct() {
		$this->options = get_option(MD4AI_OPTION) ?: [];

		// Initialize sub-components
		$this->cache = new Md4Ai_Cache();
		$this->markdown = new Md4Ai_Markdown($this->cache);

		// Initialize Access Handler
		new Md4Ai_Access_Handler($this->cache, $this->markdown);

		// Initialize REST API
		new Md4Ai_RestAPI($this->options, $this->markdown);

		// Initialize admin stuff
		new Md4Ai_Admin($this->cache, $this->markdown);
	}
}
