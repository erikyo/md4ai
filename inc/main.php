<?php
class md4AI {

	/**
	 * List of AI bots to detect
	 */
	private $ai_useragents = [
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

	private $ai_bots;

	/**
	 * Cache directory path
	 */
	private $cache_dir;

	/**
	 * Post-meta key for custom Markdown
	 */
	private $meta_key = '_ai_md_custom_markdown';

	/**
	 * REST API namespace
	 */
	private $namespace = 'md4ai/v1';

	public function __construct() {
		$this->cache_dir = WP_CONTENT_DIR . '/cache/md4ai';

		// Create a cache directory if it doesn't exist
		$this->ensure_cache_directory();

		add_action('template_redirect', [$this, 'serve_markdown_to_bots'], 1);

		// Clear cache when a post is updated
		add_action('save_post', [$this, 'clear_post_cache']);
		add_action('delete_post', [$this, 'clear_post_cache']);

		// Add the admin menu for cache management
		add_action('admin_menu', [$this, 'add_admin_menu']);

		// Add metabox for Markdown editing
		add_action('add_meta_boxes', [$this, 'add_markdown_metabox']);
		add_action('save_post', [$this, 'save_markdown_metabox'], 20);

		// Enqueue admin scripts
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

		// Register REST API routes
		add_action('rest_api_init', [$this, 'register_rest_routes']);

		$this->ai_bots = $this->setup_ai_useragents();
	}

	private function setup_ai_useragents() {
		return apply_filters( 'md4ai_ai_useragents', $this->ai_useragents );
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

		$user_agent = strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])));

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

		// Check if cache is newer than post modification date
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
			wp_delete_file($cache_file);
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
					wp_delete_file($file);
				}
			}
		}

		return true;
	}

	/**
	 * Gets markdown for a post - checks custom meta first, then generates
	 */
	private function get_post_markdown($post) {
		// Check if custom markdown exists
		$custom_markdown = get_post_meta($post->ID, $this->meta_key, true);

		if (!empty($custom_markdown)) {
			return $custom_markdown;
		}

		// Generate from post content
		return $this->convert_post_to_markdown($post);
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

		if ((!$post || !is_singular()) && !is_home()) {
			return;
		}

		$markdown = false;
		$from_cache = false;

		// Try to get from cache first
		if ($this->is_cache_valid($post->ID, $post->post_modified)) {
			$markdown = $this->read_from_cache($post->ID);
			$from_cache = true;
		}

		// If no valid cache, get markdown (custom or generated) and save to cache
		if ($markdown === false) {
			$markdown = $this->get_post_markdown($post);
			$this->write_to_cache($post->ID, $markdown);
			$from_cache = false;
		}

		// Set headers and serve the content
		header('Content-Type: text/markdown; charset=utf-8');
		header('X-Robots-Tag: noindex, nofollow');
		header('X-Cache: ' . ($from_cache ? 'HIT' : 'MISS'));
		echo esc_textarea($markdown);
		exit;
	}

	/**
	 * Converts a WordPress post to Markdown
	 */
	private function convert_post_to_markdown($post) {
		/* Filter the post */
		$post = apply_filters('md4ai_post', $post);

		$output = '';

		// Title
		$output .= '# ' . esc_html($post->post_title) . "\n\n";

		// Meta information
		$output .= '**URL:** ' . esc_url(get_permalink($post)) . "\n";
		$output .= '**Date:** ' . get_the_date('Y-m-d', $post) . "\n";
		$output .= '**Author:** ' . esc_html(get_the_author_meta('display_name', $post->post_author)) . "\n\n";
		$output .= "---\n\n";

		// Content
		/* Filter the post content */
		$content = apply_filters('md4ai_the_content', $post->post_content);
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
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		register_rest_route($this->namespace, '/generate-markdown/(?P<id>\d+)', [
			'methods' => 'POST',
			'callback' => [$this, 'rest_generate_markdown'],
			'permission_callback' => [$this, 'rest_permission_check'],
			'args' => [
				'id' => [
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				],
			],
		]);
	}

	/**
	 * Permission check for REST API
	 */
	public function rest_permission_check($request) {
		$post_id = $request->get_param('id');
		return current_user_can('edit_post', $post_id);
	}

	/**
	 * REST API handler for generating markdown
	 */
	public function rest_generate_markdown($request) {
		$post_id = $request->get_param('id');

		if (!$post_id) {
			return new WP_Error('invalid_post', __('Invalid post ID.', 'md4ai'), ['status' => 400]);
		}

		$post = get_post($post_id);

		if (!$post) {
			return new WP_Error('post_not_found', __('Post not found.', 'md4ai'), ['status' => 404]);
		}

		$markdown = $this->convert_post_to_markdown($post);

		return new WP_REST_Response([
			'markdown' => $markdown,
		], 200);
	}

	/**
	 * Adds metabox for markdown editing
	 */
	public function add_markdown_metabox() {
		$post_types = get_post_types(['public' => true], 'names');

		foreach ($post_types as $post_type) {
			add_meta_box(
				'ai_md_metabox',
				__('AI Bot Markdown Content', 'md4ai'),
				[$this, 'render_markdown_metabox'],
				$post_type,
				'normal',
				'low'
			);
		}
	}

	/**
	 * Renders the markdown metabox
	 */
	public function render_markdown_metabox($post) {
		wp_nonce_field('ai_md_metabox', 'ai_md_metabox_nonce');

		$custom_markdown = get_post_meta($post->ID, $this->meta_key, true);
		$has_custom = !empty($custom_markdown);

		?>
		<div id="md4ai-metabox">
			<p class="description">
				<?php esc_html_e('Customize the Markdown content that will be served to AI bots. Leave empty to auto-generate from post content.', 'md4ai'); ?>
			</p>

			<p>
				<button type="button" id="md4ai-generate" class="button">
					<?php esc_html_e('Generate from Current Content', 'md4ai'); ?>
				</button>
				<?php if ($has_custom): ?>
					<button type="button" id="md4ai-clear" class="button">
						<?php esc_html_e('Clear Custom Markdown', 'md4ai'); ?>
					</button>
				<?php endif; ?>
				<span id="md4ai-status" style="margin-left: 10px;"></span>
			</p>

			<p>
				<textarea
					name="ai_md_custom_markdown"
					id="md4ai-textarea"
					rows="20"
					style="width: 100%; font-family: monospace; font-size: 13px;"
					placeholder="<?php esc_attr_e('Custom markdown will appear here...', 'md4ai'); ?>"
				><?php echo esc_textarea($custom_markdown); ?></textarea>
			</p>

			<?php if ($has_custom): ?>
				<p class="description" style="color: #d63638;">
					<strong><?php esc_html_e('Note:', 'md4ai'); ?></strong>
					<?php esc_html_e('Custom markdown is active. AI bots will see this content instead of the auto-generated version.', 'md4ai'); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Saves the markdown metabox data
	 */
	public function save_markdown_metabox($post_id) {
		// Check nonce
		if ( ! isset( $_POST['ai_md_metabox_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ai_md_metabox_nonce'] ) ), 'ai_md_metabox' ) ) {
			return;
		}

		// Check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		// Check permissions
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		// Save or delete custom markdown
		if (isset($_POST['ai_md_custom_markdown'])) {
			$markdown = sanitize_textarea_field(
			/* phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash */
				$_POST['ai_md_custom_markdown']
			);

			if (!empty($markdown)) {
				update_post_meta($post_id, $this->meta_key, $markdown);
			} else {
				delete_post_meta($post_id, $this->meta_key);
			}

			// Clear cache when custom markdown is updated
			$this->clear_post_cache($post_id);
		}
	}

	/**
	 * Enqueues admin scripts
	 */
	public function enqueue_admin_scripts($hook) {
		if (!in_array($hook, ['post.php', 'post-new.php'])) {
			return;
		}

		wp_enqueue_script(
			'md4ai-admin',
			plugins_url('build/md4ai-admin.js', dirname(__FILE__)),
			[],
			'1.0.0',
			true
		);

		wp_localize_script('md4ai-admin', 'aiMdData', [
			'restUrl' => rest_url($this->namespace . '/generate-markdown/'),
			'nonce' => wp_create_nonce('wp_rest'),
			'postId' => get_the_ID(),
			'messages' => [
				'generating' => __('Generating...', 'md4ai'),
				'success' => __('Markdown generated successfully!', 'md4ai'),
				'error' => __('Error generating markdown.', 'md4ai'),
				'cleared' => __('Custom markdown cleared.', 'md4ai')
			]
		]);
	}

	/**
	 * Adds admin menu for cache management
	 */
	public function add_admin_menu() {
		add_management_page(
			'AI Markdown Cache',
			'md4AI Cache',
			'manage_options',
			'md4ai-cache',
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
			<h1><?php esc_html_e('md4AI', 'md4ai'); ?></h1>

			<div class="card">
				<h2><?php esc_html_e('Cache Statistics', 'md4ai'); ?></h2>
				<p><strong><?php esc_html_e('Cached Files:', 'md4ai'); ?></strong> <?php echo esc_html($file_count); ?></p>
				<p><strong><?php esc_html_e('Total Size:', 'md4ai'); ?></strong> <?php echo esc_html($total_size_mb); ?> MB</p>
				<p><strong><?php esc_html_e('Cache Directory:', 'md4ai'); ?></strong> <code><?php echo esc_html($this->cache_dir); ?></code></p>
			</div>

			<div class="card">
				<h2><?php esc_html_e('Clear Cache', 'md4ai'); ?></h2>
				<p><?php esc_html_e('Clear all cached Markdown files. This will force regeneration on the next AI bot visit.', 'md4ai'); ?></p>
				<form method="post">
					<?php wp_nonce_field('ai_md_clear_cache'); ?>
					<input type="submit" name="clear_cache" class="button button-primary"
						   value="<?php esc_attr_e('Clear All Cache', 'md4ai'); ?>"
						   onclick="return confirm('<?php esc_html_e('Are you sure you want to clear all cached files?', 'md4ai'); ?>');">
				</form>
			</div>
		</div>
		<?php
	}
}
