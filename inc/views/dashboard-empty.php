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
	<!-- Welcome Section -->
	<div class="md4ai-welcome-section">
		<div class="md4ai-welcome-icon">
			<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2" viewBox="0 0 240 240"><path d="M0 0h240v240H0z" style="fill:url(#a)"/>
				<path d="M107.06 176.15H62.2a11.67 11.67 0 0 1-11.66-11.66V66c0-6.44 5.23-11.67 11.66-11.67h58.81c1.29 0 2.51.53 3.4 1.47l22.17 23.63c.8.87 1.25 2 1.25 3.18V97.9c-.47.08-.92.17-1.35.27-1.97.46-5.62 1.7-7.94 2.65V84.45L119 63.62H62.2a2.37 2.37 0 0 0-2.36 2.37v98.5c0 1.3 1.06 2.37 2.36 2.37h45.87a43.13 43.13 0 0 0-.85 5.44v.01c-.15 1.5-.2 2.77-.16 3.84Z" style="fill:#fff"/>
				<path d="M143.19 85.65h-15.16a7.02 7.02 0 0 1-7.02-7.01V60.45" style="fill:#fff"/>
				<path
					d="M143.19 81a4.65 4.65 0 0 1 0 9.3h-15.16a11.67 11.67 0 0 1-11.67-11.66V60.45a4.65 4.65 0 0 1 9.3 0v18.19c0 1.3 1.06 2.36 2.37 2.36h15.16ZM162.06 103.29a37.26 37.26 0 0 1 15.7 5.74 33.11 33.11 0 0 1 14.16 20.42 31.75 31.75 0 0 1-3.74 23.24l-1.32 2.28 1.4.44c3 .94 5.76 2.66 7.15 4.49 2.48 3.25 5.2 11.44 5.2 15.59-.02 2.2-1.23 4.04-3.06 4.57-1.38.42-1.95.4-3.04-.1-1.5-.69-1.98-1.7-2.91-6.22-1.36-6.57-2.4-9.1-4.2-10.2-1.03-.66-3.58-1.36-5.6-1.58-1.37-.15-1.37-.13-2.39.7-.55.47-1.38 1.09-1.85 1.4l-.8.54.68.36a9.1 9.1 0 0 1 3.53 3.79c1.06 2.25 1.44 4.76 1.46 9.93 0 3.97-.04 4.42-.42 5.08-.68 1.15-1.81 1.62-3.96 1.62-1.68 0-1.87-.05-2.61-.62-1.36-1.04-1.5-1.55-1.32-6.27.08-2.9.06-4.36-.13-4.96-.4-1.36-1.66-2.57-3.78-3.66l-1.92-1-2.4.54c-4.02.89-5.62 1.04-9.78.91-4.04-.1-6.36-.49-9.62-1.51l-1.57-.49-1.55.83c-2.47 1.36-3.49 2.19-4.08 3.4-.64 1.28-.7 2.38-.32 7.02.12 1.6.19 3.17.1 3.45-.21.85-1.34 1.97-2.36 2.38-2.1.78-4.87-.15-5.65-1.94-.34-.76-.43-1.64-.54-4.93-.17-5.28.09-7.49 1.1-9.61a12.86 12.86 0 0 1 3.94-4.43c.34-.21.6-.42.58-.5-.04-.1-.77-.7-1.62-1.37l-1.55-1.21-2.06.36c-2.39.4-4.66 1.13-5.7 1.85-1.79 1.2-3.15 4.74-3.5 9.04-.33 4.34-.42 4.89-1.01 5.78a3.95 3.95 0 0 1-3.55 1.81c-1.4-.02-2.26-.36-3.1-1.3-1.2-1.27-1.41-2.46-1.07-6.1.62-6.42 2.3-10.65 5.55-13.97a15.37 15.37 0 0 1 6.46-3.98l1.77-.55-.96-1.47a34.4 34.4 0 0 1-3.55-7.7 30.33 30.33 0 0 1-1.17-9.16c0-5.53.85-9.21 3.21-14.02 3.07-6.23 9.06-12.22 15.44-15.4 1.62-.8 5.96-2.31 8.06-2.8 3.77-.87 9.85-1.08 14.27-.51Zm-12.36 10.74c-8.71 1.08-15.8 5.76-19.03 12.57-1.27 2.66-1.55 3.91-1.66 7.42-.12 3.74.2 5.53 1.4 7.97a15.32 15.32 0 0 0 7.37 7.15 20 20 0 0 0 7.86 2.1c3.17.28 21.93.28 24.5-.02 4.47-.49 7.7-1.89 10.27-4.44 2.9-2.85 4.09-6.1 4.1-11.06 0-3.72-.59-6.36-2.14-9.46a18.79 18.79 0 0 0-4.13-5.55c-3.61-3.43-7.48-5.32-13.16-6.38-2.06-.4-12.9-.6-15.37-.3Zm22.5 11.14c4.73 2 6.38 9.34 3.17 13.95-1.66 2.36-3.87 3.51-6.5 3.36-1.88-.08-3.1-.66-4.72-2.12-2.77-2.51-3.56-6.79-2-10.9a9.68 9.68 0 0 1 3.46-4.05c1.6-.94 4.7-1.05 6.6-.24Zm-24.43.17c4.62 2.38 6 9.04 2.87 13.74-1.47 2.2-3.27 3.25-5.74 3.4a6.49 6.49 0 0 1-5.32-2.02c-2.42-2.23-3.38-5.36-2.7-8.78.56-2.85 1.77-4.8 3.77-6.06 1.27-.8 1.93-.96 4.04-.9 1.68.07 2.19.18 3.08.62Z"
					style="fill:#fff"/>
				<path d="M69.78 122.32V92.36h8.82l8.8 11.02 8.82-11.02h8.81v29.96h-8.81v-17.18l-8.81 11.02-8.81-11.02v17.18h-8.81ZM105.11 141.53l-17.14 15.6v-10.4H69.79v-10.39h18.18v-10.4l17.14 15.6Z" style="fill:#fff;fill-rule:nonzero"/>
			</svg>
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
			<div class="md4ai-tip-card-icon" style="background: linear-gradient(135deg, #FF9800 0%, #FF5722 100%);">
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
		<?php if ( ! $is_ai_enabled ) : ?>
		<div class="md4ai-tip-card md4ai-tip-card--highlight">
			<div class="md4ai-tip-card-icon" style="background: linear-gradient(135deg, #FF9800 0%, #E91E63 100%);">
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
		<?php else : ?>
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
				<?php if ( $is_ai_enabled ) : ?>
				<div class="md4ai-tip-card-cta">
					<a href="<?php echo esc_url( $geo_tab_url ); ?>" class="button button-secondary">
						<span class="dashicons dashicons-chart-pie"></span>
						<?php esc_html_e( 'Run Analysis', 'md4ai' ); ?>
					</a>
				</div>
				<?php else : ?>
				<p class="md4ai-tip-note">
					<span class="dashicons dashicons-lock"></span>
					<?php esc_html_e( 'Requires AI Services plugin', 'md4ai' ); ?>
				</p>
				<?php endif; ?>
			</div>
		</div>

	</div>
</div>
