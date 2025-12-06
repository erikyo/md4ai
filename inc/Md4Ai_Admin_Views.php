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

	/**
	 * Returns the URL for a specific tab
	 *
	 * @param string $tab The tab slug
	 *
	 * @return string The tab URL
	 */
	private function get_tab_url( $tab ) {
		return add_query_arg( 'tab', $tab, menu_page_url( 'md4ai', false ) );
	}

	/**
	 * Renders the tabs
	 *
	 * @param Md4AI_Admin $instance
	 *
	 * @return void
	 */
	private function render_tabs() {
		$tabs = [
			'dashboard' => 'Dashboard',
			'llms-txt' => 'llms.txt',
			'cache'    => 'Cache',
			'geo-insights' => 'Geo Insights',
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
						esc_html($tab)
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
				} else if ( $active_tab == 'geo-insights' ) {
					$this->render_geo_insights_page();
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
			<h1><?php esc_html_e( 'Md4AI', 'md4ai' ); ?></h1>
			<?php $this->render_tabs(); ?>
		</div>
		<?php
	}

	/**
	 * Prepares the visitor data for the Traffic Insights dashboard
	 *
	 * @param array $visitors The raw visitors data from the database.
	 *
	 * @return array The processed traffic statistics.
	 */
	public static function prepare_traffic_stats($visitors) {
		$source_counts = [];
		$referral_counts_per_day = [];

		if (empty($visitors)) {
			return [
				'source_counts' => [],
				'referral_chart_data' => ['dates' => [], 'data' => []],
				'latest_views' => []
			];
		}

		$latest_views = [];

		foreach ($visitors as $week_date => $daily_visits) {
			if (!is_array($daily_visits)) continue;

			foreach ($daily_visits as $visit) {
				// 1. Source Counts (for Pie Chart)
				$source = $visit['source'];
				$source_counts[$source] = ($source_counts[$source] ?? 0) + 1;

				// 2. Referral Counts per Day (for Bar Chart)
				$date_str = gmdate('Y-m-d', $visit['date_recorded']);
				if (stripos($source, 'search: ') === 0 || stripos($source, 'llm: ') === 0) {
					$referral_counts_per_day[$date_str] = ($referral_counts_per_day[$date_str] ?? 0) + 1;
				}

				// 3. Latest Views (for Table)
				$latest_views[] = $visit;
			}
		}

		// Sort latest views by timestamp descending and take top 10
		usort($latest_views, function($a, $b) {
			return $b['date_recorded'] - $a['date_recorded'];
		});
		$latest_views = array_slice($latest_views, 0, 10);

		// Prepare chart data for last 7 days for the new bar chart
		$last_7_days = [];
		for ($i = 6; $i >= 0; $i--) {
			$date = gmdate('Y-m-d', strtotime("-$i days"));
			$last_7_days[$date] = $referral_counts_per_day[$date] ?? 0;
		}

		return [
			'source_counts' => $source_counts,
			'referral_chart_data' => [
				'dates' => array_keys($last_7_days),
				'data' => array_values($last_7_days)
			],
			'latest_views' => $latest_views
		];
	}

	/**
	 * Renders the dashboard page
	 *
	 * @param Md4AI_Admin $instance
	 *
	 * @return void
	 */
	private function render_tab_dashboard() {
		$options = get_option( MD4AI_OPTION );
		$analytics = $options['requests'] ?? [];
		$visitors = $options['visitors'] ?? [];

		// If no data yet, show educational empty state
		$has_data = !empty($analytics) || !empty($visitors);
		if (!$has_data) {
			$this->render_dashboard_empty_state();
			return;
		}

		// Prepare stats
		$md4ai_stats = self::prepare_dashboard_stats($analytics);
		$md4ai_traffic_stats = self::prepare_traffic_stats($visitors);
		include __DIR__ . '/views/dashboard.php';

	}

	/**
	 * Renders the dashboard empty state with educational cards
	 * Shown when no bot visits have been recorded yet
	 *
	 * @return void
	 */
	private function render_dashboard_empty_state() {
		$llms_tab_url = wp_nonce_url( $this->get_tab_url( 'llms-txt' ), 'cf7a_admin_tab_switch' );
		$geo_tab_url = wp_nonce_url( $this->get_tab_url( 'geo-insights' ), 'cf7a_admin_tab_switch' );
		$ai_services_url = 'https://wordpress.org/plugins/ai-services/';
		$is_ai_enabled = Md4Ai_Utils::is_ai_service_enabled();
		include __DIR__ . '/views/dashboard-empty.php';

	}

	/**
	 * Prepare the dashboard stats
	 *
	 * @param array $analytics The analytics data
	 *
	 * @return array The stats
	 */
	public static function prepare_dashboard_stats($analytics) {
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
			$date = gmdate('Y-m-d', strtotime("-$i days"));
			$last_7_days[$date] = 0;
		}

		$unique_crawlers = [];
		$all_posts = [];
		$post_hits = [];
		$recent = [];
		$crawler_counts = [];

		// parse the data
		foreach ($analytics as $week_date => $requests) {
			if (!is_array($requests)) continue;

			foreach ($requests as $request) {
				$stats['total_requests']++;

				// get the real date
				$actual_date = gmdate('Y-m-d', $request['timestamp']);

				// day count (last 7 days)
				if (isset($last_7_days[$actual_date])) {
					$last_7_days[$actual_date]++;
				}

				// Today requests
				if ($actual_date === gmdate('Y-m-d')) {
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

				// Post hits
				if (!isset($post_hits[$post_id])) {
					$post_hits[$post_id] = [
						'count' => 0,
						'last_crawled' => 0
					];
				}
				$post_hits[$post_id]['count']++;
				$post_hits[$post_id]['last_crawled'] = max($post_hits[$post_id]['last_crawled'], $request['timestamp']);

				// Recent activity
				$recent[] = $request;
			}
		}

		// Stats
		$stats['unique_crawlers'] = count($unique_crawlers);
		$stats['unique_posts'] = count($all_posts);

		// Prepare chart data
		$stats['chart_data']['dates'] = array_keys($last_7_days);
		$stats['chart_data']['requests_per_day'] = array_values($last_7_days);

		// Top 10 crawlers
		$final_crawler_counts = []; // Use a different variable name to avoid confusion
		foreach ($analytics as $week_date => $requests) {
			if (!is_array($requests)) continue;
			foreach ($requests as $request) {
				$crawler = $request['user_agent'];
				if (!isset($final_crawler_counts[$crawler])) {
					$final_crawler_counts[$crawler] = 0;
				}
				$final_crawler_counts[$crawler]++;
			}
		}

		arsort($final_crawler_counts);
		$top_crawlers = array_slice($final_crawler_counts, 0, 5, true);
		// get the count of the rest of the crawlers
		$rest_count = array_sum(array_slice($final_crawler_counts, 5, null, true));
		$top_crawlers['Others'] = $rest_count;
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

		// Last 10 activity
		usort($recent, function($a, $b) {
			return $b['timestamp'] - $a['timestamp'];
		});
		$stats['recent_activity'] = array_slice($recent, 0, 10);

		return $stats;
	}

	/**
	 * Renders the llms.txt page
	 *
	 * @param Md4AI_Admin $instance
	 *
	 * @return void
	 */
	public function render_tab_llms_txt() {
		$options = get_option( MD4AI_OPTION );
		if (empty($options)) {
			$llms_content = '';
		} else {
			$llms_content = $options['llms_content'];
		}
		$llms_url     = home_url( '/llms.txt' );
		$has_content  = ! empty( $llms_content );
		$llms_txt_placeholder = $this->llms_txt_placeholder;
		include __DIR__ . '/views/llms-txt.php';

	}

	/**
	 * Renders the cache page
	 *
	 * @param Md4AI_Admin $instance
	 *
	 * @return void
	 */
	public function render_tab_cache() {
		$stats = $this->cache->get_statistics();
		include __DIR__ . '/views/cache.php';
	}

	/**
	 * Renders the geo insights page
	 *
	 * @param Md4AI_Admin $instance
	 *
	 * @return void
	 */
	public function render_geo_insights_page() {
		// 1. Logic: Check if WooCommerce is active
		$is_woo_active = class_exists('WooCommerce');

		// 2. Get theme screenshot for preview
		$theme = wp_get_theme();
		$theme_screenshot = $theme->get_screenshot();
		$site_url = home_url();
		$site_domain = wp_parse_url( $site_url, PHP_URL_HOST );

		// 2. Configuration: Set dynamic labels and colors for the 3rd chart
		// We pass this state to JS via a data attribute
		include __DIR__ . '/views/geo-insights.php';
	}
}
