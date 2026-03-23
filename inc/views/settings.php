<?php
/**
 * Settings View
 *
 * @package Md4Ai
 *
 * @var array $stats
 * @var string $default_service
 * @var string $default_model
 */
?>
<div id="cf7a-tab-panel" class="md4ai-settings">
	<div class="card">
		<h2><?php esc_html_e( 'AI Service Settings', 'md4ai' ); ?></h2>
		<p><?php esc_html_e( 'Select the default AI service and model to be used by the plugin.', 'md4ai' ); ?></p>
		<form method="post" id="md4ai-settings-form">
			<?php wp_nonce_field( 'md4ai_update_settings' ); ?>
			<input type="hidden" name="update_settings" value="1" />
			
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="md4ai-default-service"><?php esc_html_e( 'Default Service', 'md4ai' ); ?></label></th>
						<td>
							<select name="md4ai_default_service" id="md4ai-default-service" class="md4ai-settings-service-select" data-selected="<?php echo esc_attr( $default_service ); ?>">
								<option value=""><?php esc_html_e( 'Loading services...', 'md4ai' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="md4ai-default-model"><?php esc_html_e( 'Default Model', 'md4ai' ); ?></label></th>
						<td>
							<select name="md4ai_default_model" id="md4ai-default-model" class="md4ai-settings-model-select" data-selected="<?php echo esc_attr( $default_model ); ?>" disabled>
								<option value=""><?php esc_html_e( 'Select service first', 'md4ai' ); ?></option>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'md4ai' ); ?>">
			</p>
		</form>
	</div>

	<div class="card">
		<h2><?php esc_html_e( 'Cache Statistics', 'md4ai' ); ?></h2>
		<p><strong><?php esc_html_e( 'Cached Files:', 'md4ai' ); ?></strong> <?php echo esc_html( $stats['file_count'] ); ?></p>
		<p><strong><?php esc_html_e( 'Total Size:', 'md4ai' ); ?></strong> <?php echo esc_html( $stats['total_size_mb'] ); ?> MB</p>
		<p><strong><?php esc_html_e( 'Cache Directory:', 'md4ai' ); ?></strong> <code><?php echo esc_html( $stats['cache_dir'] ); ?></code></p>
	</div>

	<div class="card">
		<h2><?php esc_html_e( 'Clear Cache', 'md4ai' ); ?></h2>
		<p><?php esc_html_e( 'Clear all cached Markdown files. This will force regeneration on the next AI bot visit.', 'md4ai' ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'md4ai_clear_cache' ); ?>
			<input type="submit" name="clear_cache" class="button button-primary"
					value="<?php esc_attr_e( 'Clear All Cache', 'md4ai' ); ?>"
					onclick="return confirm('<?php esc_html_e( 'Are you sure you want to clear all cached files?', 'md4ai' ); ?>');">
		</form>
	</div>
</div>
