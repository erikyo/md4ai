<?php
/**
 * Markdown generation and conversion class
 */
class md4AI_Markdown {

	/**
	 * Post-meta key for custom Markdown
	 */
	private $meta_key = '_ai_md_custom_markdown';

	/**
	 * Cache instance
	 */
	private $cache;

	public function __construct($cache) {
		$this->cache = $cache;
	}

	/**
	 * Gets markdown for a post - checks custom meta first, then generates
	 */
	public function get_post_markdown($post) {
		// Get default args
		$args = apply_filters('md4ai-post-args', [
			'include_navigation' => true,
			'include_categories' => true,
			'include_tags' => true,
			'include_footer' => true
		]);

		// Check if custom markdown exists
		$args['custom_markdown'] = get_post_meta($post->ID, $this->meta_key, true);

		// Generate from post content
		return $this->convert_post_to_markdown($post, $args);
	}

	/**
	 * Converts a WordPress post to Markdown
	 */
	public function convert_post_to_markdown($post, $args = []) {
		$args = wp_parse_args($args, [
			'content' => false,
			'include_navigation' => false,
			'include_categories' => false,
			'include_tags' => false,
			'include_footer' => false,
		]);

		/* Filter the post */
		$post = apply_filters('md4ai_post', $post);

		$output = "";

		// Get header/footer data (cached)
		if ($args['include_navigation'] === true) {
			$nav_data = $this->cache->get_header_footer_data([$this, 'extract_header_footer_links']);

			// Add header navigation
			if (!empty($nav_data['header'])) {
				$output .= $this->format_navigation_markdown($nav_data['header'], 'Site Navigation');
				$output .= "---\n\n";
			}
		}

		if (!empty($args['content'])) {
			$output .= $args['content'];
		} else {
			// Title
			$output .= '# ' . esc_html($post->post_title) . "\n\n";

			// Meta information
			$output .= '**URL:** ' . esc_url(get_permalink($post)) . "\n";
			$output .= '**Date:** ' . get_the_date('Y-m-d', $post) . "\n";
			$output .= '**Author:** ' . esc_html(get_the_author_meta('display_name', $post->post_author)) . "\n\n";
			$output .= "---\n\n";

			// Content
			$content = apply_filters('md4ai_the_content', $post->post_content);
			$content = $this->html_to_markdown($content);
			$output .= $content . "\n\n";
		}

		// Categories and tags
		if ($args['include_categories']) {
			$categories = get_the_category($post->ID);
			if (!empty($categories)) {
				$output .= "## Categories\n\n";
				foreach ($categories as $cat) {
					$output .= '- ' . esc_html($cat->name) . "\n";
				}
				$output .= "\n";
			}
		}

		if ($args['include_tags']) {
			$tags = get_the_tags($post->ID);
			if (!empty($tags)) {
				$output .= "## Tags\n\n";
				foreach ($tags as $tag) {
					$output .= '- ' . esc_html($tag->name) . "\n";
				}
				$output .= "\n";
			}
		}

		// Add footer navigation
		if ($args['include_footer']) {
			$nav_data = $this->cache->get_header_footer_data([$this, 'extract_header_footer_links']);
			if (!empty($nav_data['footer'])) {
				$output .= "---\n\n";
				$output .= $this->format_navigation_markdown($nav_data['footer'], 'Footer Links');
			}
		}

		return $output;
	}

	/**
	 * Extracts links from header and footer
	 */
	public function extract_header_footer_links() {
		// Start output buffering to capture header
		ob_start();
		get_header();
		$header_html = ob_get_clean();

		// Capture footer
		ob_start();
		get_footer();
		$footer_html = ob_get_clean();

		return [
			'header' => $this->parse_navigation_html($header_html, 'header'),
			'footer' => $this->parse_navigation_html($footer_html, 'footer')
		];
	}

	/**
	 * Parses HTML to extract navigation links
	 */
	private function parse_navigation_html($html, $type = 'header') {
		$links = [];

		// Remove scripts, styles, and forms
		$html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
		$html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
		$html = preg_replace('/<form\b[^>]*>(.*?)<\/form>/is', '', $html);

		// Extract all links with their text
		preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			$url = $match[1];
			$text = wp_strip_all_tags($match[2]);
			$text = trim(preg_replace('/\s+/', ' ', $text));

			// Skip empty links, anchors, and javascript
			if (empty($text) ||
			    strpos($url, '#') === 0 ||
			    strpos($url, 'javascript:') === 0 ||
			    strlen($text) > 100) { // Skip very long text (likely not navigation)
				continue;
			}

			$links[] = [
				'text' => $text,
				'url' => $url
			];
		}

		// Remove duplicate links (same URL)
		$unique_links = [];
		$seen_urls = [];

		foreach ($links as $link) {
			if (!in_array($link['url'], $seen_urls)) {
				$unique_links[] = $link;
				$seen_urls[] = $link['url'];
			}
		}

		return $unique_links;
	}

	/**
	 * Formats header/footer links as Markdown
	 */
	private function format_navigation_markdown($links, $title) {
		if (empty($links)) {
			return '';
		}

		$output = "## {$title}\n\n";

		foreach ($links as $link) {
			$output .= "- [{$link['text']}]({$link['url']})\n";
		}

		return $output . "\n";
	}

	/**
	 * Basic HTML to Markdown conversion
	 */
	private function html_to_markdown($html) {
		// Remove script, style and forms
		$html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
		$html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
		$html = preg_replace('/<form\b[^>]*>(.*?)<\/form>/is', '', $html);

		// Headers
		$html = preg_replace('/<h1[^>]*>(.*?)<\/h1>/i', "\n# $1\n", $html);
		$html = preg_replace('/<h2[^>]*>(.*?)<\/h2>/i', "\n## $1\n", $html);
		$html = preg_replace('/<h3[^>]*>(.*?)<\/h3>/i', "\n### $1\n", $html);
		$html = preg_replace('/<h4[^>]*>(.*?)<\/h4>/i', "\n#### $1\n", $html);
		$html = preg_replace('/<h5[^>]*>(.*?)<\/h5>/i', "\n##### $1\n", $html);
		$html = preg_replace('/<h6[^>]*>(.*?)<\/h6>/i', "\n###### $1\n", $html);

		// Bold and Italic
		$html = preg_replace('/<(strong|b)[^>]*>(.*?)<\/(strong|b)>/i', '**$2**', $html);
		$html = preg_replace('/<(em|i)[^>]*>(.*?)<\/(em|i)>/i', '*$2*', $html);

		// Link
		$html = preg_replace('/<a[^>]+href="([^"]+)"[^>]*>(.*?)<\/a>/i', '[$2]($1)', $html);

		// Images
		$html = preg_replace('/<img[^>]+src="([^"]+)"[^>]*alt="([^"]*)"[^>]*>/i', '![$2]($1)', $html);
		$html = preg_replace('/<img[^>]+src="([^"]+)"[^>]*>/i', '![]($1)', $html);

		// Lists
		$html = preg_replace('/<li[^>]*>(.*?)<\/li>/i', "- $1\n", $html);
		$html = preg_replace('/<\/?ul[^>]*>/i', "\n", $html);
		$html = preg_replace('/<\/?ol[^>]*>/i', "\n", $html);

		// Paragraphs
		$html = preg_replace('/<p[^>]*>(.*?)<\/p>/i', "$1\n\n", $html);
		$html = preg_replace('/<br[^>]*>/i', "\n", $html);

		// Blockquote
		$html = preg_replace('/<blockquote[^>]*>(.*?)<\/blockquote>/is', "> $1\n", $html);

		// Code
		$html = preg_replace('/<code[^>]*>(.*?)<\/code>/i', '`$1`', $html);
		$html = preg_replace('/<pre[^>]*>(.*?)<\/pre>/is', "```\n$1\n```\n", $html);

		// Remove all other HTML tags
		$html = wp_strip_all_tags($html);

		// Decode HTML entities
		$html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		// Clean up multiple spaces
		$html = preg_replace('/\n\s*\n\s*\n/', "\n\n", $html);
		$html = trim($html);

		return $html;
	}

	/**
	 * Get the meta key
	 */
	public function get_meta_key() {
		return $this->meta_key;
	}
}
