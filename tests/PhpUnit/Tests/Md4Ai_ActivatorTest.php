<?php

namespace Md4Ai\Tests\PhpUnit\Tests;

use Md4Ai\Md4Ai_Activator;

class Md4Ai_ActivatorTest extends \WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		delete_option( MD4AI_OPTION );
	}

	public function test_activate_creates_option() {
		$this->assertFalse( get_option( MD4AI_OPTION ) );

		Md4Ai_Activator::activate();

		$options = get_option( MD4AI_OPTION );
		$this->assertIsArray( $options );
		$this->assertArrayHasKey( 'llms_content', $options );
		$this->assertNotEmpty( $options['llms_content'] );
	}

	public function test_activate_does_not_overwrite_existing_option() {
		$existing_options = [ 'llms_content' => 'existing content' ];
		add_option( MD4AI_OPTION, $existing_options );

		Md4Ai_Activator::activate();

		$this->assertEquals( $existing_options, get_option( MD4AI_OPTION ) );
	}

	public function test_uninstall_deletes_post_meta() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_md4ai_custom_markdown', 'custom markdown' );

		$this->assertEquals( 'custom markdown', get_post_meta( $post_id, '_md4ai_custom_markdown', true ) );

		Md4Ai_Activator::uninstall();

		$this->assertEmpty( get_post_meta( $post_id, '_md4ai_custom_markdown', true ) );
	}
}
