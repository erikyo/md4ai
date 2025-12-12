<?php
/**
 * Settings tab view
 *
 * @var string $output_format The current output format setting
 * @var string $extraction_mode The current extraction mode setting
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="md4ai-settings-page">
	<h2><?php esc_html_e( 'Plugin Settings', 'md4ai' ); ?></h2>
	
	<form method="post" action="">
		<?php wp_nonce_field( 'md4ai_update_settings' ); ?>
		
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="output_format"><?php esc_html_e( 'Output Format for AI Bots', 'md4ai' ); ?></label>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">
								<span><?php esc_html_e( 'Output Format', 'md4ai' ); ?></span>
							</legend>
							
							<label>
								<input type="radio" name="output_format" value="html" <?php checked( $output_format, 'html' ); ?>>
								<strong><?php esc_html_e( 'HTML Wrapped (Recommended)', 'md4ai' ); ?></strong>
							</label>
							<p class="description" style="margin-left: 25px; margin-top: 5px;">
								<?php esc_html_e( 'Wraps markdown content in HTML for better compatibility with all AI bots (ChatGPT, Claude, Perplexity, etc.). This format works with ChatGPT\'s browser tool which requires valid HTML.', 'md4ai' ); ?>
							</p>
							
							<br>
							
							<label>
								<input type="radio" name="output_format" value="markdown" <?php checked( $output_format, 'markdown' ); ?>>
								<strong><?php esc_html_e( 'Pure Markdown', 'md4ai' ); ?></strong>
							</label>
							<p class="description" style="margin-left: 25px; margin-top: 5px;">
								<?php esc_html_e( 'Serves pure markdown content with Content-Type: text/markdown header. Works well with Claude and other bots that can directly parse markdown, but may cause issues with ChatGPT\'s browser tool.', 'md4ai' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="extraction_mode"><?php esc_html_e( 'Content Extraction Mode', 'md4ai' ); ?></label>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">
								<span><?php esc_html_e( 'Extraction Mode', 'md4ai' ); ?></span>
							</legend>
							
							<label>
								<input type="radio" name="extraction_mode" value="advanced" <?php checked( $extraction_mode, 'advanced' ); ?>>
								<strong><?php esc_html_e( 'Advanced Mode (DOM Extractor) - Recommended', 'md4ai' ); ?></strong>
							</label>
							<p class="description" style="margin-left: 25px; margin-top: 5px;">
								<?php esc_html_e( 'Extracts complete content including schema markup, reviews, meta tags, FAQ, tables, structured data, and all rich content from your pages. Provides maximum information to AI bots for better understanding and indexing.', 'md4ai' ); ?>
							</p>
							
							<br>
							
							<label>
								<input type="radio" name="extraction_mode" value="standard" <?php checked( $extraction_mode, 'standard' ); ?>>
								<strong><?php esc_html_e( 'Standard Mode (Basic Extraction)', 'md4ai' ); ?></strong>
							</label>
							<p class="description" style="margin-left: 25px; margin-top: 5px;">
								<?php esc_html_e( 'Uses basic HTML to Markdown conversion. Extracts only the main post content, title, meta information, and basic formatting. Lighter and simpler output.', 'md4ai' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>
			</tbody>
		</table>
		
		<p class="submit">
			<input type="submit" name="update_settings" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'md4ai' ); ?>">
		</p>
	</form>
	
	<hr>
	
	<div class="md4ai-info-box" style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 12px 20px; margin-top: 20px;">
		<h3 style="margin-top: 0;">ℹ️ <?php esc_html_e( 'About Output Formats', 'md4ai' ); ?></h3>
		<p>
			<strong><?php esc_html_e( 'HTML Wrapped Format:', 'md4ai' ); ?></strong><br>
			<?php esc_html_e( 'The markdown content is embedded in a minimal HTML structure within a <pre> tag. This ensures compatibility with AI bots that use browser tools to fetch content, particularly ChatGPT. The markdown remains fully readable and parseable by the AI.', 'md4ai' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Pure Markdown Format:', 'md4ai' ); ?></strong><br>
			<?php esc_html_e( 'The original format that serves pure markdown with a text/markdown Content-Type header. This works perfectly with Claude and most AI crawlers, but ChatGPT\'s browser tool may encounter parsing errors.', 'md4ai' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Note:', 'md4ai' ); ?></strong>
			<?php esc_html_e( 'Changing this setting will clear the cache to ensure all bots receive content in the new format.', 'md4ai' ); ?>
		</p>
	</div>
</div>

<style>
.md4ai-settings-page h2 {
	margin-bottom: 20px;
}

.md4ai-settings-page fieldset {
	margin: 0;
}

.md4ai-settings-page label {
	display: block;
	margin-bottom: 10px;
}

.md4ai-settings-page input[type="radio"] {
	margin-right: 8px;
}

.md4ai-info-box p:last-child {
	margin-bottom: 0;
}
</style>
