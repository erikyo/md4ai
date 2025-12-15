<?php

namespace Md4Ai;

use WP_Filesystem_Base;

/**
 * Cache management class
 */
class Md4Ai_Cache {

	/**
	 * Cache directory path
	 *
	 * @var string $cache_dir Cache directory path
	 */
	private $cache_dir;

	/**
	 * The Md4Ai Cache constructor
	 */
	public function __construct() {
		$this->cache_dir = WP_CONTENT_DIR . '/cache/md4ai';
		$this->ensure_cache_directory();

		// Clear cache when posts are updated
		add_action( 'save_post', array( $this, 'clear_post_cache' ) );
		add_action( 'delete_post', array( $this, 'clear_post_cache' ) );
		add_action( 'after_switch_theme', array( $this, 'clear_navigation_cache' ) );
		add_action( 'wp_update_nav_menu', array( $this, 'clear_navigation_cache' ) );
	}

	/**
	 * Ensures the cache directory exists and is writable
	 */
	private function ensure_cache_directory() {
		if ( ! file_exists( $this->cache_dir ) ) {
			wp_mkdir_p( $this->cache_dir );
		}

		// Initialize WP_Filesystem
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		/**
		 * The WP_Filesystem instance
		 *
		 * @global WP_Filesystem_Base $wp_filesystem
		 */
		global $wp_filesystem;

		// Add .htaccess to protect cache directory
		$htaccess_file = $this->cache_dir . '/.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			$htaccess_content = "Order deny,allow\nDeny from all";
			$wp_filesystem->put_contents( $htaccess_file, $htaccess_content );
		}

		// Add index.php to prevent directory listing
		$index_file = $this->cache_dir . '/index.php';
		if ( ! file_exists( $index_file ) ) {
			$wp_filesystem->put_contents( $index_file, "<?php\n// Silence is golden." );
		}
	}

	/**
	 * Gets the cache file path for a post
	 *
	 * @param int $post_id The post ID
	 *
	 * @return string The cache file path
	 */
	private function get_cache_file_path( $post_id ) {
		return $this->cache_dir . '/post-' . $post_id . '.md';
	}

	/**
	 * Checks if the cached file exists and is valid
	 *
	 * @param int    $post_id The post-ID
	 * @param string $post_modified The post-modification date
	 *
	 * @return bool True if the cache is valid, false otherwise
	 */
	public function is_cache_valid( int $post_id, string $post_modified ): bool {
		$cache_file = $this->get_cache_file_path( $post_id );

		if ( ! file_exists( $cache_file ) ) {
			return false;
		}

		// Check if cache is newer than post modification date
		$cache_time = filemtime( $cache_file );
		$post_time  = strtotime( $post_modified );

		return $cache_time >= $post_time;
	}

	/**
	 * Reads Markdown from a cache file
	 *
	 * @param int $post_id The post-ID
	 *
	 * @return string|false The Markdown content or false if the cache does not exist
	 */
	public function read_from_cache( int $post_id ) {
		$cache_file = $this->get_cache_file_path( $post_id );

		if ( file_exists( $cache_file ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
			/**
			 * The WP_Filesystem instance
			 *
			 * @global WP_Filesystem_Base $wp_filesystem
			 */
			global $wp_filesystem;
			return $wp_filesystem->get_contents( $cache_file );
		}

		return false;
	}

	/**
	 * Writes Markdown to a cache file
	 *
	 * @param int    $post_id The post-ID
	 * @param string $markdown The Markdown content
	 *
	 * @return bool True if the cache was written successfully, false otherwise
	 */
	public function write_to_cache( int $post_id, string $markdown ): bool {
		$cache_file = $this->get_cache_file_path( $post_id );

		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		/**
		 * The WP_Filesystem instance
		 *
		 * @global WP_Filesystem_Base $wp_filesystem
		 */
		global $wp_filesystem;
		return $wp_filesystem->put_contents( $cache_file, $markdown ) !== false;
	}

	/**
	 * Clears cache for a specific post
	 *
	 * @param int $post_id The post-ID
	 */
	public function clear_post_cache( int $post_id ) {
		$cache_file = $this->get_cache_file_path( $post_id );

		if ( file_exists( $cache_file ) ) {
			wp_delete_file( $cache_file );
		}
	}

	/**
	 * Clears all cache files
	 */
	public function clear_all_cache(): bool {
		// Clear post caches
		$files = glob( $this->cache_dir . '/post-*.md' );

		if ( $files ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}
		}

		return true;
	}

	/**
	 * Clear header/footer cache
	 *
	 * @return bool True if the cache was cleared successfully, false otherwise
	 */
	public function clear_navigation_cache(): bool {
		$cache_file = $this->cache_dir . '/header-footer.json';
		if ( file_exists( $cache_file ) ) {
			wp_delete_file( $cache_file );
		}
		return true;
	}

	/**
	 * Gets cached header/footer data or generates it
	 *
	 * @param callable $callback The callback function to generate the header/footer data
	 *
	 * @return array The header/footer data
	 */
	public function get_header_footer_data( callable $callback ): array {
		$cache_file = $this->cache_dir . '/header-footer.json';
		// 24 hours
		$cache_duration = 86400;

		// Initialize WP_Filesystem
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		/**
		 * The WP_Filesystem instance
		 *
		 * @global WP_Filesystem_Base $wp_filesystem
		 */
		global $wp_filesystem;

		// Check if a cache file exists and is valid
		if ( file_exists( $cache_file ) && ( time() - filemtime( $cache_file ) ) < $cache_duration ) {
			// Read the cache file
			$data = json_decode( $wp_filesystem->get_contents( $cache_file ), true );
			if ( $data ) {
				return $data;
			}
		}

		// Generate header/footer data using callback
		$data = call_user_func( $callback );

		// Cache the data
		$wp_filesystem->put_contents( $cache_file, wp_json_encode( $data ) );

		return $data;
	}

	/**
	 * Get cache statistics
	 *
	 * @return array The cache statistics
	 */
	public function get_statistics(): array {
		$files      = glob( $this->cache_dir . '/post-*.md' );
		$file_count = $files ? count( $files ) : 0;
		$total_size = 0;

		if ( $files ) {
			foreach ( $files as $file ) {
				$total_size += filesize( $file );
			}
		}

		return array(
			'file_count'    => $file_count,
			'total_size'    => $total_size,
			'total_size_mb' => number_format( $total_size / 1024 / 1024, 2 ),
			'cache_dir'     => $this->cache_dir,
		);
	}
}
