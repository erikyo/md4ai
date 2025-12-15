<?php

namespace Md4Ai;

/**
 * Core class - handles initialization
 */
class Md4Ai_Core {

	/**
	 * The Md4Ai_Cache instance
	 *
	 * @var Md4Ai_Cache
	 */
	private Md4Ai_Cache $cache;
	/**
	 * The Md4Ai_Markdown instance
	 *
	 * @var Md4Ai_Markdown
	 */
	private Md4Ai_Markdown $markdown;

	/**
	 * The options
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Md4Ai_Core constructor.
	 */
	public function __construct() {
		$this->options = get_option( MD4AI_OPTION ) ?: array();

		// Initialize sub-components
		$this->cache    = new Md4Ai_Cache();
		$this->markdown = new Md4Ai_Markdown( $this->cache );

		// Initialize Access Handler
		new Md4Ai_Access_Handler( $this->cache, $this->markdown );

		// Initialize REST API
		new Md4Ai_RestAPI( $this->options, $this->markdown );

		// Initialize admin stuff
		new Md4Ai_Admin( $this->cache, $this->markdown );
	}
}
