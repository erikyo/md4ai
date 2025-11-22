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
	 * Logs a request to the md4ai_requests to the md4ai option in the database
	 *
	 * @param int $ID The ID of the post
	 * @param string $user_agent The user agent of the request
	 * @param array $ai_useragents A list of user agents to check against the user agent
	 */
	public static function log_request( int $ID, $ai_useragents ) {

		$options = get_option( MD4AI_OPTION );

		// create the request array if it doesn't exist
		if ( ! isset( $options['requests'] ) ) {
			$options['requests'] = [];
		}

		$user_agent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));

		// the date of the last monday o today if today is monday
		$date = strtotime( 'Monday this week' );

		$user_agent = strtolower( $user_agent );

		// find the user agent string in the $ai_useragents array and keep only the name of the spider
		foreach ( $ai_useragents as $bot ) {
			if ( str_contains( $user_agent, $bot ) ) {
				$user_agent = $bot;
				break;
			}
		}

		$options['requests'][ wp_date( 'Y-m-d', $date ) ][] = [
			'post_id'    => $ID,
			'user_agent' => $user_agent,
			'timestamp'  => time(),
		];
		update_option( MD4AI_OPTION, $options );
	}

	/**
	 * Gets the llms.txt content
	 *
	 * @return string The llms.txt content
	 */
	public static function get_llms_txt_content() {
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

	public static function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}
}

