<?php
/**
 * Dashboard View
 *
 * @var array $stats
 * @var array $traffic_stats
 */
?>
<div id="md4ai-tab-panel md4ai-dashboard">
	<div class="md4ai-section-header">
		<h2 class="md4ai-section-title">
			<span class="dashicons dashicons-admin-generic"></span>
			<?php esc_html_e( 'Dashboard', 'md4ai' ); ?>
		</h2>
	</div>

	<div class="md4ai-alerts">
		<?php if ( Md4Ai_Utils::is_ai_service_enabled() ): ?>
			<div class="notice notice-success inline">
				<p><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'AI services are enabled!', 'md4ai' ); ?></p>
			</div>
		<?php else: ?>
			<div class="notice notice-warning inline">
				<p><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'AI services plugin is not installed or not active. Please install and activate it to use the "Generate with AI" and "Geo-Insights" feature.', 'md4ai' ); ?></p>
			</div>
		<?php endif; ?>
	</div>

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
				<span class="stat-period"><?php echo esc_html(gmdate('d M Y')); ?></span>
			</div>
		</div>
	</div>

	<div class="md4ai-charts-container">
		<div class="md4ai-chart-box">
			<h3><?php esc_html_e('Requests per Day', 'md4ai'); ?></h3>
			<div class="chartjs-wrapper">
				<canvas id="md4ai-requests-chart" height="400" width="600"></canvas>
			</div>
		</div>

		<div class="md4ai-chart-box">
			<h3><?php esc_html_e('Requests by Crawler', 'md4ai'); ?></h3>
			<div class="chartjs-wrapper">
				<canvas id="md4ai-crawlers-chart" height="150" width="400"></canvas>
			</div>
		</div>
	</div>

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
								<a href="<?php echo esc_url(get_edit_post_link($post_stat['post_id'])); ?>">
									<?php echo esc_html(get_the_title($post_stat['post_id'])); ?>
								</a>
							</strong>
						</td>
						<td><?php echo esc_html($post_stat['count']); ?></td>
						<td>
							<?php
							// Format: 2024-01-01 14:30 (2 days ago)
							$date_format = wp_date('Y-m-d H:i', $post_stat['last_crawled']);
							$time_diff = human_time_diff($post_stat['last_crawled'], current_time('timestamp'));
							echo esc_html(sprintf('%s (%s ago)', $date_format, $time_diff));
							?>
						</td>
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

	<div class="md4ai-table-container">
		<h3><?php esc_html_e('Recent Crawler Activity', 'md4ai'); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
			<tr>
				<th><?php esc_html_e('Crawler', 'md4ai'); ?></th>
				<th><?php esc_html_e('Post', 'md4ai'); ?></th>
				<th><?php esc_html_e('Date', 'md4ai'); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php if (!empty($stats['recent_activity'])): ?>
				<?php foreach ($stats['recent_activity'] as $activity): ?>
					<tr>
						<td>
							<span class="md4ai-crawler-badge">
								<?php echo esc_html($activity['user_agent']); ?>
							</span>
						</td>
						<td>
							<a href="<?php echo esc_url(get_edit_post_link($activity['post_id'])); ?>">
								<?php echo esc_html(get_the_title($activity['post_id'])); ?>
							</a>
						</td>
						<td>
							<?php
							// Format: 2024-01-01 14:30 (2 mins ago)
							$date_format = wp_date('Y-m-d H:i', $activity['timestamp']);
							$time_diff = human_time_diff($activity['timestamp'], current_time('timestamp'));
							echo esc_html(sprintf('%s (%s ago)', $date_format, $time_diff));
							?>
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

	<h2 style="margin-top: 30px;">📊 Traffic Insights</h2>

	<div class="md4ai-charts-container">
		<div class="md4ai-chart-box">
			<h3><?php esc_html_e('Search/LLM Referrals per Day', 'md4ai'); ?></h3>
			<div class="chartjs-wrapper">
				<canvas id="md4ai-referrals-chart" height="400" width="600"></canvas>
			</div>
		</div>
		<div class="md4ai-chart-box">
			<h3><?php esc_html_e('Source Distribution', 'md4ai'); ?></h3>
			<div class="chartjs-wrapper">
				<canvas id="md4ai-source-chart" height="150" width="400"></canvas>
			</div>
		</div>
	</div>

	<div class="traffic-insights-container">
		<div class="traffic-table-wrapper" style="width: 100%;">
			<h3>Latest Views</h3>
			<table class="wp-list-table widefat fixed striped traffic-table">
				<thead>
				<tr>
					<th><?php esc_html_e('Source', 'md4ai'); ?></th>
					<th><?php esc_html_e('Search Terms', 'md4ai'); ?></th>
					<th><?php esc_html_e('Date', 'md4ai'); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php if (!empty($traffic_stats['latest_views'])): ?>
					<?php foreach ($traffic_stats['latest_views'] as $view): ?>
						<tr>
							<td><?php echo esc_html($view['source']); ?></td>
							<td><?php echo esc_html($view['search_terms'] ?: 'N/A'); ?></td>
							<td>
								<?php
								// Format: 2024-01-01 14:30 (2 hours ago)
								$date_format = wp_date('Y-m-d H:i', $view['date_recorded']);
								$time_diff = human_time_diff($view['date_recorded'], current_time('timestamp'));
								echo esc_html(sprintf('%s (%s ago)', $date_format, $time_diff));
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr>
						<td colspan="3" style="text-align: center;">
							<?php esc_html_e('No visitor data available yet', 'md4ai'); ?>
						</td>
					</tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
