<?php
/**
 * Dashboard Empty State View
 *
 * @var string $llms_tab_url
 * @var string $geo_tab_url
 * @var string $ai_services_url
 * @var bool $is_ai_enabled
 */
?>
<div id="md4ai-tab-panel md4ai-dashboard" class="md4ai-empty-state">
	<div class="md4ai-section-header">
		<h2 class="md4ai-section-title">
			<span class="dashicons dashicons-admin-generic"></span>
			<?php esc_html_e( 'Dashboard', 'md4ai' ); ?>
		</h2>
	</div>

	<!-- Welcome Section -->
	<div class="md4ai-welcome-section">
		<div class="md4ai-welcome-icon">
			<span class="dashicons dashicons-chart-bar"></span>
		</div>
		<h3><?php esc_html_e( 'Welcome to Md4AI!', 'md4ai' ); ?></h3>
		<p><?php esc_html_e( 'No bot visits recorded yet. Once AI crawlers and LLMs start visiting your site, you\'ll see detailed analytics here. In the meantime, explore these tips to get the most out of the plugin.', 'md4ai' ); ?></p>
	</div>

	<!-- Educational Cards Grid -->
	<div class="md4ai-tip-cards">

		<!-- Getting Started Card -->
		<div class="md4ai-tip-card">
			<div class="md4ai-tip-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
				<span class="dashicons dashicons-welcome-learn-more"></span>
			</div>
			<div class="md4ai-tip-card-content">
				<h4><?php esc_html_e( 'Getting Started', 'md4ai' ); ?></h4>
				<p><?php esc_html_e( 'Md4AI automatically serves optimized Markdown content to AI crawlers like GPTBot, Claude, and Perplexity. Your regular visitors see your normal HTML pages.', 'md4ai' ); ?></p>
				<ul class="md4ai-tip-list">
					<li><?php esc_html_e( 'AI bots are detected automatically by user agent', 'md4ai' ); ?></li>
					<li><?php esc_html_e( 'Content is converted to clean Markdown format', 'md4ai' ); ?></li>
					<li><?php esc_html_e( 'Visit stats appear here once bots start crawling', 'md4ai' ); ?></li>
				</ul>
			</div>
		</div>

		<!-- GEO Tips Card -->
		<div class="md4ai-tip-card">
			<div class="md4ai-tip-card-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
				<span class="dashicons dashicons-chart-area"></span>
			</div>
			<div class="md4ai-tip-card-content">
				<h4><?php esc_html_e( 'GEO Optimization Tips', 'md4ai' ); ?></h4>
				<p><?php esc_html_e( 'Generative Engine Optimization (GEO) helps AI models understand and cite your content better:', 'md4ai' ); ?></p>
				<ul class="md4ai-tip-list">
					<li><?php esc_html_e( 'Use clear headings and structured content', 'md4ai' ); ?></li>
					<li><?php esc_html_e( 'Include Q&A sections for common questions', 'md4ai' ); ?></li>
					<li><?php esc_html_e( 'Add author bios and cite credible sources', 'md4ai' ); ?></li>
					<li><?php esc_html_e( 'Keep content factual, fresh and well-organized', 'md4ai' ); ?></li>
					<li><?php esc_html_e( 'Use statistics and data to support claims', 'md4ai' ); ?></li>
				</ul>
			</div>
		</div>

		<!-- llms.txt Card -->
		<div class="md4ai-tip-card">
			<div class="md4ai-tip-card-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
				<span class="dashicons dashicons-media-text"></span>
			</div>
			<div class="md4ai-tip-card-content">
				<h4><?php esc_html_e( 'Create Your llms.txt', 'md4ai' ); ?></h4>
				<p><?php esc_html_e( 'The llms.txt file helps AI models understand your site\'s structure and important content. Think of it as robots.txt but for LLMs.', 'md4ai' ); ?></p>
				<ul class="md4ai-tip-list">
					<li><?php esc_html_e( 'Describe your site and its purpose', 'md4ai' ); ?></li>
					<li><?php esc_html_e( 'List key pages and their importance', 'md4ai' ); ?></li>
					<li><?php esc_html_e( 'Guide AI on what content to prioritize', 'md4ai' ); ?></li>
				</ul>
				<div class="md4ai-tip-card-cta">
					<a href="<?php echo esc_url( $llms_tab_url ); ?>" class="button button-secondary">
						<span class="dashicons dashicons-edit"></span>
						<?php esc_html_e( 'Edit llms.txt', 'md4ai' ); ?>
					</a>
				</div>
			</div>
		</div>

		<!-- AI Services Card (Conditional) -->
		<?php if ( ! $is_ai_enabled ): ?>
		<div class="md4ai-tip-card md4ai-tip-card--highlight">
			<div class="md4ai-tip-card-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
				<span class="dashicons dashicons-admin-plugins"></span>
			</div>
			<div class="md4ai-tip-card-content">
				<h4><?php esc_html_e( 'Enable AI Features', 'md4ai' ); ?></h4>
				<p><?php esc_html_e( 'Install the AI Services plugin to unlock powerful features:', 'md4ai' ); ?></p>
				<ul class="md4ai-tip-list">
					<li><?php esc_html_e( 'Auto-generate Q&A sections for your content', 'md4ai' ); ?></li>
					<li><?php esc_html_e( 'AI-powered llms.txt generation', 'md4ai' ); ?></li>
					<li><?php esc_html_e( 'Geo Insights: AI analysis of your site\'s GEO readiness', 'md4ai' ); ?></li>
				</ul>
				<p class="md4ai-tip-note">
					<span class="dashicons dashicons-info"></span>
					<?php esc_html_e( 'Some AI providers offer free API keys!', 'md4ai' ); ?>
				</p>
				<div class="md4ai-tip-card-cta">
					<a href="<?php echo esc_url( $ai_services_url ); ?>" target="_blank" class="button button-primary">
						<span class="dashicons dashicons-download"></span>
						<?php esc_html_e( 'Get AI Services Plugin', 'md4ai' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php else: ?>
		<div class="md4ai-tip-card md4ai-tip-card--success">
			<div class="md4ai-tip-card-icon" style="background: linear-gradient(135deg, #00c853 0%, #64dd17 100%);">
				<span class="dashicons dashicons-yes-alt"></span>
			</div>
			<div class="md4ai-tip-card-content">
				<h4><?php esc_html_e( 'AI Features Enabled!', 'md4ai' ); ?></h4>
				<p><?php esc_html_e( 'Great! You have AI Services installed. You can now:', 'md4ai' ); ?></p>
				<ul class="md4ai-tip-list">
					<li><?php esc_html_e( 'Generate Q&A sections with AI on any post', 'md4ai' ); ?></li>
					<li><?php esc_html_e( 'Auto-generate your llms.txt file', 'md4ai' ); ?></li>
					<li><?php esc_html_e( 'Run Geo Insights analysis on your site', 'md4ai' ); ?></li>
				</ul>
			</div>
		</div>
		<?php endif; ?>

		<!-- Geo Insights Card -->
		<div class="md4ai-tip-card">
			<div class="md4ai-tip-card-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
				<span class="dashicons dashicons-search"></span>
			</div>
			<div class="md4ai-tip-card-content">
				<h4><?php esc_html_e( 'Geo Insights Analysis', 'md4ai' ); ?></h4>
				<p><?php esc_html_e( 'Get an AI-powered evaluation of your website\'s GEO performance with scores for:', 'md4ai' ); ?></p>
				<ul class="md4ai-tip-list">
					<li><strong><?php esc_html_e( 'Authority', 'md4ai' ); ?></strong> - <?php esc_html_e( 'How trustworthy your content appears', 'md4ai' ); ?></li>
					<li><strong><?php esc_html_e( 'Relevance', 'md4ai' ); ?></strong> - <?php esc_html_e( 'How well content matches user intent', 'md4ai' ); ?></li>
					<li><strong><?php esc_html_e( 'Knowledge', 'md4ai' ); ?></strong> - <?php esc_html_e( 'Depth and accuracy of information', 'md4ai' ); ?></li>
				</ul>
				<?php if ( $is_ai_enabled ): ?>
				<div class="md4ai-tip-card-cta">
					<a href="<?php echo esc_url( $geo_tab_url ); ?>" class="button button-secondary">
						<span class="dashicons dashicons-chart-pie"></span>
						<?php esc_html_e( 'Run Analysis', 'md4ai' ); ?>
					</a>
				</div>
				<?php else: ?>
				<p class="md4ai-tip-note">
					<span class="dashicons dashicons-lock"></span>
					<?php esc_html_e( 'Requires AI Services plugin', 'md4ai' ); ?>
				</p>
				<?php endif; ?>
			</div>
		</div>

	</div>
</div>
