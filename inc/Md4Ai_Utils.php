<?php

namespace Md4Ai;

class Md4Ai_Utils {

	/**
	 * Displays buttons for generating llms.txt content
	 *
	 * @param string $field The field name to pass as a data attribute
	 * @param string $endpoint The REST API endpoint to call when generating llms.txt
	 *
	 * @return string The HTML output containing the buttons
	 */
	public static function display_llmstxt_buttons( string $field, string $endpoint = 'generate-llmstxt' ): string {
		$output = '';

		// the data field is used to pass the field name to the JavaScript, that is the HTML id of the textarea to update
		$data_field = sprintf( 'data-field="%s" ', $field );

		$output .= sprintf( '<button type="button" class="button md4ai-generate" data-action="replace" data-endpoint="%s" %s>%s</button>', $endpoint, $data_field, esc_html__( 'Generate', 'md4ai' ) );

		// if AI service is enabled, add the AI generate button
		if ( self::is_ai_service_enabled() ) {
			$output .= sprintf( '<button type="button" class="button md4ai-ai-generate button-primary-ai" data-action="append-after" data-endpoint="%s" %s>%s</button>', $endpoint, $data_field, esc_html__( 'Generate using AI', 'md4ai' ) );
		}

		return $output;
	}

	/**
	 * Gets the referrer
	 *
	 * @return string The referrer
	 */
	public static function get_referrer(): string {
		return isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
	}


	/**
	 * Gets the user agent
	 *
	 * @return string The user agent
	 */
	public static function get_user_agent(): string {
		return isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	}

	/**
	 * Logs a request to the md4ai_requests to the md4ai option in the database
	 *
	 * @param int   $ID The ID of the post
	 * @param array $ai_useragents A list of user agents to check against the user agent
	 */
	public static function log_request( int $ID, array $ai_useragents ) {

		$options = get_option( MD4AI_OPTION );

		// create the request array if it doesn't exist
		if ( ! isset( $options['requests'] ) ) {
			$options['requests'] = array();
		}

		// the date of the last monday o today if today is monday
		$date = strtotime( 'Monday this week' );

		$user_agent_string = self::get_user_agent_string( $ai_useragents );

		$options['requests'][ wp_date( 'Y-m-d', $date ) ][] = array(
			'post_id'    => $ID,
			'user_agent' => $user_agent_string,
			'timestamp'  => time(),
		);
		update_option( MD4AI_OPTION, $options );
	}

	/**
	 * Stores visitor data in the MD4AI_OPTION in the database
	 *
	 * @param string $source The source of the visitor
	 * @param string $search_terms The search terms of the visitor
	 */
	public static function store_visitor_data( string $source, string $search_terms ) {
		$options = get_option( MD4AI_OPTION );

		// create the visitor array if it doesn't exist
		if ( ! isset( $options['visitors'] ) ) {
			$options['visitors'] = array();
		}

		// the date of the last monday o today if today is monday
		$date = strtotime( 'Monday this week' );

		$options['visitors'][ wp_date( 'Y-m-d', $date ) ][] = array(
			'source'        => $source,
			'search_terms'  => $search_terms,
			'date_recorded' => time(),
		);
		update_option( MD4AI_OPTION, $options );
	}

	/**
	 * Gets the llms.txt content
	 *
	 * @return string The llms.txt content
	 */
	public static function get_llms_txt_content(): string {
		$options = get_option( MD4AI_OPTION, '' );

		return $options['llms_txt_content'] ?? '';
	}

	/**
	 * Checks if the AI services are enabled
	 *
	 * @return bool Whether the AI services are enabled
	 */
	public static function is_ai_service_enabled(): bool {
		return function_exists( 'ai_services' );
	}

	/**
	 * Checks if WooCommerce is active
	 *
	 * @return bool Whether WooCommerce is active
	 */
	public static function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Gets the user agent string
	 *
	 * @param array $ai_useragents A list of user agents to check against the user agent
	 *
	 * @return string The user agent string
	 */
	private static function get_user_agent_string( array $ai_useragents ): string {
		// get the user agent and convert it to lowercase
		$user_agent = strtolower( self::get_user_agent() );

		// find the user agent string in the $ai_useragents array and keep only the name of the spider
		foreach ( $ai_useragents as $bot ) {
			if ( str_contains( $user_agent, $bot ) ) {
				$user_agent = $bot;
				break;
			}
		}

		return $user_agent;
	}
}
