<?php

namespace Md4Ai;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API endpoints class
 */
class Md4Ai_RestAPI {

	/**
	 * REST API namespace
	 */
	private string $namespace = 'md4ai/v1';

	/**
	 * Markdown instance
	 */
	private Md4Ai_Markdown $markdown;

	/**
	 * Constructs the REST API endpoints class
	 *
	 * @param Md4Ai_Markdown $markdown Markdown generation and conversion class instance
	 */
	public function __construct( Md4Ai_Markdown $markdown) {
		$this->markdown = $markdown;
		add_action('rest_api_init', [$this, 'register_rest_routes']);
	}

	/**
	 * Get the REST namespace
	 */
	public function get_namespace() {
		return $this->namespace;
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		register_rest_route($this->namespace, '/generate-markdown/(?P<id>\d+)', [
			'methods' => 'POST',
			'callback' => [$this, 'rest_generate_markdown'],
			'permission_callback' => [$this, 'rest_permission_check'],
			'args' => [
				'id' => [
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				],
			],
		]);


		register_rest_route($this->namespace, '/generate-llmstxt', [
			'methods' => 'POST',
			'callback' => [$this, 'rest_generate_llmstxt'],
			'permission_callback' => [$this, 'admin_permission_check']
		]);

		register_rest_route( $this->namespace, '/get-stats', [
			'methods' => 'GET',
			'callback' => [$this, 'rest_get_stats'],
			'permission_callback' => [$this, 'admin_permission_check']
			]);

		register_rest_route( $this->namespace, '/geo-insights', [
			'methods' => 'POST',
			'callback' => [$this, 'geo_insights'],
			'permission_callback' => [$this, 'admin_permission_check'],
			'args' => [
				'content' => [
					'required' => true,
					'validate_callback' => function($param) {
						return is_string($param);
					}
				]
			],
		]);
	}

	/**
	 * Permission check for REST API
	 * @return bool Whether the user has permission to access the API
	 */
	public function rest_permission_check($request) {
		$post_id = $request->get_param('id');
		return current_user_can('edit_post', $post_id);
	}

	/**
	 * Permission check for REST API
	 * @return bool Whether the user has permission to access the API
	 */
	public function admin_permission_check($request) {
		return current_user_can('edit_posts');
	}

	/**
	 * REST API handler for generating markdown
	 *
	 * @return WP_REST_Response | WP_Error The response from the API or an error
	 */
	public function rest_generate_markdown($request) {
		$post_id = $request->get_param('id');

		if (!$post_id) {
			return new WP_Error('invalid_post', __('Invalid post ID.', 'md4ai'), ['status' => 400]);
		}

		$post = get_post($post_id);

		if (!$post) {
			return new WP_Error('post_not_found', __('Post not found.', 'md4ai'), ['status' => 404]);
		}

		$markdown = $this->markdown->convert_post_to_markdown($post);

		return new WP_REST_Response([
			'markdown' => $markdown,
		], 200);
	}

	/**
	 * REST API handler for generating llmstxt
	 *
	 * @return WP_REST_Response The response from the API or an error
	 */
	public function rest_generate_llmstxt() {
		$llmstxt = $this->markdown->generate_default_llmstxt();
		return new WP_REST_Response([
			'markdown' => $llmstxt,
		], 200);
	}

	/**
	 * Generate Stats
	 *
	 * @returns WP_REST_Response | WP_Error The response from the API or an error
	 */
	public function rest_get_stats() {
		$options = get_option( MD4AI_OPTION );
		$analytics = $options['requests'] ?? [];

		if (empty($analytics)) {
			return new WP_REST_Response([
				'stats' => [],
			], 200);
		}

		$stats = Md4Ai_Admin_Views::prepare_dashboard_stats($analytics);
		return new WP_REST_Response([
			'stats' => $stats,
		], 200);
	}

	/**
	 * Generate Geo Insights
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @returns WP_REST_Response | WP_Error The response from the API or an error
	 */
	public function geo_insights( WP_REST_Request $request ) {
		$content = sanitize_text_field( $request['content'] );

		if (empty($content)) {
			return new WP_REST_Response([
				'result' => 'No content provided',
			], 400);
		}

		$geo_analyzer = new Md4Ai_Geo_Analyzer($content);

		return new WP_REST_Response([
			'result' => $geo_analyzer->get_analysis_results(),
		], 200);
	}
}
