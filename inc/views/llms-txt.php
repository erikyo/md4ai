<?php
/**
 * llms.txt View
 *
 * @var string $llms_content
 * @var string $llms_url
 * @var bool $has_content
 * @var string $llms_txt_placeholder
 */

use Md4Ai\Md4Ai_Utils;

?>
<div id="cf7a-tab-panel md4ai-llms-txt">

	<div class="md4ai-llms-notice <?php echo $has_content ? 'success' : ''; ?>">
		<span class="md4ai-llms-notice-icon dashicons <?php echo $has_content ? 'dashicons-yes-alt' : 'dashicons-info'; ?>"></span>
		<div class="md4ai-llms-notice-content">
			<?php if ( $has_content ) : ?>
				<strong><?php esc_html_e( 'Custom llms.txt is active.', 'md4ai' ); ?></strong>
				<?php esc_html_e( 'This content will be served at', 'md4ai' ); ?>
				<a href="<?php echo esc_url( $llms_url ); ?>" target="_blank"><?php echo esc_html( $llms_url ); ?></a>
			<?php else : ?>
				<?php esc_html_e( 'This content will be served at', 'md4ai' ); ?>
				<a href="<?php echo esc_url( $llms_url ); ?>" target="_blank"><?php echo esc_html( $llms_url ); ?></a>.
				<strong><?php esc_html_e( 'Leave empty to use default content.', 'md4ai' ); ?></strong>
			<?php endif; ?>
		</div>
	</div>

	<form method="post">
		<?php wp_nonce_field( 'md4ai_update_llmstxt' ); ?>

		<div class="md4ai-llms-container">
			<!-- Editor Panel -->
			<div class="md4ai-llms-editor">
				<label for="llmstxt_content" class="md4ai-panel-title" style="display: block; margin-bottom: 8px; font-weight: 600; color: #1d2327;">
					<span class="dashicons dashicons-edit" style="margin-right: 4px;"></span>
					<?php esc_html_e( 'Editor', 'md4ai' ); ?>
				</label>
				<textarea
					id="llmstxt_content"
					name="llmstxt_content"
					class="md4ai-llms-textarea"
					placeholder="<?php echo esc_attr( $llms_txt_placeholder ); ?>"
				><?php echo esc_textarea( $llms_content ); ?></textarea>

				<div class="md4ai-toolbar-section">
					<div class="md4ai-toolbar-group">
						<?php
						echo wp_kses(
							Md4Ai_Utils::display_llmstxt_buttons( 'llmstxt_content', 'generate-llmstxt' ),
							array(
								'button' => array(
									'type'          => true,
									'class'         => true,
									'data-action'   => true,
									'data-endpoint' => true,
									'data-field'    => true,
								),
							)
						);
						?>
					</div>

					<span class="md4ai-toolbar-divider"></span>

					<div class="md4ai-toolbar-group md4ai-flex md4ai-justify-between">
						<button type="button" class="button md4ai-clear" data-field="llmstxt_content">
							<span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
							<?php esc_html_e( 'Clear', 'md4ai' ); ?>
						</button>
						<input type="submit"
								name="update_llmstxt"
								class="button button-primary"
								data-field="llmstxt_content"
								value="<?php esc_attr_e( 'Save Changes', 'md4ai' ); ?>">
					</div>

					<span id="md4ai-status"></span>
				</div>
			</div>

			<!-- Preview Panel -->
			<div class="md4ai-llms-preview">
				<label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1d2327;">
					<span class="dashicons dashicons-visibility" style="margin-right: 4px;"></span>
					<?php esc_html_e( 'Preview', 'md4ai' ); ?>
				</label>
				<div class="md4ai-preview-box">
					<div id="md4ai-preview-content">
						<div class="md4ai-preview-empty">
							<span class="dashicons dashicons-welcome-view-site"></span>
							<p style="margin: 0; font-size: 14px;">
								<?php esc_html_e( 'Preview will appear here', 'md4ai' ); ?>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
