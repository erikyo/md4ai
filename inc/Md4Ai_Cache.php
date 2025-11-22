<?php

namespace Md4Ai;

/**
 * Cache management class
 */
class Md4Ai_Cache {

	/**
	 * Cache directory path
	 */
	private $cache_dir;

	public function __construct() {
		$this->cache_dir = WP_CONTENT_DIR . '/cache/md4ai';
		$this->ensure_cache_directory();

		// Clear cache when posts are updated
		add_action('save_post', [$this, 'clear_post_cache']);
		add_action('delete_post', [$this, 'clear_post_cache']);
		add_action('after_switch_theme', [$this, 'clear_navigation_cache']);
		add_action('wp_update_nav_menu', [$this, 'clear_navigation_cache']);
	}

	/**
	 * Ensures the cache directory exists and is writable
	 */
	private function ensure_cache_directory() {
		if (!file_exists($this->cache_dir)) {
			wp_mkdir_p($this->cache_dir);
		}

		// Add .htaccess to protect cache directory
		$htaccess_file = $this->cache_dir . '/.htaccess';
		if (!file_exists($htaccess_file)) {
			$htaccess_content = "Order deny,allow\nDeny from all";
			file_put_contents($htaccess_file, $htaccess_content);
		}

		// Add index.php to prevent directory listing
		$index_file = $this->cache_dir . '/index.php';
		if (!file_exists($index_file)) {
			file_put_contents($index_file, "<?php\n// Silence is golden.");
		}
	}

	/**
	 * Gets the cache file path for a post
	 */
	private function get_cache_file_path($post_id) {
		return $this->cache_dir . '/post-' . $post_id . '.md';
	}

	/**
	 * Checks if cached file exists and is valid
	 */
	public function is_cache_valid($post_id, $post_modified) {
		$cache_file = $this->get_cache_file_path($post_id);

		if (!file_exists($cache_file)) {
			return false;
		}

		// Check if cache is newer than post modification date
		$cache_time = filemtime($cache_file);
		$post_time = strtotime($post_modified);

		return $cache_time >= $post_time;
	}

	/**
	 * Reads markdown from cache
	 */
	public function read_from_cache($post_id) {
		$cache_file = $this->get_cache_file_path($post_id);

		if (file_exists($cache_file)) {
			return file_get_contents($cache_file);
		}

		return false;
	}

	/**
	 * Writes markdown to cache
	 */
	public function write_to_cache($post_id, $markdown) {
		$cache_file = $this->get_cache_file_path($post_id);
		return file_put_contents($cache_file, $markdown) !== false;
	}

	/**
	 * Clears cache for a specific post
	 */
	public function clear_post_cache($post_id) {
		$cache_file = $this->get_cache_file_path($post_id);

		if (file_exists($cache_file)) {
			wp_delete_file($cache_file);
		}
	}

	/**
	 * Clears all cache files
	 */
	public function clear_all_cache(): bool {
		// Clear post caches
		$files = glob($this->cache_dir . '/post-*.md');

		if ($files) {
			foreach ($files as $file) {
				if (is_file($file)) {
					wp_delete_file($file);
				}
			}
		}

		return true;
	}

	/**
	 * Clear header/footer cache
	 */
	public function clear_navigation_cache() {
		$cache_file = $this->cache_dir . '/header-footer.json';
		if (file_exists($cache_file)) {
			wp_delete_file($cache_file);
		}
		return true;
	}

	/**
	 * Gets cached header/footer data or generates it
	 */
	public function get_header_footer_data($callback) {
		$cache_file = $this->cache_dir . '/header-footer.json';
		$cache_duration = 86400; // 24 hours

		// Check if cache exists and is valid
		if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_duration) {
			$data = json_decode(file_get_contents($cache_file), true);
			if ($data) {
				return $data;
			}
		}

		// Generate header/footer data using callback
		$data = call_user_func($callback);

		// Cache the data
		file_put_contents($cache_file, json_encode($data));

		return $data;
	}

	/**
	 * Get cache statistics
	 */
	public function get_statistics() {
		$files = glob($this->cache_dir . '/post-*.md');
		$file_count = $files ? count($files) : 0;
		$total_size = 0;

		if ($files) {
			foreach ($files as $file) {
				$total_size += filesize($file);
			}
		}

		return [
			'file_count' => $file_count,
			'total_size' => $total_size,
			'total_size_mb' => number_format($total_size / 1024 / 1024, 2),
			'cache_dir' => $this->cache_dir
		];
	}
}
