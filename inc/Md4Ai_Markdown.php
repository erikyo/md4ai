<?php

namespace Md4Ai;

/**
 * Markdown generation and conversion class
 */
class Md4Ai_Markdown {

	/**
	 * Post-meta key for custom Markdown
	 */
	private string $meta_key = '_md4ai_custom_markdown';

	/**
	 * Cache instance
	 */
	private $cache;

	public function __construct($cache) {
		$this->cache = $cache;
	}

	/**
	 * Get the meta key
	 */
	public function get_meta_key(): string {
		return $this->meta_key;
	}

	/**
	 * Gets markdown for a post - checks custom meta first, then generates
	 */
	public function get_post_markdown($post) {
		/**
		 * Filter to modify post arguments
		 *
		 * @param array $args The arguments to pass to the convert_post_to_markdown method
		 */
		$args = apply_filters('md4ai_post_args', [
			'include_navigation' => true,
			'include_categories' => true,
			'include_tags' => true,
			'include_footer' => true
		]);

		// Check if custom markdown exists
		$args['content'] = get_post_meta($post->ID, $this->meta_key, true);

		// Generate from post content
		return $this->convert_post_to_markdown($post, $args);
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

	public function generate_website_links($args, $post = false) {
		$output = "";

		// Categories and tags
		if ($post && $args['include_categories']) {
			$categories = get_the_category($post->ID);
			if (!empty($categories)) {
				$output .= "---\n\n";
				$output .= "## Categories\n\n";
				foreach ($categories as $cat) {
					$output .= '- ' . esc_html($cat->name) . "\n";
				}
				$output .= "\n";
			}
		}

		// Get header/footer data (cached)
		if ($args['include_navigation'] === true) {
			$nav_data = $this->cache->get_header_footer_data([$this, 'extract_header_footer_links']);

			// Add header navigation
			if (!empty($nav_data['header'])) {
				$output .= "---\n\n";
				$output .= $this->format_navigation_markdown($nav_data['header'], 'Navigation');
			}
		}

		if ($post && $args['include_tags']) {
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

		/**
		 * Filter to modify post before conversion to Markdown
		 *
		 * @param object $post The post object
		 */
		$post = apply_filters('md4ai_post', $post);

		$output = "";

		if (!empty($args['content'])) {
			$output .= $args['content'] . "\n\n";
		} else {
			// Title
			$output .= '# ' . esc_html($post->post_title) . "\n\n";

			// The Page Meta information
			$output .= '**URL:** ' . esc_url(get_permalink($post)) . "\n";
			$output .= '**Date:** ' . get_the_date('Y-m-d', $post) . "\n";
			$output .= '**Author:** ' . esc_html(get_the_author_meta('display_name', $post->post_author)) . "\n\n";
			$output .= "---\n\n";

			/**
			 * Filter to modify content before conversion to Markdown
			 *
			 * @param string $content The post content
			 */
			$content = apply_filters('md4ai_the_content', $post->post_content);

			// Convert HTML to Markdown
			$output .= $this->html_to_markdown($content) . "\n\n";
		}

		$output .= $this->generate_website_links($args, $post);

		return $output;
	}

	/**
	 * Extracts links from header and footer
	 */
	public function extract_header_footer_links() {
		// Start output buffering to capture the header
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
			if (empty($text) || str_starts_with( $url, '#' ) || str_starts_with( $url, 'javascript:' ) ||
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
	 *
	 * @return string The formatted Markdown
	 */
	private function format_navigation_markdown($links, $title): string {
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
	 * Generates default llms.txt content using WordPress site data
	 *
	 * @return string Default llms.txt content
	 */
	public function generate_default_llmstxt(): string {
		$site_title = get_bloginfo('name');
		$site_description = get_bloginfo('description');
		$site_url = home_url();
		$admin_email = get_bloginfo('admin_email');

		// Get recent posts
		$recent_posts = get_posts([
			'numberposts' => 5,
			'post_status' => 'publish'
		]);

		// Build the default content
		$content = "# {$site_title}\n";

		if (!empty($site_description)) {
			$content .= "> {$site_description}\n\n";
		}

		$content .= "This file provides structured information about {$site_title} for AI bots and LLM crawlers.\n\n";

		// Add site information section
		$content .= "## Site Information\n";
		$content .= "- **Website**: [{$site_title}]({$site_url})\n";

		if (!empty($site_description)) {
			$content .= "- **Description**: {$site_description}\n";
		}

		$content .= "- **Contact**: {$admin_email}\n\n";

		// Add recent content section
		if (!empty($recent_posts)) {
			$content .= "## Recent Content\n";
			foreach ($recent_posts as $post) {
				$post_url = get_permalink($post->ID);
				$post_title = esc_html($post->post_title);
				$post_excerpt = wp_trim_words(wp_strip_all_tags($post->post_excerpt ?: $post->post_content), 20);

				$content .= "- [{$post_title}]({$post_url})";
				if (!empty($post_excerpt)) {
					$content .= ": {$post_excerpt}";
				}
				$content .= "\n";
			}
			$content .= "\n";
		}

		// Add navigation/pages section if there are published pages
		$pages = get_pages([
			'post_status' => 'publish',
			'number' => 10,
			'sort_column' => 'menu_order'
		]);

		if (!empty($pages)) {
			$content .= "## Main Pages\n";
			foreach ($pages as $page) {
				$page_url = get_permalink($page->ID);
				$page_title = esc_html($page->post_title);
				$content .= "- [{$page_title}]({$page_url})\n";
			}
			$content .= "\n";
		}

		// Add navigation sections if there are any
		$content .= $this->generate_website_links([
			'include_categories' => false,
			'include_navigation' => true,
			'include_tags' => false,
			'include_footer' => true,
		]);

		// Add footer note
		$content .= "---\n\n## Additional Information\n";
		$content .= "For more information about our content and structure, please explore the links above or visit our homepage at {$site_url}.\n";

		return $content;
	}
}
