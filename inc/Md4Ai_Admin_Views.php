<?php

namespace Md4Ai;

class Md4Ai_Admin_Views {

	/**
	 * Cache instance
	 */
	private Md4Ai_Cache $cache;

	/**
	 * The placeholder for the llms.txt content
	 * @var string
	 */
	private string $llms_txt_placeholder;

	public function __construct($cache) {
		$this->cache = $cache;
		$this->llms_txt_placeholder = '## Title

> Optional description goes here

Optional details go here

## Section name

- [Link title](https://link_url): Optional link details

## Optional

- [Link title](https://link_url)';
	}

	private function get_tab_url( $tab ) {
		return add_query_arg( 'tab', $tab, menu_page_url( 'md4ai', false ) );
	}

	private function render_tabs() {
		$tabs = [
			'dashboard' => 'Dashboard',
			'llms-txt' => 'llms.txt',
			'cache'    => 'Cache',
		];

		// Check if 'tab' is present in the GET request.
		$nonce_action = 'cf7a_admin_tab_switch';

		$active_tab = array_key_first($tabs);

		if ( isset( $_GET['tab'] ) ) {
			if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ '_wpnonce' ] ) ), $nonce_action ) ) {
				$active_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
			}
		}
		?>
		<div class="md4ai-tabs">
			<ul class="md4ai-nav-tab-wrapper">
				<?php
					// loop for each tab
				foreach ( $tabs as $slug => $tab) {
					$tab_active = $active_tab === $slug ? 'nav-tab-active' : '';
					printf('<li class="md4ai-nav-tab tab-%s %s"><a href="%s">%s</a></li>',
						esc_attr($slug),
						esc_attr($tab_active),
						esc_url( wp_nonce_url( $this->get_tab_url($slug), $nonce_action)),
						$tab
					);
				}
				?>
			</ul>
			<div class="md4ai-tab-content">
				<?php
				if ( $active_tab == 'dashboard' ) {
					$this->render_tab_dashboard();
				} else if ( $active_tab == 'llms-txt' ) {
					$this->render_tab_llms_txt();
				} else if ( $active_tab == 'cache' ) {
					$this->render_tab_cache();
				} ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the admin page
	 *
	 * @param Md4Ai_Admin $instance
	 */
	public function render_admin_page() {
		// Handle action cache clear request
		if ( isset( $_POST['clear_cache'] ) && check_admin_referer( 'md4ai_clear_cache' ) ) {
			$this->cache->clear_all_cache();
			printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html__( 'Cache cleared successfully!', 'md4ai' ) );
		}

		// Handle action llms.txt update
		if ( isset( $_POST['update_llmstxt'] ) && check_admin_referer( 'md4ai_update_llmstxt' ) ) {
			if ( isset( $_POST['llmstxt_content'] ) ) {
				$options = get_option( MD4AI_OPTION );
				$options['llms_content'] = sanitize_textarea_field( wp_unslash( $_POST['llmstxt_content'] ) );
				update_option( MD4AI_OPTION, $options );
				printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html__( 'llms.txt updated successfully!', 'md4ai' ) );
			}
		}
		?>
		<div class="wrap md4ai-admin">
			<h1><?php esc_html_e( 'md4AI', 'md4ai' ); ?></h1>
			<?php $this->render_tabs(); ?>
		</div>
		<?php
	}

	private function render_tab_dashboard() {
		$options = get_option( MD4AI_OPTION );
		$analytics = isset($options['requests']) ? $options['requests'] : [];

		// Prepara i dati per i grafici
		$stats = self::prepare_dashboard_stats($analytics);
		?>
		<div id="md4ai-tab-panel md4ai-dashboard">
			<div class="md4ai-section-header">
				<h2 class="md4ai-section-title">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php esc_html_e( 'Dashboard', 'md4ai' ); ?>
				</h2>
			</div>

			<!-- Status Alerts -->
			<div class="md4ai-alerts">
				<?php if ( Md4Ai_Utils::is_ai_service_enabled() ): ?>
					<div class="notice notice-success inline">
						<p><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'AI services are enabled!', 'md4ai' ); ?></p>
					</div>
				<?php else: ?>
					<div class="notice notice-warning inline">
						<p><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'AI services are not enabled. Please configure them in Settings.', 'md4ai' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<!-- Statistics Cards -->
			<div class="md4ai-stats-grid">
				<div class="md4ai-stat-card">
					<div class="stat-icon" style="background: #2271b1;">
						<span class="dashicons dashicons-chart-line"></span>
					</div>
					<div class="stat-content">
						<h3><?php echo esc_html($stats['total_requests']); ?></h3>
						<p><?php esc_html_e('Total Requests', 'md4ai'); ?></p>
						<span class="stat-period"><?php esc_html_e('Last 7 days', 'md4ai'); ?></span>
					</div>
				</div>

				<div class="md4ai-stat-card">
					<div class="stat-icon" style="background: #00a32a;">
						<span class="dashicons dashicons-admin-users"></span>
					</div>
					<div class="stat-content">
						<h3><?php echo esc_html($stats['unique_crawlers']); ?></h3>
						<p><?php esc_html_e('Unique Crawlers', 'md4ai'); ?></p>
						<span class="stat-period"><?php esc_html_e('Different bots', 'md4ai'); ?></span>
					</div>
				</div>

				<div class="md4ai-stat-card">
					<div class="stat-icon" style="background: #d63638;">
						<span class="dashicons dashicons-admin-post"></span>
					</div>
					<div class="stat-content">
						<h3><?php echo esc_html($stats['unique_posts']); ?></h3>
						<p><?php esc_html_e('Posts Indexed', 'md4ai'); ?></p>
						<span class="stat-period"><?php esc_html_e('Total posts', 'md4ai'); ?></span>
					</div>
				</div>

				<div class="md4ai-stat-card">
					<div class="stat-icon" style="background: #f0a800;">
						<span class="dashicons dashicons-calendar-alt"></span>
					</div>
					<div class="stat-content">
						<h3><?php echo esc_html($stats['today_requests']); ?></h3>
						<p><?php esc_html_e('Today\'s Requests', 'md4ai'); ?></p>
						<span class="stat-period"><?php echo esc_html(date('d M Y')); ?></span>
					</div>
				</div>
			</div>

			<!-- Charts Section -->
			<div class="md4ai-charts-container">
				<div class="md4ai-chart-box">
					<h3><?php esc_html_e('Requests per Day', 'md4ai'); ?></h3>
					<canvas id="md4ai-requests-chart" width="300" height="200"></canvas>
				</div>

				<div class="md4ai-chart-box">
					<h3><?php esc_html_e('Requests by Crawler', 'md4ai'); ?></h3>
					<canvas id="md4ai-crawlers-chart" width="300" height="200"></canvas>
				</div>
			</div>

			<!-- Top Posts Table -->
			<div class="md4ai-table-container">
				<h3><?php esc_html_e('Most Indexed Posts', 'md4ai'); ?></h3>
				<table class="wp-list-table widefat fixed striped">
					<thead>
					<tr>
						<th><?php esc_html_e('Post Title', 'md4ai'); ?></th>
						<th><?php esc_html_e('Total Hits', 'md4ai'); ?></th>
						<th><?php esc_html_e('Last Crawled', 'md4ai'); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php if (!empty($stats['top_posts'])): ?>
						<?php foreach ($stats['top_posts'] as $post_stat): ?>
							<tr>
								<td>
									<strong>
										<a href="<?php echo get_edit_post_link($post_stat['post_id']); ?>">
											<?php echo esc_html(get_the_title($post_stat['post_id'])); ?>
										</a>
									</strong>
								</td>
								<td><?php echo esc_html($post_stat['count']); ?></td>
								<td><?php echo esc_html(human_time_diff($post_stat['last_crawled'], current_time('timestamp'))); ?> ago</td>
							</tr>
						<?php endforeach; ?>
					<?php else: ?>
						<tr>
							<td colspan="3" style="text-align: center;">
								<?php esc_html_e('No data available yet', 'md4ai'); ?>
							</td>
						</tr>
					<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Recent Activity -->
			<div class="md4ai-table-container">
				<h3><?php esc_html_e('Recent Crawler Activity', 'md4ai'); ?></h3>
				<table class="wp-list-table widefat fixed striped">
					<thead>
					<tr>
						<th><?php esc_html_e('Time', 'md4ai'); ?></th>
						<th><?php esc_html_e('Crawler', 'md4ai'); ?></th>
						<th><?php esc_html_e('Post', 'md4ai'); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php if (!empty($stats['recent_activity'])): ?>
						<?php foreach ($stats['recent_activity'] as $activity): ?>
							<tr>
								<td><?php echo esc_html(human_time_diff($activity['timestamp'], current_time('timestamp'))); ?> ago</td>
								<td>
                                    <span class="md4ai-crawler-badge">
                                        <?php echo esc_html($activity['user_agent']); ?>
                                    </span>
								</td>
								<td>
									<a href="<?php echo get_edit_post_link($activity['post_id']); ?>">
										<?php echo esc_html(get_the_title($activity['post_id'])); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else: ?>
						<tr>
							<td colspan="3" style="text-align: center;">
								<?php esc_html_e('No recent activity', 'md4ai'); ?>
							</td>
						</tr>
					<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Prepare the dashboard stats
	 *
	 * @param array $analytics The analytics data
	 *
	 * @return array The stats
	 */
	public static function prepare_dashboard_stats( array $analytics): array {
		$stats = [
			'total_requests' => 0,
			'unique_crawlers' => 0,
			'unique_posts' => 0,
			'today_requests' => 0,
			'top_posts' => [],
			'recent_activity' => [],
			'chart_data' => [
				'dates' => [],
				'requests_per_day' => [],
				'crawler_labels' => [],
				'crawler_counts' => []
			]
		];

		if (empty($analytics)) {
			return $stats;
		}

		// 7 days
		$last_7_days = [];
		for ($i = 6; $i >= 0; $i--) {
			$date = date('Y-m-d', strtotime("-$i days"));
			$last_7_days[$date] = 0;
		}

		$unique_crawlers = [];
		$all_posts = [];
		$post_hits = [];
		$recent = [];
		$crawler_counts = [];

		// Data parse
		foreach ($analytics as $date => $requests) {
			if (!is_array($requests)) continue;

			foreach ($requests as $request) {
				$stats['total_requests']++;

				// Last 7 days count
				if (isset($last_7_days[$date])) {
					$last_7_days[$date]++;
				}

				// Today
				if ($date === date('Y-m-d')) {
					$stats['today_requests']++;
				}

				// Unique Crawler
				$crawler = $request['user_agent'];
				if (!in_array($crawler, $unique_crawlers)) {
					$unique_crawlers[] = $crawler;
				}

				// Crawler counts
				if (!isset($crawler_counts[$crawler])) {
					$crawler_counts[$crawler] = 0;
				}
				$crawler_counts[$crawler]++;

				// Uniques posts
				$post_id = $request['post_id'];
				if (!in_array($post_id, $all_posts)) {
					$all_posts[] = $post_id;
				}

				if (!isset($post_hits[$post_id])) {
					$post_hits[$post_id] = [
						'count' => 0,
						'last_crawled' => 0
					];
				}
				$post_hits[$post_id]['count']++;
				$post_hits[$post_id]['last_crawled'] = max($post_hits[$post_id]['last_crawled'], $request['timestamp']);

				// Attività recente
				$recent[] = $request;
			}
		}

		$stats['unique_crawlers'] = count($unique_crawlers);
		$stats['unique_posts'] = count($all_posts);

		// Prepare chart data
		$stats['chart_data']['dates'] = array_keys($last_7_days);
		$stats['chart_data']['requests_per_day'] = array_values($last_7_days);

		// Top 5 crawler
		$crawler_counts = [];
		foreach ($analytics as $date => $requests) {
			if (!is_array($requests)) continue;
			foreach ($requests as $request) {
				$crawler = $request['user_agent'];
				if (!isset($crawler_counts[$crawler])) {
					$crawler_counts[$crawler] = 0;
				}
				$crawler_counts[$crawler]++;
			}
		}

		arsort($crawler_counts);
		$top_crawlers = array_slice($crawler_counts, 0, 5, true);
		$stats['chart_data']['crawler_labels'] = array_keys($top_crawlers);
		$stats['chart_data']['crawler_counts'] = array_values($top_crawlers);

		// Top 10 post
		uasort($post_hits, function($a, $b) {
			return $b['count'] - $a['count'];
		});
		$top_posts = array_slice($post_hits, 0, 10, true);
		foreach ($top_posts as $post_id => $data) {
			$stats['top_posts'][] = [
				'post_id' => $post_id,
				'count' => $data['count'],
				'last_crawled' => $data['last_crawled']
			];
		}

		// Last 10 attività
		usort($recent, function($a, $b) {
			return $b['timestamp'] - $a['timestamp'];
		});
		$stats['recent_activity'] = array_slice($recent, 0, 10);

		return $stats;
	}

	public function render_tab_llms_txt() {
		$options = get_option( MD4AI_OPTION );
		if (empty($options)) {
			$llms_content = '';
		} else {
			$llms_content = $options['llms_content'];
		}
		$llms_url     = home_url( '/llms.txt' );
		$has_content  = ! empty( $llms_content );
		?>
		<div id="cf7a-tab-panel md4ai-llms-txt">
			<div class="md4ai-section-header">
				<h2 class="md4ai-section-title">
					<span class="dashicons dashicons-media-text"></span>
					<?php esc_html_e( 'llms.txt', 'md4ai' ); ?>
				</h2>
				<a href="<?php echo esc_url( $llms_url ); ?>" target="_blank" class="button">
					<span class="dashicons dashicons-external" style="margin-top: 3px;"></span>
					<?php esc_html_e( 'View llms.txt', 'md4ai' ); ?>
				</a>
			</div>

			<div class="md4ai-llms-notice <?php echo $has_content ? 'success' : ''; ?>">
				<span class="md4ai-llms-notice-icon dashicons <?php echo $has_content ? 'dashicons-yes-alt' : 'dashicons-info'; ?>"></span>
				<div class="md4ai-llms-notice-content">
					<?php if ( $has_content ): ?>
						<strong><?php esc_html_e( 'Custom llms.txt is active.', 'md4ai' ); ?></strong>
						<?php esc_html_e( 'This content will be served at', 'md4ai' ); ?>
						<a href="<?php echo esc_url( $llms_url ); ?>" target="_blank"><?php echo esc_html( $llms_url ); ?></a>
					<?php else: ?>
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
							placeholder="<?php echo esc_attr( $this->llms_txt_placeholder ); ?>"
						><?php echo esc_textarea( $llms_content ); ?></textarea>

						<div class="md4ai-toolbar-section">
							<div class="md4ai-toolbar-group">
								<?php
								echo wp_kses( Md4Ai_Utils::display_llmstxt_buttons( 'llmstxt_content', true, 'generate-llmstxt' ), [
									'button' => [
										'type'          => true,
										'class'         => true,
										'data-action'   => true,
										'data-endpoint' => true,
										'data-field'    => true,
									],
								] );
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
		<?php
	}

	public function render_tab_cache() {
		// Get cache statistics
		$stats = $this->cache->get_statistics();
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
		<?php
	}

}
