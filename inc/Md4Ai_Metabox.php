<?php

namespace Md4Ai;
class Md4Ai_Metabox {

	private $markdown;

	public function __construct(Md4Ai_Markdown $markdown) {
		$this->markdown = $markdown;

		// Add metabox for Markdown editing
		add_action('add_meta_boxes', [$this, 'add_markdown_metabox']);
		add_action('save_post', [$this, 'save_markdown_metabox'], 20);
	}

	/**
	 * Adds metabox for markdown editing
	 */
	public function add_markdown_metabox() {
		$post_types = get_post_types(['public' => true], 'names');


		foreach ($post_types as $post_type) {
			add_meta_box(
				'md4ai_metabox',
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
	 *
	 * @param $post
	 */
	public function render_markdown_metabox( $post ): void {
		wp_nonce_field( 'md4ai_metabox', 'md4ai_metabox_nonce' );

		$meta_key        = $this->markdown->get_meta_key();
		$custom_markdown = get_post_meta( $post->ID, $meta_key, true );
		$has_custom      = ! empty( $custom_markdown );
		$textarea_id     = 'md4ai-textarea';
		$post_url        = esc_url( home_url( '/?p=' . $post->ID . '&md4ai_md=1' ) );

		?>
		<div id="md4ai-metabox-container">
			<!-- Editor Panel -->
			<div id="md4ai-editor-panel">
				<div class="md4ai-panel-header">
					<h3 class="md4ai-panel-title">
						<span class="dashicons dashicons-edit" style="margin-right: 5px;"></span>
						<?php esc_html_e( 'Markdown Editor', 'md4ai' ); ?>
					</h3>
					<a href="<?php echo esc_url( $post_url ); ?>" target="_blank" class="button button-small">
						<span class="dashicons dashicons-external" style="margin-top: 3px;"></span>
						<?php esc_html_e( 'View Output', 'md4ai' ); ?>
					</a>
				</div>

				<div class="md4ai-notice">
					<span class="md4ai-notice-icon dashicons dashicons-info"></span>
					<div class="md4ai-notice-content">
						<?php esc_html_e( 'Customize the Markdown content that will be served to AI bots. Leave empty to auto-generate from post content.', 'md4ai' ); ?>
					</div>
				</div>

				<?php if ( $has_custom ): ?>
					<div class="md4ai-notice warning">
						<span class="md4ai-notice-icon dashicons dashicons-warning"></span>
						<div class="md4ai-notice-content">
							<strong><?php esc_html_e( 'Custom markdown is active.', 'md4ai' ); ?></strong>
							<?php esc_html_e( 'AI bots will see this content instead of the auto-generated version.', 'md4ai' ); ?>
						</div>
					</div>
				<?php endif; ?>

				<textarea
					name="md4ai_custom_markdown"
					id="<?php echo esc_attr( $textarea_id ); ?>"
					placeholder="<?php esc_attr_e( 'Custom markdown will appear here...', 'md4ai' ); ?>"
				><?php echo esc_textarea( $custom_markdown ); ?></textarea>

				<div class="md4ai-toolbar-section">
					<?php
					echo wp_kses( Md4Ai_Utils::display_llmstxt_buttons( $textarea_id, Md4Ai_Utils::is_ai_service_enabled(), 'generate-markdown' ), array(
						'button' => array(
							'type'          => true,
							'class'         => true,
							'data-action'   => true,
							'data-endpoint' => true,
							'data-field'    => true,
						)
					) );
					echo '<span class="md4ai-toolbar-divider"></span>';

					if ( $has_custom ) {
						printf( '<button type="button" class="button md4ai-clear" data-field="%s">
                            <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
                            %s
                        </button>', esc_attr( $textarea_id ), esc_html__( 'Clear Custom', 'md4ai' ) );
					}
					?>
					<span id="md4ai-status"></span>
				</div>
			</div>

			<!-- Preview Panel -->
			<div id="md4ai-preview-panel">
				<div class="md4ai-panel-header">
					<h3 class="md4ai-panel-title">
						<span class="dashicons dashicons-visibility" style="margin-right: 5px;"></span>
						<?php esc_html_e( 'Markdown Preview', 'md4ai' ); ?>
					</h3>
				</div>

				<div id="md4ai-preview-content" style="min-height: 400px; padding: 12px; background: #fff; border: 1px solid #dcdcde; border-radius: 4px;">
					<p style="color: #787c82; text-align: center; padding: 40px 20px;">
						<span class="dashicons dashicons-welcome-view-site" style="font-size: 48px; opacity: 0.3;"></span><br>
						<?php esc_html_e( 'Preview will appear here', 'md4ai' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Saves the markdown metabox data
	 */
	public function save_markdown_metabox($post_id) {
		// Check nonce
		if (!isset($_POST['md4ai_metabox_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['md4ai_metabox_nonce'])), 'md4ai_metabox')) {
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
		if (isset($_POST['md4ai_custom_markdown'])) {
			$markdown = sanitize_textarea_field(
			/* phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash */
				$_POST['md4ai_custom_markdown']
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

}
