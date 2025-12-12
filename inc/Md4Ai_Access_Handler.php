<?php

namespace Md4Ai;

/**
 * Access Handler class - handles bot detection, request serving, and insights
 */
class Md4Ai_Access_Handler {

	/**
	 * List of AI bots to detect
	 *
	 * Claude https://support.claude.com/en/articles/8896518-does-anthropic-crawl-data-from-the-web-and-how-can-site-owners-block-the-crawler
	 * ChatGPT https://platform.openai.com/docs/bots/overview-of-openai-crawlers
	 * Perplexity https://docs.perplexity.ai/guides/bots
	 * Google https://developers.google.com/crawling/docs/crawlers-fetchers/google-common-crawlers
	 */
	private array $ai_useragents = array(
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
		'amazonbot', // Amazonbot/0.1; +https://developer.amazon.com/support/amazonbot
		'amzn-user', // Amzn-User/0.1; +https://developer.amazon.com/support/amazonbot
		'ccbot', // CCCBot/2.0 (https://commoncrawl.org/faq/)
		'perplexitybot', // PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)
		'perplexity-user', // Perplexity‑User/1.0; +https://perplexity.ai/perplexityuser
		'google-extended', // Google-Extended/1.0; +http://www.google.com/bot.html
		'applebot-extended', // Applebot-Extended/1.0; +http://www.apple.com/bot.html
		'cohere-training-data-crawler', // cohere-training-data-crawler/1.0; +http://www.cohere.ai/bot.html
		'cohere-ai', // cohere-ai/1.0; +http://www.cohere.ai/bot.html
	);

	/**
	 * Default list of LLM domains
	 *
	 * @var array
	 */
	private array $default_llm_domains = array(
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
		'bard.google.com',
	);

	private array $default_search_engines = array(
		'google.'        => 'Search: Google',
		'bing.com'       => 'Search: Bing',
		'duckduckgo.com' => 'Search: DuckDuckGo',
		'yahoo.com'      => 'Search: Yahoo',
		'yandex.'        => 'Search: Yandex',
		'baidu.com'      => 'Search: Baidu',
		'ecosia.org'     => 'Search: Ecosia',
		'startpage.com'  => 'Search: Startpage',
	);

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
	 * @var mixed|null
	 */
	private $search_engines;

	public function __construct( Md4Ai_Cache $cache, Md4Ai_Markdown $markdown ) {
		$this->cache    = $cache;
		$this->markdown = $markdown;

		$this->ai_bots        = $this->setup_ai_useragents();
		$this->llm_domains    = $this->setup_llm_domains();
		$this->search_engines = $this->setup_search_engines();

		// Hook into template redirect
		add_action( 'template_redirect', array( $this, 'handle_requests' ), 1 );

		// Log insights on wp_head to avoid duplicates on redirects
		add_action( 'wp_head', array( $this, 'log_insights' ) );

		// Add rewrite rule for llms.txt
		add_action( 'init', array( $this, 'add_llmstxt_rewrite' ) );
		add_filter( 'query_vars', array( $this, 'add_llmstxt_query_var' ) );
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
		return apply_filters( 'md4ai_ai_useragents', $this->ai_useragents );
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
		return apply_filters( 'md4ai_llm_domains', $this->default_llm_domains );
	}

	/**
	 * Set up search engines
	 */
	private function setup_search_engines() {
		/**
		 * Filters the list of search engines
		 *
		 * @param array $search_engines The list of search engines
		 */
		return apply_filters( 'md4ai_search_engines', $this->default_search_engines );
	}

	/**
	 * Checks if the user agent matches an AI bot
	 */
	public function is_ai_bot(): bool {
		$user_agent = strtolower( Md4Ai_Utils::get_user_agent() );

		if ( empty( $user_agent ) ) {
			return false;
		}

		foreach ( $this->ai_bots as $bot ) {
			if ( strpos( $user_agent, $bot ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Analyzes the referrer and user agent to determine the source of the request
	 *
	 * @return array The source, medium, and search terms
	 */
	private function get_referrer_insights(): array {
		$referrer_url = Md4Ai_Utils::get_referrer();

		// Get the current URL the user is visiting (to check for UTMs)
		$current_url = sanitize_text_field(
			wp_unslash( $_SERVER['REQUEST_URI'] ?? '' )
		);

		$source       = 'Direct/Unknown';
		$search_terms = '';
		$medium       = 'unknown';

		// 1. CHECK FOR UTM TAGS (The most reliable way to get terms for Ads)
		// We check the *Current URL*, not the referrer
		$parsed_current = wp_parse_url( $current_url );
		if ( ! empty( $parsed_current['query'] ) ) {
			parse_str( $parsed_current['query'], $current_params );

			// Check for utm_term or utm_content
			if ( ! empty( $current_params['utm_term'] ) ) {
				$search_terms = sanitize_text_field( urldecode( $current_params['utm_term'] ) );
				$source       = $current_params['utm_source'] ?? 'Paid Source';
				$medium       = 'cpc'; // Cost per click
			}
		}

		// 2. ANALYZE REFERRER (If UTMs didn't provide the answer)
		if ( ! empty( $referrer_url ) && empty( $search_terms ) ) {
			$parsed_ref    = wp_parse_url( $referrer_url );
			$referrer_host = $parsed_ref['host'] ?? '';

			if ( ! empty( $referrer_host ) ) {

				// A. Check LLM domains
				foreach ( $this->llm_domains as $domain ) {
					if ( strpos( $referrer_host, $domain ) !== false ) {
						$source = 'LLM: ' . $domain;
						$medium = 'ai_referral';
						break;
					}
				}

				// B. Common search engines
				if ( $source === 'Direct/Unknown' ) {
					foreach ( $this->search_engines as $domain => $label ) {
						if ( strpos( $referrer_host, $domain ) !== false ) {
							$source = $label;
							$medium = 'organic'; // Organic search

							// Try to parse query (Only works for non-secure engines or very old links)
							// We keep this just in case, but expect it to be empty for Google.
							$query = $parsed_ref['query'] ?? '';
							if ( ! empty( $query ) ) {
								parse_str( $query, $query_params );
								$search_param_keys = array( 'q', 'p', 'query', 'search', 's' );
								foreach ( $search_param_keys as $key ) {
									if ( ! empty( $query_params[ $key ] ) ) {
										$search_terms = sanitize_text_field( urldecode( $query_params[ $key ] ) );
										break;
									}
								}
							}
							break;
						}
					}
				}
			}
		}

		// 3. FALLBACK: IF ORGANIC GOOGLE & NO TERMS
		// We record the Landing Page URL. This is your "Search Intent" proxy.
		if ( empty( $search_terms ) ) {
			$search_terms = '(Not Provided) - Landed on: ' . $parsed_current['path'];
		}

		// === DATA STORAGE ===
		if ( $source !== 'Direct/Unknown' ) {
			Md4Ai_Utils::store_visitor_data( $source, $search_terms );
		}

		return array(
			'source'       => $source,
			'medium'       => $medium,
			'search_terms' => $search_terms,
			'referrer'     => $referrer_url,
		);
	}

	/**
	 * Add rewrite rule for llms.txt
	 */
	public function add_llmstxt_rewrite(): void {
		add_rewrite_rule( '^llms\.txt$', 'index.php?md4ai_llmstxt=1', 'top' );
	}

	/**
	 * Add query var for llms.txt
	 * Utilizes the add_query_var filter
	 */
	public function add_llmstxt_query_var( $vars ) {
		$vars[] = 'md4ai_llmstxt';
		$vars[] = 'md4ai_md';
		return $vars;
	}

	/**
	 * Handles all requests (llms.txt or markdown for AI bots)
	 */
	public function handle_requests() {
		if ( is_admin() || defined( 'REST_REQUEST' ) || wp_doing_ajax() ) {
			return;
		}

		global $post;
		if ( ( ! $post || ! is_singular() ) && ! is_home() ) {
			return;
		}

		// Check if requesting llms.txt
		if ( get_query_var( 'md4ai_llmstxt' ) ) {
			$this->serve_llmstxt();
			return;
		}

		// Check if it's an AI bot or a request for Markdown
		if ( get_query_var( 'md4ai_md' ) || $this->is_ai_bot() ) {
			$this->serve_markdown_to_bots();
		}
	}

	/**
	 * Logs visitor insights
	 * Hooked to wp_head to ensure we only log when the page is actually rendered (avoiding redirects)
	 */
	public function log_insights() {
		if ( is_admin() || defined( 'REST_REQUEST' ) || wp_doing_ajax() ) {
			return;
		}

		global $post;
		if ( ( ! $post || ! is_singular() ) && ! is_home() ) {
			return;
		}

		$this->get_referrer_insights();
	}

	/**
	 * Serves the llms.txt content
	 */
	private function serve_llmstxt() {
		$llms_content = Md4Ai_Utils::get_llms_txt_content();

		// If no content is set, provide a default message
		if ( empty( $llms_content ) ) {
			$llms_content = $this->markdown->generate_default_llmstxt();
		}

		// Set appropriate headers
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex, nofollow' );
		header( 'Cache-Control: public, max-age=3600' ); // Cache for 1 hour

		echo esc_textarea( $llms_content );

		if ( ! is_user_logged_in() ) {
			Md4Ai_Utils::log_request( 0, $this->ai_bots );
		}

		exit;
	}

	/**
	 * Wraps markdown content in HTML for better compatibility with AI bot browsers
	 *
	 * @param string $markdown The markdown content
	 * @param string $title The page title
	 * @return string HTML-wrapped markdown
	 */
	private function wrap_markdown_in_html( $markdown, $title = '' ) {
		// If no title provided, try to extract from markdown first line
		if ( empty( $title ) ) {
			$lines = explode( "\n", $markdown );
			foreach ( $lines as $line ) {
				$line = trim( $line );
				// Look for first markdown heading (# Title)
				if ( preg_match( '/^#\s+(.+)$/', $line, $matches ) ) {
					$title = $matches[1];
					break;
				}
			}
		}

		// Fallback title
		if ( empty( $title ) ) {
			$title = 'Content for AI';
		}

		// Escape title for HTML but preserve special characters
		$html_title = htmlspecialchars( $title, ENT_QUOTES, 'UTF-8' );

		// Build HTML with markdown in <pre> tag
		$html  = '<!DOCTYPE html>' . "\n";
		$html .= '<html lang="en">' . "\n";
		$html .= '<head>' . "\n";
		$html .= '    <meta charset="UTF-8">' . "\n";
		$html .= '    <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
		$html .= '    <title>' . $html_title . '</title>' . "\n";
		$html .= '</head>' . "\n";
		$html .= '<body>' . "\n";
		$html .= '<pre id="markdown-content">' . "\n";
		// Don't use htmlspecialchars here - we want to preserve the markdown as-is
		$html .= $markdown;
		$html .= "\n" . '</pre>' . "\n";
		$html .= '</body>' . "\n";
		$html .= '</html>';

		return $html;
	}

	/**
	 * Serves the content in Markdown to AI bots
	 */
	private function serve_markdown_to_bots() {
		// Get the current post
		global $post;

		// Special handling for homepage
		if ( is_home() || is_front_page() ) {
			$this->serve_homepage_markdown();
			return;
		}

		$markdown   = false;
		$from_cache = false;

		// Try to get from cache first
		if ( $this->cache->is_cache_valid( $post->ID, $post->post_modified ) ) {
			$markdown   = $this->cache->read_from_cache( $post->ID );
			$from_cache = true;
		}

		// If no valid cache, get Markdown and save to cache
		if ( $markdown === false ) {
			$markdown = $this->markdown->get_post_markdown( $post );
			$this->cache->write_to_cache( $post->ID, $markdown );
			$from_cache = false;
		}

		// Get output format setting (default to 'html' for backward compatibility)
		$options       = get_option( MD4AI_OPTION );
		$output_format = $options['output_format'] ?? 'html';

		// Serve based on output format setting
		if ( $output_format === 'html' ) {
			// HTML Wrapped format
			$post_title   = html_entity_decode( $post->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$html_content = $this->wrap_markdown_in_html( $markdown, $post_title );

			header( 'Content-Type: text/html; charset=utf-8' );
			header( 'X-Robots-Tag: noindex, nofollow' );
			header( 'X-Cache: ' . ( $from_cache ? 'HIT' : 'MISS' ) );
			echo $html_content;
		} else {
			// Pure Markdown format
			header( 'Content-Type: text/markdown; charset=utf-8' );
			header( 'X-Robots-Tag: noindex, nofollow' );
			header( 'X-Cache: ' . ( $from_cache ? 'HIT' : 'MISS' ) );
			echo $markdown;
		}

		// If is a bot log the request
		if ( $this->is_ai_bot() ) {
			Md4Ai_Utils::log_request( $post->ID, $this->ai_bots );
		}
		exit;
	}

	/**
	 * Serves Markdown content for the homepage/front page
	 */
	private function serve_homepage_markdown() {
		// Get output format setting
		$options       = get_option( MD4AI_OPTION );
		$output_format = $options['output_format'] ?? 'html';

		// Check if homepage is a static page
		$page_on_front = get_option( 'page_on_front' );

		if ( $page_on_front && $page_on_front > 0 ) {
			// Homepage is a static page - serve its content
			$front_page = get_post( $page_on_front );

			if ( $front_page && $front_page->post_status === 'publish' ) {
				// Use the existing markdown generation for this page
				$markdown = $this->markdown->get_post_markdown( $front_page );

				// Serve based on output format setting
				if ( $output_format === 'html' ) {
					$page_title   = html_entity_decode( $front_page->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
					$html_content = $this->wrap_markdown_in_html( $markdown, $page_title );

					header( 'Content-Type: text/html; charset=utf-8' );
					header( 'X-Robots-Tag: noindex, nofollow' );
					header( 'X-Cache: MISS' );
					echo $html_content;
				} else {
					header( 'Content-Type: text/markdown; charset=utf-8' );
					header( 'X-Robots-Tag: noindex, nofollow' );
					header( 'X-Cache: MISS' );
					echo $markdown;
				}

				// Log the request
				if ( $this->is_ai_bot() ) {
					Md4Ai_Utils::log_request( $page_on_front, $this->ai_bots );
				}
				exit;
			}
		}

		// Homepage is blog or page not found - generate generic homepage content
		$site_title       = get_bloginfo( 'name' );
		$site_description = get_bloginfo( 'description' );
		$site_url         = home_url();

		// Build homepage Markdown
		$markdown = "---\n";
		// Decode entities for homepage title too
		$clean_title = html_entity_decode( $site_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$markdown   .= '# ' . $clean_title . "\n\n";
		$markdown   .= '**URL:** ' . esc_url( $site_url ) . "\n";
		$markdown   .= "**Type:** Homepage (Blog)\n";

		if ( ! empty( $site_description ) ) {
			$clean_description = html_entity_decode( $site_description, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$markdown         .= '**Description:** ' . $clean_description . "\n";
		}

		$markdown .= "---\n\n";

		if ( ! empty( $site_description ) ) {
			$clean_description = html_entity_decode( $site_description, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$markdown         .= '> ' . $clean_description . "\n\n";
		}

		// Add main pages
		$pages = get_pages(
			array(
				'post_status' => 'publish',
				'number'      => 10,
				'sort_column' => 'menu_order',
			)
		);

		if ( ! empty( $pages ) ) {
			$markdown .= "## Main Pages\n\n";
			foreach ( $pages as $page ) {
				$page_url     = get_permalink( $page->ID );
				$page_title   = html_entity_decode( $page->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				$page_excerpt = wp_trim_words( wp_strip_all_tags( $page->post_excerpt ?: $page->post_content ), 20 );
				$page_excerpt = html_entity_decode( $page_excerpt, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

				$markdown .= "### [{$page_title}]({$page_url})\n\n";
				if ( ! empty( $page_excerpt ) ) {
					$markdown .= "{$page_excerpt}\n\n";
				}
			}
		}

		// Add navigation links
		$markdown .= $this->markdown->generate_website_links(
			array(
				'include_categories' => false,
				'include_navigation' => true,
				'include_tags'       => false,
				'include_footer'     => true,
			)
		);

		// Serve based on output format setting
		if ( $output_format === 'html' ) {
			$html_content = $this->wrap_markdown_in_html( $markdown, $clean_title );

			header( 'Content-Type: text/html; charset=utf-8' );
			header( 'X-Robots-Tag: noindex, nofollow' );
			header( 'X-Cache: MISS' );
			echo $html_content;
		} else {
			header( 'Content-Type: text/markdown; charset=utf-8' );
			header( 'X-Robots-Tag: noindex, nofollow' );
			header( 'X-Cache: MISS' );
			echo $markdown;
		}

		// Log the request
		if ( $this->is_ai_bot() ) {
			Md4Ai_Utils::log_request( 0, $this->ai_bots );
		}
		exit;
	}
}
