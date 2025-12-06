<?php

namespace Md4Ai;

/**
 * Class Geo_Analyzer
 * Handles parsing of the AI response and comparison with real WordPress/WooCommerce data.
 */
class Md4Ai_Geo_Analyzer {

	private $ai_raw_response;
	private $ground_truth = [];
	private $parsed_ai_data = [];
	private $is_woo_active = false;

	public function __construct( $ai_response_text ) {
		$this->ai_raw_response = $ai_response_text;
		$this->is_woo_active   = class_exists( 'WooCommerce' );

		// 1. Extract data from AI
		$this->parse_ai_response();

		// 2. Fetch real data from WP
		$this->fetch_ground_truth();
	}

	/**
	 * 1. PARSING: Transforms the AI text into a usable array
	 */
	private function parse_ai_response() {
		$text = $this->ai_raw_response;

		// Helper for quick regex extraction
		$extract = function ( $pattern ) use ( $text ) {
			preg_match( $pattern, $text, $matches );

			return isset( $matches[1] ) ? trim( $matches[1] ) : 'Unknown';
		};

		// Section 1 Parsing
		$this->parsed_ai_data['website_name'] = $extract( '/Website Name:\s*(.*)$/m' );
		$this->parsed_ai_data['author_name']  = $extract( '/Author Name:\s*(.*)$/m' );
		$this->parsed_ai_data['subject']      = $extract( '/Primary Subject Matter:\s*(.*)$/m' );

		// Section 2 & 3 Parsing
		$this->parsed_ai_data['main_entity'] = $extract( '/Main Entity Type:\s*(.*)$/m' );

		// New GEO/AIO Metrics
		$this->parsed_ai_data['brand_strength'] = $extract( '/Brand Entity Strength:\s*(.*)$/m' );
		$this->parsed_ai_data['score_structure'] = (int) $extract( '/Content Structure Score:\s*(\d+)/m' );
		$this->parsed_ai_data['score_multimedia'] = (int) $extract( '/Multimedia Usage Score:\s*(\d+)/m' );
		$this->parsed_ai_data['score_tech_seo'] = (int) $extract( '/Technical SEO Perception:\s*(\d+)/m' );

		// E-commerce specific parsing (if present in the text)
		$this->parsed_ai_data['is_ecommerce'] = $extract( '/Is an E-commerce site:\s*(.*)$/m' );
		$this->parsed_ai_data['woo_detected'] = $extract( '/Reasoning for Detection:\s*(.*)$/m' ); // Often used as a proxy

		// Final numeric evaluations
		$this->parsed_ai_data['score_auth']      = (int) $extract( '/Authoritative Content:\s*(\d+)/m' );
		$this->parsed_ai_data['score_relevance'] = (int) $extract( '/Contextual Relevance:\s*(\d+)/m' );
		$this->parsed_ai_data['score_data']      = (int) $extract( '/Amount of data available:\s*(\d+)/m' );
		$this->parsed_ai_data['score_crawler']   = (int) $extract( '/The website is intelligible to crawlers:\s*(\d+)/m' );
	}

	/**
	 * 2. GROUND TRUTH: Retrieves real data from the WP Database
	 */
	private function fetch_ground_truth() {
		// General Data
		$this->ground_truth['website_name'] = get_bloginfo( 'name' );

		// Main Author (gets the first admin or the first post's author)
		$users                             = get_users( [ 'role__in' => [ 'administrator', 'editor' ], 'number' => 1 ] );
		$this->ground_truth['author_name'] = ! empty( $users ) ? $users[0]->display_name : 'Admin';

		// Categories (Core Topics)
		$categories                   = get_terms( [ 'taxonomy' => 'category', 'hide_empty' => true, 'number' => 3 ] );
		$this->ground_truth['topics'] = wp_list_pluck( $categories, 'name' );

		// Technical Ground Truth
		$this->ground_truth['is_ssl'] = is_ssl();
		$this->ground_truth['context_sufficiency'] = $this->check_context_sufficiency();

		// E-commerce Data
		if ( $this->is_woo_active ) {
			$this->ground_truth['is_ecommerce'] = 'Yes';
			// "Best Seller" products (simulated via total sales)
			$products      = wc_get_products( [
				'limit'   => 3,
				'orderby' => 'total_sales',
				'order'   => 'DESC',
				'return'  => 'ids',
			] );
			$product_names = [];
			foreach ( $products as $pid ) {
				$product_names[] = get_the_title( $pid );
			}
			$this->ground_truth['products'] = $product_names;
		} else {
			$this->ground_truth['is_ecommerce'] = 'No';
		}
	}

	/**
	 * Checks if the site provides enough context for AI (Description & Navigation)
	 */
	private function check_context_sufficiency() {
		$issues = [];

		// 1. Check Site Description
		$description = get_bloginfo( 'description' );
		if ( empty( $description ) ) {
			$issues[] = 'missing_description';
		}

		// 2. Check Navigation (Menus or Header/Footer links)
		$has_menus = has_nav_menu( 'primary' ) || has_nav_menu( 'header' ) || has_nav_menu( 'footer' );
		if ( ! $has_menus ) {
			// Fallback: Check if there are any published pages that would appear in a default menu
			$pages = wp_count_posts( 'page' );
			if ( ! isset( $pages->publish ) || $pages->publish < 1 ) {
				$issues[] = 'missing_navigation';
			}
		}

		if ( empty( $issues ) ) {
			return [ 'status' => true, 'issues' => [] ];
		}

		return [ 'status' => false, 'issues' => $issues ];
	}

	/**
	 * 3. ANALYSIS & SCORES
	 * Returns the final array for the Frontend (JSON)
	 */
	public function get_analysis_results() {

		$corrections = [];
		$scores      = [];

		// --- CHECK 1: Identity (Website Name) ---
		$sim_name = 0;
		similar_text( strtolower( $this->parsed_ai_data['website_name'] ), strtolower( $this->ground_truth['website_name'] ), $sim_name );

		if ( $sim_name < 50 ) { // If similarity < 50%
			$corrections[] = [
				'field'      => 'Website Name',
				'ai_value'   => $this->parsed_ai_data['website_name'],
				'real_value' => $this->ground_truth['website_name'],
				'tip'        => 'Check your Schema.org "WebSite" markup and Title Tag.'
			];
		}

		// --- CHECK 2: Author ---
		$sim_auth = 0;
		// Handle "Unknown"
		if ( strtolower( $this->parsed_ai_data['author_name'] ) === 'unknown' || strtolower( $this->parsed_ai_data['author_name'] ) === 'n/a' ) {
			$sim_auth = 0;
		} else {
			similar_text( strtolower( $this->parsed_ai_data['author_name'] ), strtolower( $this->ground_truth['author_name'] ), $sim_auth );
		}

		if ( $sim_auth < 40 ) {
			$corrections[] = [
				'field'      => 'Author/Owner',
				'ai_value'   => $this->parsed_ai_data['author_name'],
				'real_value' => $this->ground_truth['author_name'],
				'tip'        => 'Add a clear "About Us" page and Person Schema markup.'
			];
		}

		$scores['identity_match'] = round( ( $sim_name + $sim_auth ) / 2 );

		// --- CHECK 3: E-commerce Tech ---
		$is_ecom_match = 0;
		// Normalize Yes/No
		$ai_is_ecom   = stripos( $this->parsed_ai_data['is_ecommerce'], 'Yes' ) !== false;
		$real_is_ecom = $this->ground_truth['is_ecommerce'] === 'Yes';

		if ( $ai_is_ecom === $real_is_ecom ) {
			$is_ecom_match = 100;
		} else {
			$corrections[] = [
				'field'      => 'E-commerce Detection',
				'ai_value'   => $ai_is_ecom ? 'Yes' : 'No',
				'real_value' => $real_is_ecom ? 'Yes' : 'No',
				'tip'        => $real_is_ecom ? 'Ensure /shop/ or product pages are crawlable.' : 'Check for confusing transactional keywords.'
			];
		}
		$scores['tech_match'] = $is_ecom_match;

		// --- CHECK 4: Technical SEO (SSL & Context) ---
		if ( ! $this->ground_truth['is_ssl'] ) {
			$corrections[] = [
				'field'      => 'Security (SSL)',
				'ai_value'   => 'N/A',
				'real_value' => 'Missing',
				'tip'        => 'Install an SSL certificate (HTTPS) for better ranking and trust.'
			];
		}

		if ( ! $this->ground_truth['context_sufficiency']['status'] ) {
			$issues = $this->ground_truth['context_sufficiency']['issues'];
			
			if ( in_array( 'missing_description', $issues ) ) {
				$corrections[] = [
					'field'      => 'Context: Description',
					'ai_value'   => 'N/A',
					'real_value' => 'Missing Tagline',
					'tip'        => 'Add a site tagline in Settings > General to help AI understand your site.'
				];
			}

			if ( in_array( 'missing_navigation', $issues ) ) {
				$corrections[] = [
					'field'      => 'Context: Navigation',
					'ai_value'   => 'N/A',
					'real_value' => 'No Menus/Pages',
					'tip'        => 'Ensure your site has navigation menus or crawlable internal links.'
				];
			}
		}

		// --- CHECK 5: AI Perception Score (Average of the 0–10 ratings normalized to 100) ---
		// We now have 7 metrics: 4 original + 3 new (structure, multimedia, tech_seo)
		$total_ai_points = 
			$this->parsed_ai_data['score_auth'] + 
			$this->parsed_ai_data['score_relevance'] + 
			$this->parsed_ai_data['score_data'] + 
			$this->parsed_ai_data['score_crawler'] +
			$this->parsed_ai_data['score_structure'] +
			$this->parsed_ai_data['score_multimedia'] +
			$this->parsed_ai_data['score_tech_seo'];

		// Max points = 70. Normalize to 100.
		$scores['ai_perception'] = round( ($total_ai_points / 70) * 100 );

		// Add sub-scores for detailed view
		$scores['geo_structure'] = $this->parsed_ai_data['score_structure'] * 10;
		$scores['geo_multimedia'] = $this->parsed_ai_data['score_multimedia'] * 10;
		$scores['geo_tech'] = $this->parsed_ai_data['score_tech_seo'] * 10;

		// --- FINAL OUTPUT ---
		return [
			'scores'       => $scores,       // For Chart.js
			'corrections'  => $corrections, // "What to fix" table
			'raw_ai_data'  => $this->parsed_ai_data,
			'ground_truth' => $this->ground_truth
		];
	}
}
