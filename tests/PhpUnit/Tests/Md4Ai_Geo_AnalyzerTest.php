<?php

namespace Md4Ai\Tests\PhpUnit\Tests;

use Md4Ai\Md4Ai_Geo_Analyzer;

class Md4Ai_Geo_AnalyzerTest extends \WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		// Clear description and menus
		update_option( 'blogdescription', '' );
		// Delete all menus
		$menus = wp_get_nav_menus();
		foreach ( $menus as $menu ) {
			wp_delete_nav_menu( $menu->term_id );
		}
		// Delete all pages
		$pages = get_posts( [ 'post_type' => 'page', 'numberposts' => -1 ] );
		foreach ( $pages as $page ) {
			wp_delete_post( $page->ID, true );
		}
	}

	public function test_context_sufficiency_fails_missing_description() {
		$analyzer = new Md4Ai_Geo_Analyzer( '' );
		$reflection = new \ReflectionClass( $analyzer );
		$method = $reflection->getMethod( 'check_context_sufficiency' );
		$method->setAccessible( true );

		$result = $method->invoke( $analyzer );

		$this->assertFalse( $result['status'] );
		$this->assertContains( 'missing_description', $result['issues'] );
	}

	public function test_context_sufficiency_fails_missing_navigation() {
		update_option( 'blogdescription', 'Test Description' );
		
		$analyzer = new Md4Ai_Geo_Analyzer( '' );
		$reflection = new \ReflectionClass( $analyzer );
		$method = $reflection->getMethod( 'check_context_sufficiency' );
		$method->setAccessible( true );

		$result = $method->invoke( $analyzer );

		$this->assertFalse( $result['status'] );
		$this->assertContains( 'missing_navigation', $result['issues'] );
	}

	public function test_context_sufficiency_passes_with_description_and_page() {
		update_option( 'blogdescription', 'Test Description' );
		// Create a published page to satisfy the fallback navigation check
		self::factory()->post->create( [ 'post_type' => 'page', 'post_status' => 'publish' ] );

		$analyzer = new Md4Ai_Geo_Analyzer( '' );
		$reflection = new \ReflectionClass( $analyzer );
		$method = $reflection->getMethod( 'check_context_sufficiency' );
		$method->setAccessible( true );

		$result = $method->invoke( $analyzer );

		$this->assertTrue( $result['status'] );
		$this->assertEmpty( $result['issues'] );
	}
	
	public function test_get_analysis_results_adds_corrections() {
		// Ensure context is missing
		update_option( 'blogdescription', '' );
		
		$analyzer = new Md4Ai_Geo_Analyzer( '' );
		$results = $analyzer->get_analysis_results();

		$corrections = $results['corrections'];
		$fields = array_column( $corrections, 'field' );

		$this->assertContains( 'Context: Description', $fields );
		$this->assertContains( 'Context: Navigation', $fields );
	}
}
