<?php
/**
 * Cache View
 *
 * @package Md4Ai
 *
 * @var array $stats
 */
?>
<div id="cf7a-tab-panel md4ai-cache">
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
