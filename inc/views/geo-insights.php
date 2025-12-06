<?php
/**
 * Geo Insights View
 *
 * @var bool $is_woo_active
 * @var string $site_domain
 * @var string $site_url
 * @var string $theme_screenshot
 * @var WP_Theme $theme
 */
?>
<div class="wrap geo-insights-wrapper" data-woo-active="<?php echo $is_woo_active ? 'true' : 'false'; ?>" data-site-domain="<?php echo esc_attr( $site_domain ); ?>">

	<!-- Header Section -->
	<div class="geo-header-section">
		<div class="geo-header-content">
			<div class="geo-header-title">
				<span class="dashicons dashicons-chart-pie"></span>
				<div>
					<h1><?php esc_html_e( 'Geo Insights', 'md4ai' ); ?></h1>
					<p class="geo-subtitle"><?php esc_html_e( 'Analyze how AI systems perceive your website', 'md4ai' ); ?></p>
				</div>
			</div>
			<div class="geo-score-legend">
				<div class="legend-item legend-good">
					<span class="legend-dot"></span>
					<span class="legend-text"><?php esc_html_e( '90-100', 'md4ai' ); ?></span>
				</div>
				<div class="legend-item legend-average">
					<span class="legend-dot"></span>
					<span class="legend-text"><?php esc_html_e( '50-89', 'md4ai' ); ?></span>
				</div>
				<div class="legend-item legend-poor">
					<span class="legend-dot"></span>
					<span class="legend-text"><?php esc_html_e( '0-49', 'md4ai' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- Site Preview & Analyze Section -->
	<div class="geo-analyze-section">
		<div class="geo-site-preview">
			<?php if ( $theme_screenshot ): ?>
				<img src="<?php echo esc_url( $theme_screenshot ); ?>" alt="<?php echo esc_attr( $theme->get('Name') ); ?>" class="geo-theme-screenshot">
			<?php else: ?>
				<div class="geo-screenshot-fallback">
					<span class="dashicons dashicons-admin-appearance"></span>
					<span><?php echo esc_html( $theme->get('Name') ); ?></span>
				</div>
			<?php endif; ?>
		</div>
		<div class="geo-analyze-form">
			<label for="geo-url-input" class="screen-reader-text"><?php esc_html_e( 'Website URL', 'md4ai' ); ?></label>
			<div class="geo-search-wrapper">
				<div class="geo-url-input-wrapper">
					<span class="dashicons dashicons-admin-site"></span>
					<input type="text" id="geo-url-input" class="geo-url-input" value="<?php echo esc_url( $site_url ); ?>" placeholder="<?php echo esc_attr( $site_url ); ?>">
				</div>
				<p class="geo-url-hint"><?php printf(
					/* translators: %s is the site domain */
						esc_html__( 'Analysis is restricted to %s', 'md4ai' ), '<strong>' . esc_html( $site_domain ) . '</strong>'
					); ?></p>
			</div>
			<button id="btn-start-analysis" class="geo-analyze-button">
				<span class="dashicons dashicons-superhero"></span>
				<?php esc_html_e( 'Analyze', 'md4ai' ); ?>
			</button>
		</div>

		<div class="geo-service-selection">
			<div class="geo-select-wrapper" style="flex: 1;">
				<label for="geo-service-select" class="screen-reader-text"><?php esc_html_e( 'Select AI Service', 'md4ai' ); ?></label>
				<select id="geo-service-select" class="geo-select" disabled>
					<option value=""><?php esc_html_e( 'Loading services...', 'md4ai' ); ?></option>
				</select>
			</div>
			<div class="geo-select-wrapper" style="flex: 1;">
				<label for="geo-model-select" class="screen-reader-text"><?php esc_html_e( 'Select Model', 'md4ai' ); ?></label>
				<select id="geo-model-select" class="geo-select" disabled>
					<option value=""><?php esc_html_e( 'Select service first', 'md4ai' ); ?></option>
				</select>
			</div>
		</div>
	</div>

	<!-- Loading State -->
	<div id="geo-loading" style="display:none;">
		<div class="geo-loading-content">
			<h3><?php esc_html_e( 'Analyzing your website...', 'md4ai' ); ?></h3>
			<p class="geo-loading-step" id="geo-loading-step"><?php esc_html_e( 'Connecting to AI service', 'md4ai' ); ?></p>
			<div class="geo-loading-progress">
				<div class="geo-loading-progress-bar"></div>
			</div>
		</div>
	</div>

	<!-- Results Section -->
	<div id="geo-results" style="display:none;">

		<!-- Core GEO Scores (4 circular gauges) -->
		<div class="geo-core-scores">
			<h2 class="geo-section-title">
				<span class="dashicons dashicons-performance"></span>
				<?php esc_html_e( 'Core GEO Metrics', 'md4ai' ); ?>
			</h2>
			<div class="geo-scores-container">
				<div class="geo-gauge-card" data-metric="authority">
					<div class="single-chart">
						<svg viewBox="0 0 36 36" class="circular-chart orange">
							<circle class="circle-bg" cx="18" cy="18" r="15.9155" />
							<circle class="circle" stroke-dasharray="0, 100" cx="18" cy="18" r="15.9155" />
							<text x="18" y="20.35" class="percentage">0</text>
						</svg>
					</div>
					<span class="gauge-label"><?php esc_html_e( 'Authority', 'md4ai' ); ?></span>
					<span class="gauge-description"><?php esc_html_e( 'Trustworthiness', 'md4ai' ); ?></span>
				</div>

				<div class="geo-gauge-card" data-metric="relevance">
					<div class="single-chart">
						<svg viewBox="0 0 36 36" class="circular-chart green">
							<circle class="circle-bg" cx="18" cy="18" r="15.9155" />
							<circle class="circle" stroke-dasharray="0, 100" cx="18" cy="18" r="15.9155" />
							<text x="18" y="20.35" class="percentage">0</text>
						</svg>
					</div>
					<span class="gauge-label"><?php esc_html_e( 'Relevance', 'md4ai' ); ?></span>
					<span class="gauge-description"><?php esc_html_e( 'Content match', 'md4ai' ); ?></span>
				</div>

				<div class="geo-gauge-card" data-metric="knowledge">
					<div class="single-chart">
						<svg viewBox="0 0 36 36" class="circular-chart blue">
							<circle class="circle-bg" cx="18" cy="18" r="15.9155" />
							<circle class="circle" stroke-dasharray="0, 100" cx="18" cy="18" r="15.9155" />
							<text x="18" y="20.35" class="percentage">0</text>
						</svg>
					</div>
					<span class="gauge-label"><?php esc_html_e( 'Knowledge', 'md4ai' ); ?></span>
					<span class="gauge-description"><?php esc_html_e( 'Data depth', 'md4ai' ); ?></span>
				</div>

				<div class="geo-gauge-card" data-metric="crawler">
					<div class="single-chart">
						<svg viewBox="0 0 36 36" class="circular-chart purple">
							<circle class="circle-bg" cx="18" cy="18" r="15.9155" />
							<circle class="circle" stroke-dasharray="0, 100" cx="18" cy="18" r="15.9155" />
							<text x="18" y="20.35" class="percentage">0</text>
						</svg>
					</div>
					<span class="gauge-label"><?php esc_html_e( 'Crawlability', 'md4ai' ); ?></span>
					<span class="gauge-description"><?php esc_html_e( 'Bot accessibility', 'md4ai' ); ?></span>
				</div>
			</div>
		</div>

		<!-- Core Web Vitals-style metrics will be injected by JS -->
		<div id="geo-vitals-container"></div>

		<!-- Opportunities & Diagnostics will be injected by JS -->
		<div id="geo-opportunities-container"></div>

		<!-- Detailed Report will be injected by JS -->
		<div id="geo-report-container"></div>
	</div>
</div>
