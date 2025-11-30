<?php

namespace Md4Ai;

/**
 * Core class - handles initialization and AI bot detection
 */
class Md4Ai_Core {

	/**
	 * List of AI bots to detect
	 *
	 * Claude https://support.claude.com/en/articles/8896518-does-anthropic-crawl-data-from-the-web-and-how-can-site-owners-block-the-crawler
	 * ChatGPT https://platform.openai.com/docs/bots/overview-of-openai-crawlers
	 * Perplexity https://docs.perplexity.ai/guides/bots
	 * Google https://developers.google.com/crawling/docs/crawlers-fetchers/google-common-crawlers
	 */
	private array $ai_useragents = [
		'oai-searchbot', // OAI-SearchBot/1.0; +https://openai.com/searchbot
		'gptbot', // GPTBot/1.0 (+https://openai.com/gptbot)
		'chatgpt-user', // ChatGPT-User/1.0; +https://openai.com/bot
		'mistralai-user', // MistralAI-User/1.0; +https://docs.mistral.ai/robots
		'gptbot', // GPTBot/1.1; +https://openai.com/gptbot
		'deepseekbot', // DeepSeekBot/1.0; +http://www.deepseek.com/bot
		'chatglm', // ChatGLM/1.0; +https://chatglm.com/bot
		'claudebot', // ChatGLM-Spider # https://darkvisitors.com/agents/chatglm-spider
		'claude-user', // Claude-User/1.0; +https://openai.com/bot
		'anthropic-ai', // anthropic-ai/1.0 (+https://www.anthropic.com/bot)
		'meta-externalagent', // meta-externalagent/1.1 (+https://developers.facebook.com/docs/sharing/webmasters/crawler)
		'ccbot', // CCBot/2.0 (https://commoncrawl.org/faq/)
		'perplexitybot', // PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)
		'perplexity-user', // Perplexity‑User/1.0; +https://perplexity.ai/perplexityuser
		'google-extended', // Google-Extended/1.0; +http://www.google.com/bot.html
		'applebot-extended', // Applebot-Extended/1.0; +http://www.apple.com/bot.html
		'cohere-training-data-crawler', // cohere-training-data-crawler/1.0; +http://www.cohere.ai/bot.html
		'cohere-ai' // cohere-ai/1.0; +http://www.cohere.ai/bot.html
	];

	/**
	 * Default list of LLM domains
	 *
	 * @var array
	 */
	private array $default_llm_domains = [
		'chatgpt.com',
		'openai.com',
		'claude.ai',
		'gemini.google.com',
		'perplexity.ai',
		'copilot.microsoft.com',
		'm365.cloud.microsoft', // Microsoft 365 Copilot
		'grok.com',
		'you.com',
		'phind.com',
		'poe.com',
		'character.ai',
		'huggingface.co',
		'deepseek.com',
		'mistral.ai',
		'cohere.ai',
		'bard.google.com'
	];

	private array $ai_bots;
	/**
	 * @var Md4Ai_Cache
	 */
	private Md4Ai_Cache $cache;
	/**
	 * @var Md4Ai_Markdown
	 */
	private Md4Ai_Markdown $markdown;

	/**
	 * @var mixed|null
	 */
	private $llm_domains;

	/**
	 * @var array
	 */
	private $options;

	public function __construct() {
		$this->ai_bots = $this->setup_ai_useragents();
		$this->llm_domains = $this->setup_llm_domains();

		$this->options = get_option('md4ai_options');

		// Initialize sub-components
		$this->cache = new Md4Ai_Cache();
		$this->markdown = new Md4Ai_Markdown($this->cache);

		// Initialize REST API
		new Md4Ai_RestAPI($this->options, $this->markdown);

		// Initialize admin stuff
		new Md4Ai_Admin($this->cache, $this->markdown);

		// Hook into template redirect
		add_action('template_redirect', [$this, 'handle_requests'], 1);

		// Add rewrite rule for llms.txt
		add_action('init', [$this, 'add_llmstxt_rewrite']);
		add_filter('query_vars', [$this, 'add_llmstxt_query_var']);
	}

	/**
	 * Set up AI user agents
	 */
	private function setup_ai_useragents() {
		/**
		 * Filters the list of AI user agents
		 *
		 * @param array $ai_useragents The list of AI user agents
		 */
		return apply_filters('md4ai_ai_useragents', $this->ai_useragents);
	}

	/**
	 * Set up LLM domains
	 */
	private function setup_llm_domains() {
		/**
		 * Filters the list of LLM domains
		 *
		 * @param array $llm_domains The list of LLM domains
		 */
		return apply_filters('md4ai_llm_domains', $this->default_llm_domains);
	}

	/**
	 * Checks if the user agent matches an AI bot
	 */
	public function is_ai_bot(): bool {
		$user_agent = Md4Ai_Utils::get_user_agent();

		if (empty($user_agent)) {
			return false;
		}

		foreach ($this->ai_bots as $bot) {
			if (strpos($user_agent, strtolower($bot)) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Analyzes the referrer and user agent to determine the source of the request
	 */
	private function get_referrer_insights() {
		$referrer_url = Md4Ai_Utils::get_referrer();
		$user_agent = Md4Ai_Utils::get_user_agent();

		$source = 'Unknown';
		$search_terms = '';
		$additional_info = [];

		// === REFERER ANALYSIS (if present) ===
		if (!empty($referrer_url)) {
			$parsed_url = wp_parse_url($referrer_url);
			$referrer_host = $parsed_url['host'] ?? '';

			if (!empty($referrer_host)) {
				// Check LLM domains
				foreach ($this->default_llm_domains as $domain) {
					if (strpos($referrer_host, $domain) !== false) {
						$source = 'LLM: ' . $domain;
						break;
					}
				}

				// Common search engines
				if ($source === 'Unknown') {
					$search_engines = [
						'google.' => 'Search: Google',
						'bing.com' => 'Search: Bing',
						'duckduckgo.com' => 'Search: DuckDuckGo',
						'yahoo.com' => 'Search: Yahoo',
						'yandex.' => 'Search: Yandex',
						'baidu.com' => 'Search: Baidu',
						'ecosia.org' => 'Search: Ecosia',
						'startpage.com' => 'Search: Startpage',
					];

					foreach ($search_engines as $domain => $label) {
						if (strpos($referrer_host, $domain) !== false) {
							$source = $label;
							break;
						}
					}
				}

				// Extract search terms from query string
				$query = $parsed_url['query'] ?? '';
				if (!empty($query)) {
					parse_str($query, $query_params);

					// Common search term parameters
					$search_param_keys = ['q', 'p', 'query', 'search', 'term', 'text', 's', 'qs'];

					foreach ($search_param_keys as $key) {
						if (!empty($query_params[$key])) {
							$search_terms = sanitize_text_field(urldecode($query_params[$key]));
							break;
						}
					}
				}
			}
		}

		// === USER AGENT ANALYSIS (fallback for LLM without referer) ===
		if (!empty($user_agent) && $source === 'Unknown') {
			$user_agent_lower = strtolower($user_agent);

			foreach ($this->ai_useragents as $pattern) {
				if (strpos($user_agent_lower, $pattern) !== false) {
					$source = 'LLM Bot: ' . $pattern;
					break;
				}
			}
		}

		// === DATA STORAGE ===
		if ($source !== 'Unknown') {
			Md4Ai_Utils::store_visitor_data($source, $search_terms);
		}

		return [
			'source' => $source,
			'search_terms' => $search_terms,
			'referrer' => $referrer_url
		];
	}

	/**
	 * Add rewrite rule for llms.txt
	 */
	public function add_llmstxt_rewrite(): void {
		add_rewrite_rule('^llms\.txt$', 'index.php?md4ai_llmstxt=1', 'top');
	}

	/**
	 * Add query var for llms.txt
	 * Utilizes the add_query_var filter
	 */
	public function add_llmstxt_query_var($vars) {
		$vars[] = 'md4ai_llmstxt';
		$vars[] = 'md4ai_md';
		return $vars;
	}

	/**
	 * Handles all requests (llms.txt or markdown for AI bots)
	 */
	public function handle_requests() {
		if (is_admin()) {
			return;
		}

		// Check if requesting llms.txt
		if (get_query_var('md4ai_llmstxt')) {
			$this->serve_llmstxt();
			return;
		}

		// Check if it's an AI bot or a request for markdown
		if (get_query_var('md4ai_md') || $this->is_ai_bot()) {
			$this->serve_markdown_to_bots();
		} else {
			$this->get_referrer_insights();
		}
	}

	/**
	 * Serves the llms.txt content
	 */
	private function serve_llmstxt() {
		$llms_content = Md4Ai_Utils::get_llms_txt_content();

		// If no content is set, provide a default message
		if (empty($llms_content)) {
			$llms_content = $this->markdown->generate_default_llmstxt();
		}

		// Set appropriate headers
		header('Content-Type: text/plain; charset=utf-8');
		header('X-Robots-Tag: noindex, nofollow');
		header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

		echo esc_textarea($llms_content);

		if ($this->is_ai_bot()) {
			Md4Ai_Utils::log_request( 0, $this->ai_bots );
		}
		exit;
	}

	/**
	 * Serves the content in Markdown to AI bots
	 */
	private function serve_markdown_to_bots() {
		// Get the current post
		global $post;

		if ((!$post || !is_singular()) && !is_home()) {
			return;
		}

		$markdown = false;
		$from_cache = false;

		// Try to get from cache first
		if ($this->cache->is_cache_valid($post->ID, $post->post_modified)) {
			$markdown = $this->cache->read_from_cache($post->ID);
			$from_cache = true;
		}

		// If no valid cache, get markdown and save to cache
		if ($markdown === false) {
			$markdown = $this->markdown->get_post_markdown($post);
			$this->cache->write_to_cache($post->ID, $markdown);
			$from_cache = false;
		}

		// Set headers and serve the content
		header('Content-Type: text/markdown; charset=utf-8');
		header('X-Robots-Tag: noindex, nofollow');
		header('X-Cache: ' . ($from_cache ? 'HIT' : 'MISS'));
		echo esc_textarea($markdown);

		// If is a bot log the request
		if ($this->is_ai_bot()) {
			Md4Ai_Utils::log_request( $post->ID,  $this->ai_bots );
		}
		exit;
	}
}
