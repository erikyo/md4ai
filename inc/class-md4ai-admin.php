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
	private $llms_txt_placeholder = '## Title

> Optional description goes here

Optional details go here

## Section name

- [Link title](https://link_url): Optional link details

## Optional

- [Link title](https://link_url)';

	public function __construct($cache, $markdown) {
		$this->cache = $cache;
		$this->markdown = $markdown;

		// Add metabox for Markdown editing
		add_action('add_meta_boxes', [$this, 'add_markdown_metabox']);
		add_action('save_post', [$this, 'save_markdown_metabox'], 20);

		// Enqueue admin scripts
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

		// Enqueue admin services scripts
		if ( self::is_ai_service_enabled() ) {
			add_action( 'admin_enqueue_scripts', [$this, 'enqueue_admin_services' ]);
		}

		// Add the admin menu for cache management
		add_action('admin_menu', [$this, 'add_admin_menu']);
	}

	/**
	 * Checks if the AI services are enabled
	 *
	 * @return bool Whether the AI services are enabled
	 */
	private function is_ai_service_enabled(): bool {
		return function_exists( 'ai_services' );
	}

	/**
	 * Gets the llms.txt content
	 *
	 * @return string The llms.txt content
	 */
	public function get_llms_txt_content() {
		return get_option($this->llms_txt_option, '');
	}

	private function render_card_llms_txt() {
		$llms_content = get_option($this->llms_txt_option, '');
		?>
		<div class="card">
			<h2><?php esc_html_e('llms.txt Content', 'md4ai'); ?></h2>
			<p><?php esc_html_e('This content will be served at /llms.txt for AI bots and crawlers. Leave empty to use default.', 'md4ai'); ?></p>
			<form method="post">
				<?php wp_nonce_field('ai_md_update_llmstxt'); ?>
				<p>
					<label for="llmstxt_content"><?php esc_html_e('llms.txt Content', 'md4ai'); ?></label>
					<textarea
						id="llmstxt_content"
						name="llmstxt_content"
						rows="20"
						style="width: 100%; font-family: monospace; font-size: 13px;"
						placeholder="<?php echo $this->llms_txt_placeholder; ?>"
					><?php echo esc_textarea($llms_content); ?></textarea>
					<?php
					// display the generate buttons
					echo $this->display_llmstxt_buttons('llmstxt_content');
					// display the clean button
					printf( '<button type="button" class="button md4ai-clear" data-field="%s">%s</button>', 'llmstxt_content', esc_html__('Clear llms.txt', 'md4ai'));
					?>
					<input type="submit" name="update_llmstxt" class="button button-primary" data-field="llmstxt_content"
						   value="<?php esc_attr_e('Update llms.txt', 'md4ai'); ?>">
				</p>
				<span id="md4ai-status" style="margin-left: 10px;"></span>
			</form>
		</div>
		<?php
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
	 * Enqueues admin AI services scripts
	 */
	public function enqueue_admin_services() {

		$asset = include MD4AI_PLUGIN_DIR . '/build/md4ai-services.asset.php';
		$asset['dependencies'][] = 'ais-ai';

		wp_enqueue_script(
			'md4ai-services',
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
			'Md4AI',
			'manage_options',
			'md4ai',
			[$this, 'render_admin_page']
		);
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
	 * Enqueues admin scripts
	 */
	public function enqueue_admin_scripts($hook) {
		// check if the current script is loaded in the admin area or in the md4ai admin page
		if (!in_array($hook, ['post.php', 'post-new.php'] ) && get_current_screen()->base !== 'tools_page_md4ai') {
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
			'restUrl' => rest_url($rest_namespace ),
			'nonce' => wp_create_nonce('wp_rest' ),
			'postId' => get_the_ID(),
			'prompts' => [
				'generate-markdown' => 'You are a highly skilled SEO and GEO expert. Review the Markdown content below. Identify the key topics and generate a section of 3-5 relevant Question and Answer (Q&A) pairs to be appended to the end of the article. The Q&A should be in Markdown format, with bold questions. Output only the full, modified page content including the new Q&A section.',
				'generate-llmstxt' => 'You are a highly skilled SEO and GEO expert. Enhance the llms.txt file below to improve the Generative Engine Optimization (GEO) of the site.'
			]
		]);
	}

	/**
	 * Displays buttons for generating llms.txt content
	 *
	 * @param string $field The field name to pass as a data attribute
	 * @param string $endpoint The REST API endpoint to call when generating llms.txt
	 *
	 * @return string The HTML output containing the buttons
	 */
	public function display_llmstxt_buttons( string $field, string $endpoint = 'generate-llmstxt'): string {
		$output = '';

		// the data field is used to pass the field name to the JavaScript, that is the HTML id of the textarea to update
		$data_field = sprintf( 'data-field="%s" ', $field );

		$output .= sprintf( '<button type="button" class="button md4ai-generate" data-action="replace" data-endpoint="%s" %s>%s</button>', $endpoint, $data_field, esc_html__('Generate', 'md4ai') );

		// if AI service is enabled, add the AI generate button
		if ( $this->is_ai_service_enabled() ) {
			$output .= sprintf( '<button type="button" class="button md4ai-ai-generate button-primary-ai" data-action="append-after" data-endpoint="%s" %s>%s</button>', $endpoint, $data_field, esc_html__( 'Generate using AI', 'md4ai' ) );
		}

		return $output;
	}

	private function render_card_cache() {
		// Get cache statistics
		$stats = $this->cache->get_statistics();
		?>
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
		<?php
	}

	/**
	 * Renders the admin page
	 */
	public function render_admin_page() {
		// Handle cache clear request
		if (isset($_POST['clear_cache']) && check_admin_referer('ai_md_clear_cache')) {
			$this->cache->clear_all_cache();
			printf('<div class="notice notice-success"><p>%s</p></div>', esc_html__( 'Cache cleared successfully!', 'md4ai' ));
		}

		// Handle llms.txt update
		if (isset($_POST['update_llmstxt']) && check_admin_referer('ai_md_update_llmstxt')) {
			if (isset($_POST['llmstxt_content'])) {
				$llms_content = sanitize_textarea_field(wp_unslash($_POST['llmstxt_content']));
				update_option($this->llms_txt_option, $llms_content);
				printf('<div class="notice notice-success"><p>%s</p></div>', __('llms.txt updated successfully!', 'md4ai'));
			}
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e('md4AI', 'md4ai'); ?></h1>

			<?php self::render_card_llms_txt(); ?>

			<?php self::render_card_cache(); ?>
		</div>
		<?php
	}

	/**
	 * Renders the markdown metabox
	 */
	public function render_markdown_metabox($post) {
		wp_nonce_field('ai_md_metabox', 'ai_md_metabox_nonce');

		$meta_key = $this->markdown->get_meta_key();
		$custom_markdown = get_post_meta($post->ID, $meta_key, true);
		$has_custom = !empty($custom_markdown);
		$textarea_id = 'md4ai-textarea';

		?>
		<div id="md4ai-metabox">
			<p class="description">
				<?php esc_html_e('Customize the Markdown content that will be served to AI bots. Leave empty to auto-generate from post content.', 'md4ai'); ?>
			</p>

			<p>
				<?php
				// display the generate buttons
				echo self::display_llmstxt_buttons($textarea_id, 'generate-markdown' );
				// display the clear button if there is custom Markdown
				if ($has_custom) {
					printf( '<button type="button" class="button md4ai-clear" data-field="%s">%s</button>', $textarea_id, esc_html__('Clear Custom Markdown', 'md4ai'));
				}
				?>
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
}
