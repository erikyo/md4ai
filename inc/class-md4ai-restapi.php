<?php
/**
 * REST API endpoints class
 */
class md4AI_RestAPI {

	/**
	 * REST API namespace
	 */
	private string $namespace = 'md4ai/v1';

	/**
	 * Markdown instance
	 */
	private md4AI_Markdown $markdown;

	/**
	 * Constructs the REST API endpoints class
	 *
	 * @param md4AI_Markdown $markdown Markdown generation and conversion class instance
	 */
	public function __construct( md4AI_Markdown $markdown) {
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
	}

	/**
	 * Permission check for REST API
	 */
	public function rest_permission_check($request) {
		$post_id = $request->get_param('id');
		return current_user_can('edit_post', $post_id);
	}

	/**
	 * Permission check for REST API
	 */
	public function admin_permission_check($request) {
		return current_user_can('edit_posts');
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

	public function rest_generate_llmstxt() {
		$llmstxt = $this->markdown->generate_default_llmstxt();
		return new WP_REST_Response([
			'markdown' => $llmstxt,
		], 200);
	}
}
