<?php
/**
 * Admin interface class - handles metaboxes, admin pages, and scripts
 */
class md4AI_Admin {

	/**
	 * Cache instance
	 */
	private $cache;

	/**
	 * Markdown instance
	 */
	private $markdown;

	/**
	 * Option name for llms.txt content
	 */
	private $llms_txt_option = 'md4ai_llms_txt_content';

	public function __construct($cache, $markdown) {
		$this->cache = $cache;
		$this->markdown = $markdown;

		// Add metabox for Markdown editing
		add_action('add_meta_boxes', [$this, 'add_markdown_metabox']);
		add_action('save_post', [$this, 'save_markdown_metabox'], 20);

		// Enqueue admin scripts
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
		if ( function_exists( 'ai_services' ) ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_services' ]);
		}

		// Add the admin menu for cache management
		add_action('admin_menu', [$this, 'add_admin_menu']);
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

		$meta_key = $this->markdown->get_meta_key();
		$custom_markdown = get_post_meta($post->ID, $meta_key, true);
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
				<button type="button" id="md4ai-generate" class="button">
					<?php esc_html_e('Generate from Current Content using AI', 'md4ai'); ?>
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
		if (!isset($_POST['ai_md_metabox_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ai_md_metabox_nonce'])), 'ai_md_metabox')) {
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

			$meta_key = $this->markdown->get_meta_key();

			if (!empty($markdown)) {
				update_post_meta($post_id, $meta_key, $markdown);
			} else {
				delete_post_meta($post_id, $meta_key);
			}

			// Clear cache when custom markdown is updated
			$this->cache->clear_post_cache($post_id);
		}
	}

	/**
	 * Gets the llms.txt content
	 *
	 * @return string The llms.txt content
	 */
	public function get_llms_txt_content() {
		return get_option($this->llms_txt_option, '');
	}

	/**
	 * Generates default llms.txt content using WordPress site data
	 *
	 * @return string Default llms.txt content
	 */
	public function generate_default_llmstxt() {
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
				$post_excerpt = wp_trim_words(strip_tags($post->post_excerpt ?: $post->post_content), 20);

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
		$content .= $this->markdown->generate_website_links([
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

		// Get the REST API namespace from the RestAPI class instance
		$rest_namespace = 'md4ai/v1'; // This should ideally be passed from the RestAPI class

		wp_localize_script('md4ai-admin', 'aiMdData', [
			'restUrl' => rest_url($rest_namespace . '/generate-markdown/'),
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
	 * Enqueues admin AI services scripts
	 */
	public function enqueue_admin_services() {

		$asset = include MD4AI_PLUGIN_DIR . '/build/md4ai-services.asset.php';
		$asset['dependencies'][] = 'ais-ai';

		wp_enqueue_script(
			'md4ai-admin',
			plugins_url('build/md4ai-services.js', dirname(__FILE__)),
			$asset['dependencies'],
			$asset['version'],
			true
		);
	}

	/**
	 * Adds admin menu for cache management
	 */
	public function add_admin_menu() {
		add_management_page(
			'AI Markdown Cache',
			'md4AI',
			'manage_options',
			'Md4ai',
			[$this, 'render_admin_page']
		);
	}

	public function display_llmstxt_buttons() {
		?>
		<button type="button" class="button button-primary" id="md4ai generate_llmstxt">Generate llms.txt</button>
		<button type="button" class="button button-primary button-primary-ai" id="md4ai ai_generate_llmstxt">Generate llms.txt using AI</button>
		<?php
	}

	/**
	 * Renders the admin page
	 */
	public function render_admin_page() {
		// Handle cache clear request
		if (isset($_POST['clear_cache']) && check_admin_referer('ai_md_clear_cache')) {
			$this->cache->clear_all_cache();
			echo '<div class="notice notice-success"><p>Cache cleared successfully!</p></div>';
		}

		// Handle llms.txt update
		if (isset($_POST['update_llmstxt']) && check_admin_referer('ai_md_update_llmstxt')) {
			if (isset($_POST['llmstxt_content'])) {
				$llms_content = sanitize_textarea_field(wp_unslash($_POST['llmstxt_content']));
				update_option($this->llms_txt_option, $llms_content);
				echo '<div class="notice notice-success"><p>llms.txt updated successfully!</p></div>';
			}
		}

		// Get cache statistics
		$stats = $this->cache->get_statistics();
		$llms_content = get_option($this->llms_txt_option, '');

		?>
		<div class="wrap">
			<h1><?php esc_html_e('md4AI', 'md4ai'); ?></h1>

			<div class="card">
				<h2><?php esc_html_e('llms.txt Content', 'md4ai'); ?></h2>
				<p><?php esc_html_e('This content will be served at /llms.txt for AI bots and crawlers. Leave empty to use default.', 'md4ai'); ?></p>
				<form method="post">
					<?php wp_nonce_field('ai_md_update_llmstxt'); ?>
					<p>
						<label for="llmstxt_content"><?php esc_html_e('llms.txt Content', 'md4ai'); ?></label>
						<textarea
							name="llmstxt_content"
							rows="20"
							style="width: 100%; font-family: monospace; font-size: 13px;"
							placeholder="<?php esc_attr_e('## Title

> Optional description goes here

Optional details go here

## Section name

- [Link title](https://link_url): Optional link details

## Optional

- [Link title](https://link_url)', 'md4ai'); ?>"
						><?php echo esc_textarea($llms_content); ?></textarea>
						<?php $this->display_llmstxt_buttons(); ?>
						<input type="submit" name="update_llmstxt" class="button button-primary"
							   value="<?php esc_attr_e('Update llms.txt', 'md4ai'); ?>">
					</p>
				</form>
			</div>

			<div class="card">
				<h2><?php esc_html_e('Cache Statistics', 'md4ai'); ?></h2>
				<p><strong><?php esc_html_e('Cached Files:', 'md4ai'); ?></strong> <?php echo esc_html($stats['file_count']); ?></p>
				<p><strong><?php esc_html_e('Total Size:', 'md4ai'); ?></strong> <?php echo esc_html($stats['total_size_mb']); ?> MB</p>
				<p><strong><?php esc_html_e('Cache Directory:', 'md4ai'); ?></strong> <code><?php echo esc_html($stats['cache_dir']); ?></code></p>
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
