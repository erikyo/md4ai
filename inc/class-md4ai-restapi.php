<?php
/**
 * REST API endpoints class
 */
class md4AI_RestAPI {

	/**
	 * REST API namespace
	 */
	private $namespace = 'md4ai/v1';

	/**
	 * Markdown instance
	 */
	private $markdown;

	public function __construct($markdown) {
		$this->markdown = $markdown;
		add_action('rest_api_init', [$this, 'register_rest_routes']);
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
	}

	/**
	 * Permission check for REST API
	 */
	public function rest_permission_check($request) {
		$post_id = $request->get_param('id');
		return current_user_can('edit_post', $post_id);
	}

	/**
	 * REST API handler for generating markdown
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
	 * Get the REST namespace
	 */
	public function get_namespace() {
		return $this->namespace;
	}
}
