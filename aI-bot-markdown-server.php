<?php
/**
 * Plugin Name: AI Bot Markdown Server
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 1.0
Author: Dell7010
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
 */


if (!defined('ABSPATH')) {
	exit;
}

class AI_Bot_Markdown_Server {

	/**
	 * List of AI bots to detect
	 */
	private $ai_bots = [
		'gptbot',
		'oai-searchbot',
		'chatgpt-user',
		'claudebot',
		'perplexitybot',
		'google-extended',
		'bingbot',
		'anthropic-ai',
		'cohere-ai',
		'omgilibot',
		'omgili',
		'facebookbot',
		'applebot',
		'youbot'
	];

	/**
	 * Cache directory path
	 */
	private $cache_dir;

	public function __construct() {
		$this->cache_dir = WP_CONTENT_DIR . '/cache/ai-md';

		// Create cache directory if it doesn't exist
		$this->ensure_cache_directory();

		add_action('template_redirect', [$this, 'serve_markdown_to_bots'], 1);

		// Clear cache when post is updated
		add_action('save_post', [$this, 'clear_post_cache'], 10, 1);
		add_action('delete_post', [$this, 'clear_post_cache'], 10, 1);

		// Add admin menu for cache management
		add_action('admin_menu', [$this, 'add_admin_menu']);
	}

	/**
	 * Ensures the cache directory exists and is writable
	 */
	private function ensure_cache_directory() {
		if (!file_exists($this->cache_dir)) {
			wp_mkdir_p($this->cache_dir);
		}

		// Add .htaccess to protect cache directory
		$htaccess_file = $this->cache_dir . '/.htaccess';
		if (!file_exists($htaccess_file)) {
			$htaccess_content = "Order deny,allow\nDeny from all";
			file_put_contents($htaccess_file, $htaccess_content);
		}

		// Add index.php to prevent directory listing
		$index_file = $this->cache_dir . '/index.php';
		if (!file_exists($index_file)) {
			file_put_contents($index_file, "<?php\n// Silence is golden.");
		}
	}

	/**
	 * Checks if the user agent matches an AI bot
	 */
	private function is_ai_bot() {
		if (!isset($_SERVER['HTTP_USER_AGENT'])) {
			return false;
		}

		$user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);

		foreach ($this->ai_bots as $bot) {
			if (strpos($user_agent, strtolower($bot)) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets the cache file path for a post
	 */
	private function get_cache_file_path($post_id) {
		return $this->cache_dir . '/post-' . $post_id . '.md';
	}

	/**
	 * Checks if cached file exists and is valid
	 */
	private function is_cache_valid($post_id, $post_modified) {
		$cache_file = $this->get_cache_file_path($post_id);

		if (!file_exists($cache_file)) {
			return false;
		}

		// Check if cache is newer than post modification
		$cache_time = filemtime($cache_file);
		$post_time = strtotime($post_modified);

		return $cache_time >= $post_time;
	}

	/**
	 * Reads markdown from cache
	 */
	private function read_from_cache($post_id) {
		$cache_file = $this->get_cache_file_path($post_id);

		if (file_exists($cache_file)) {
			return file_get_contents($cache_file);
		}

		return false;
	}

	/**
	 * Writes markdown to cache
	 */
	private function write_to_cache($post_id, $markdown) {
		$cache_file = $this->get_cache_file_path($post_id);

		return file_put_contents($cache_file, $markdown) !== false;
	}

	/**
	 * Clears cache for a specific post
	 */
	public function clear_post_cache($post_id) {
		$cache_file = $this->get_cache_file_path($post_id);

		if (file_exists($cache_file)) {
			unlink($cache_file);
		}
	}

	/**
	 * Clears all cache files
	 */
	public function clear_all_cache() {
		$files = glob($this->cache_dir . '/post-*.md');

		if ($files) {
			foreach ($files as $file) {
				if (is_file($file)) {
					unlink($file);
				}
			}
		}

		return true;
	}

	/**
	 * Serves the content in Markdown to AI bots
	 */
	public function serve_markdown_to_bots() {
		// Check if it's an AI bot
		if (!$this->is_ai_bot()) {
			return;
		}

		// Get the current post
		global $post;

		if (!$post || !is_singular()) {
			return;
		}

		$markdown = false;

		// Try to get from cache first
		if ($this->is_cache_valid($post->ID, $post->post_modified)) {
			$markdown = $this->read_from_cache($post->ID);
		}

		// If no valid cache, generate and save
		if ($markdown === false) {
			$markdown = $this->convert_post_to_markdown($post);
			$this->write_to_cache($post->ID, $markdown);
		}

		// Set headers and serve the content
		header('Content-Type: text/markdown; charset=utf-8');
		header('X-Robots-Tag: noindex, nofollow');
		header('X-Cache: ' . ($this->is_cache_valid($post->ID, $post->post_modified) ? 'HIT' : 'MISS'));
		echo $markdown;
		exit;
	}

	/**
	 * Converts a WordPress post to Markdown
	 */
	private function convert_post_to_markdown($post) {
		$output = '';

		// Title
		$output .= '# ' . esc_html($post->post_title) . "\n\n";

		// Meta information
		$output .= '**URL:** ' . esc_url(get_permalink($post)) . "\n";
		$output .= '**Date:** ' . get_the_date('Y-m-d', $post) . "\n";
		$output .= '**Author:** ' . esc_html(get_the_author_meta('display_name', $post->post_author)) . "\n\n";
		$output .= "---\n\n";

		// Content
		$content = apply_filters('the_content', $post->post_content);
		$content = $this->html_to_markdown($content);
		$output .= $content . "\n\n";

		// Categories and tags
		$categories = get_the_category($post->ID);
		if (!empty($categories)) {
			$output .= "## Categories\n\n";
			foreach ($categories as $cat) {
				$output .= '- ' . esc_html($cat->name) . "\n";
			}
			$output .= "\n";
		}

		$tags = get_the_tags($post->ID);
		if (!empty($tags)) {
			$output .= "## Tags\n\n";
			foreach ($tags as $tag) {
				$output .= '- ' . esc_html($tag->name) . "\n";
			}
			$output .= "\n";
		}

		return $output;
	}

	/**
	 * Basic HTML to Markdown conversion
	 */
	private function html_to_markdown($html) {
		// Remove script and style
		$html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
		$html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

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
		$html = strip_tags($html);

		// Decode HTML entities
		$html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		// Clean up multiple spaces
		$html = preg_replace('/\n\s*\n\s*\n/', "\n\n", $html);
		$html = trim($html);

		return $html;
	}

	/**
	 * Adds admin menu for cache management
	 */
	public function add_admin_menu() {
		add_management_page(
			'AI Markdown Cache',
			'AI MD Cache',
			'manage_options',
			'ai-md-cache',
			[$this, 'render_admin_page']
		);
	}

	/**
	 * Renders the admin page
	 */
	public function render_admin_page() {
		// Handle cache clear request
		if (isset($_POST['clear_cache']) && check_admin_referer('ai_md_clear_cache')) {
			$this->clear_all_cache();
			echo '<div class="notice notice-success"><p>Cache cleared successfully!</p></div>';
		}

		// Get cache statistics
		$files = glob($this->cache_dir . '/post-*.md');
		$file_count = $files ? count($files) : 0;
		$total_size = 0;

		if ($files) {
			foreach ($files as $file) {
				$total_size += filesize($file);
			}
		}

		$total_size_mb = number_format($total_size / 1024 / 1024, 2);

		?>
		<div class="wrap">
			<h1>AI Markdown Cache Management</h1>

			<div class="card">
				<h2>Cache Statistics</h2>
				<p><strong>Cached Files:</strong> <?php echo $file_count; ?></p>
				<p><strong>Total Size:</strong> <?php echo $total_size_mb; ?> MB</p>
				<p><strong>Cache Directory:</strong> <code><?php echo esc_html($this->cache_dir); ?></code></p>
			</div>

			<div class="card">
				<h2>Clear Cache</h2>
				<p>Clear all cached Markdown files. This will force regeneration on the next AI bot visit.</p>
				<form method="post">
					<?php wp_nonce_field('ai_md_clear_cache'); ?>
					<input type="submit" name="clear_cache" class="button button-primary" value="Clear All Cache"
					       onclick="return confirm('Are you sure you want to clear all cached files?');">
				</form>
			</div>
		</div>
		<?php
	}
}

// Initialize the plugin
new AI_Bot_Markdown_Server();
