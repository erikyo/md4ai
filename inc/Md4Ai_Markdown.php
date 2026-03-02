<?php

namespace Md4Ai;

/**
 * Markdown generation and conversion class
 */
class Md4Ai_Markdown {

	/**
	 * Post-meta key for custom Markdown
	 *
	 * @var string
	 */
	private string $meta_key = '_md4ai_custom_markdown';

	/**
	 * Cache instance
	 *
	 * @var Md4Ai_Cache
	 */
	private Md4Ai_Cache $cache;

	/**
	 * Md4Ai_Markdown constructor.
	 *
	 * @param Md4Ai_Cache $cache The cache instance
	 */
	public function __construct( Md4Ai_Cache $cache ) {
		$this->cache = $cache;
	}

	/**
	 * Get the meta-key
	 *
	 * @return string
	 */
	public function get_meta_key(): string {
		return $this->meta_key;
	}

	/**
	 * Gets markdown for a post - checks custom meta first, then generates
	 *
	 * @param object $post The post object
	 *
	 * @return string The markdown for the post
	 */
	public function get_post_markdown( $post ) {
		/**
		 * Filter to modify post arguments
		 *
		 * @param array $args The arguments to pass to the convert_post_to_markdown method
		 */
		$args = apply_filters(
			'md4ai_post_args',
			array(
				'include_navigation' => true,
				'include_categories' => true,
				'include_tags'       => true,
				'include_footer'     => true,
			)
		);

		// Check if custom markdown exists
		$args['content'] = get_post_meta( $post->ID, $this->meta_key, true );

		// Generate from post content
		return $this->convert_post_to_markdown( $post, $args );
	}

	/**
	 * Sanitizes text for Markdown output
	 *
	 * @param string $text The text to sanitize
	 *
	 * @return string The sanitized text
	 */
	private function sanitize_text( string $text ): string {
		return html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * Basic HTML to Markdown conversion
	 *
	 * @param string $html The HTML to convert
	 *
	 * @return string The Markdown
	 */
	private function html_to_markdown( string $html ): string {
		// Remove script, style and forms
		$html = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $html );
		$html = preg_replace( '/<style\b[^>]*>(.*?)<\/style>/is', '', $html );
		$html = preg_replace( '/<form\b[^>]*>(.*?)<\/form>/is', '', $html );

		// Headers
		$html = preg_replace( '/<h1[^>]*>(.*?)<\/h1>/i', "\n# $1\n", $html );
		$html = preg_replace( '/<h2[^>]*>(.*?)<\/h2>/i', "\n## $1\n", $html );
		$html = preg_replace( '/<h3[^>]*>(.*?)<\/h3>/i', "\n### $1\n", $html );
		$html = preg_replace( '/<h4[^>]*>(.*?)<\/h4>/i', "\n#### $1\n", $html );
		$html = preg_replace( '/<h5[^>]*>(.*?)<\/h5>/i', "\n##### $1\n", $html );
		$html = preg_replace( '/<h6[^>]*>(.*?)<\/h6>/i', "\n###### $1\n", $html );

		// Bold and Italic
		$html = preg_replace( '/<(strong|b)[^>]*>(.*?)<\/(strong|b)>/i', '**$2**', $html );
		$html = preg_replace( '/<(em|i)[^>]*>(.*?)<\/(em|i)>/i', '*$2*', $html );

		// Link
		$html = preg_replace( '/<a[^>]+href="([^"]+)"[^>]*>(.*?)<\/a>/i', '[$2]($1)', $html );

		// Images
		$html = preg_replace( '/<img[^>]+src="([^"]+)"[^>]*alt="([^"]*)"[^>]*>/i', '![$2]($1)', $html );
		$html = preg_replace( '/<img[^>]+src="([^"]+)"[^>]*>/i', '![]($1)', $html );

		// Lists
		$html = preg_replace( '/<li[^>]*>(.*?)<\/li>/i', "- $1\n", $html );
		$html = preg_replace( '/<\/?ul[^>]*>/i', "\n", $html );
		$html = preg_replace( '/<\/?ol[^>]*>/i', "\n", $html );

		// Paragraphs
		$html = preg_replace( '/<p[^>]*>(.*?)<\/p>/i', "$1\n\n", $html );
		$html = preg_replace( '/<br[^>]*>/i', "\n", $html );

		// Blockquote
		$html = preg_replace( '/<blockquote[^>]*>(.*?)<\/blockquote>/is', "> $1\n", $html );

		// Code
		$html = preg_replace( '/<code[^>]*>(.*?)<\/code>/i', '`$1`', $html );
		$html = preg_replace( '/<pre[^>]*>(.*?)<\/pre>/is', "```\n$1\n```\n", $html );

		// Remove all other HTML tags and decode entities
		// Remove all other HTML tags and decode entities
		$html = $this->sanitize_text( $html );

		// Clean up multiple spaces
		$html = preg_replace( '/\n\s*\n\s*\n/', "\n\n", $html );
		$html = trim( $html );

		return $html;
	}

	/**
	 * Generates website links
	 *
	 * @param array  $args The arguments to pass to the function
	 * @param object $post The post-object
	 *
	 * @return string The generated website links
	 */
	public function generate_website_links( array $args, $post = false ): string {
		$output = '';

		// Categories and tags
		if ( $post && $args['include_categories'] ) {
			$categories = get_the_category( $post->ID );
			if ( ! empty( $categories ) ) {
				$output .= "---\n\n";
				$output .= "## Categories\n\n";
				foreach ( $categories as $cat ) {
					$output .= '- ' . $this->sanitize_text( $cat->name ) . "\n";
				}
				$output .= "\n";
			}
		}

		// Get header/footer data (cached)
		if ( true === $args['include_navigation'] ) {
			$nav_data = $this->cache->get_header_footer_data( array( $this, 'extract_header_footer_links' ) );

			// Add header navigation
			if ( ! empty( $nav_data['header'] ) ) {
				$output .= "---\n\n";
				$output .= $this->format_navigation_markdown( $nav_data['header'], 'Navigation' );
			}
		}

		if ( $post && $args['include_tags'] ) {
			$tags = get_the_tags( $post->ID );
			if ( ! empty( $tags ) ) {
				$output .= "## Tags\n\n";
				foreach ( $tags as $tag ) {
					$output .= '- ' . $this->sanitize_text( $tag->name ) . "\n";
				}
				$output .= "\n";
			}
		}

		// Add footer navigation
		if ( $args['include_footer'] ) {
			$nav_data = $this->cache->get_header_footer_data( array( $this, 'extract_header_footer_links' ) );
			if ( ! empty( $nav_data['footer'] ) ) {
				$output .= "---\n\n";
				$output .= $this->format_navigation_markdown( $nav_data['footer'], 'Footer Links' );
			}
		}

		return $output;
	}

	/**
	 * Converts a WordPress post to Markdown
	 *
	 * @param object $post The post object
	 * @param array  $args The arguments to pass to the function
	 *
	 * @return string The Markdown for the post
	 */
	public function convert_post_to_markdown( object $post, array $args = array() ): string {
		$args = wp_parse_args(
			$args,
			array(
				'content'            => false,
				'include_navigation' => false,
				'include_categories' => false,
				'include_tags'       => false,
				'include_footer'     => false,
			)
		);

		/**
		 * Filter to modify post before conversion to Markdown
		 *
		 * @param object $post The post object
		 */
		$post = apply_filters( 'md4ai_post', $post );

		$output = "---\n";

		if ( ! empty( $args['content'] ) ) {
			$output .= $args['content'] . "\n\n";
		} else {

			// Get post type
			$post_type = get_post_type( $post );

			// Title
			$output .= '# ' . $this->sanitize_text( $post->post_title ) . "\n\n";

			// The Page Meta information
			$output .= '**URL:** ' . esc_url( get_permalink( $post ) ) . "\n";

			// Generate meta information based on post type
			if ( 'product' !== $post_type ) {
				$output .= $this->generate_post_meta( $post, $post_type );
			} else {
				$output .= $this->generate_product_meta( $post );
			}
			$output .= "---\n\n";

			/**
			 * Filter to modify content before conversion to Markdown
			 *
			 * @param string $content The post content
			 */
			$content = apply_filters( 'md4ai_the_content', $post->post_content );

			// Convert HTML to Markdown
			$output .= $this->html_to_markdown( $content ) . "\n\n";
		}//end if

		$output .= $this->generate_website_links( $args, $post );

		return $output;
	}

	/**
	 * Extracts links from header and footer
	 *
	 * @return array The header and footer links
	 */
	public function extract_header_footer_links(): array {
		// Disable the admin bar temporarily to prevent admin links from leaking
		add_filter( 'show_admin_bar', '__return_false' );

		// Start output buffering to capture the header
		ob_start();
		get_header();
		$header_html = ob_get_clean();

		// Capture footer
		ob_start();
		get_footer();
		$footer_html = ob_get_clean();

		return array(
			'header' => $this->parse_navigation_html( $header_html, 'header' ),
			'footer' => $this->parse_navigation_html( $footer_html, 'footer' ),
		);
	}

	/**
	 * Parses HTML to extract navigation links
	 *
	 * @param string $html The HTML to parse
	 *
	 * @return array The navigation links
	 */
	private function parse_navigation_html( string $html ): array {
		$links = array();

		// Remove scripts, styles, and forms
		$html = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $html );
		$html = preg_replace( '/<style\b[^>]*>(.*?)<\/style>/is', '', $html );
		$html = preg_replace( '/<form\b[^>]*>(.*?)<\/form>/is', '', $html );

		// Extract all links with their text
		preg_match_all( '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER );

		foreach ( $matches as $match ) {
			$url  = $match[1];
			$text = wp_strip_all_tags( $match[2] );
			$text = trim( preg_replace( '/\s+/', ' ', $text ) );

			// Skip empty links, anchors, and javascript
			if ( empty( $text ) || str_starts_with( $url, '#' ) || str_starts_with( $url, 'javascript:' ) ||
				strlen( $text ) > 100 ) {
				// Skip very long text (likely not navigation)
				continue;
			}

			// Ignore WordPress admin and login links if found
			if ( strpos( $url, 'wp-admin' ) !== false || strpos( $url, 'wp-login.php' ) !== false ) {
				continue;
			}

			$links[] = array(
				'text' => $text,
				'url'  => $url,
			);
		}//end foreach

		// Remove duplicate links (same URL)
		$unique_links = array();
		$seen_urls    = array();

		foreach ( $links as $link ) {
			if ( ! in_array( $link['url'], $seen_urls, true ) ) {
				$unique_links[] = $link;
				$seen_urls[]    = $link['url'];
			}
		}

		return $unique_links;
	}

	/**
	 * Formats header/footer links as Markdown
	 *
	 * @param array  $links The links to format
	 * @param string $title The title of the section
	 *
	 * @return string The formatted Markdown
	 */
	private function format_navigation_markdown( array $links, string $title ): string {
		if ( empty( $links ) ) {
			return '';
		}

		$output = "## {$title}\n\n";

		foreach ( $links as $link ) {
			$output .= "- [{$link['text']}]({$link['url']})\n";
		}

		return $output . "\n";
	}

	/**
	 * Generates default llms.txt content using WordPress site data
	 *
	 * @return string Default llms.txt content
	 */
	public function generate_default_llmstxt(): string {
		$site_title       = get_bloginfo( 'name' );
		$site_description = get_bloginfo( 'description' );
		$site_url         = home_url();
		$admin_email      = get_bloginfo( 'admin_email' );

		// Get recent posts
		$recent_posts = get_posts(
			array(
				'numberposts' => 5,
				'post_status' => 'publish',
			)
		);

		// Build the default content
		$content = "# {$site_title}\n";

		if ( ! empty( $site_description ) ) {
			$content .= "> {$site_description}\n\n";
		}

		$content .= "This file provides structured information about {$site_title} for AI bots and LLM crawlers.\n\n";

		// Add site information section
		$content .= "## Site Information\n";
		$content .= "- **Website**: [{$site_title}]({$site_url})\n";

		if ( ! empty( $site_description ) ) {
			$content .= "- **Description**: {$site_description}\n";
		}

		$content .= "- **Contact**: {$admin_email}\n\n";

		// Add recent content section
		if ( ! empty( $recent_posts ) ) {
			$content .= "## Recent Content\n";
			foreach ( $recent_posts as $post ) {
				$post_url     = get_permalink( $post->ID );
				$post_title   = $this->sanitize_text( $post->post_title );
				$post_excerpt = wp_trim_words( wp_strip_all_tags( $post->post_excerpt ?: $post->post_content ), 20 );

				$content .= "- [{$post_title}]({$post_url})";
				if ( ! empty( $post_excerpt ) ) {
					$content .= ": {$post_excerpt}";
				}
				$content .= "\n";
			}
			$content .= "\n";
		}

		// Add navigation/pages section if there are published pages
		$pages = get_pages(
			array(
				'post_status' => 'publish',
				'number'      => 10,
				'sort_column' => 'menu_order',
			)
		);

		if ( ! empty( $pages ) ) {
			$content .= "## Main Pages\n";
			foreach ( $pages as $page ) {
				$page_url   = get_permalink( $page->ID );
				$page_title = $this->sanitize_text( $page->post_title );
				$content   .= "- [{$page_title}]({$page_url})\n";
			}
			$content .= "\n";
		}

		// Add navigation sections if there are any
		$content .= $this->generate_website_links(
			array(
				'include_categories' => false,
				'include_navigation' => true,
				'include_tags'       => false,
				'include_footer'     => true,
			)
		);

		// Add footer note
		$content .= "---\n\n## Additional Information\n";
		$content .= "For more information about our content and structure, please explore the links above or visit our homepage at {$site_url}.\n";

		return $content;
	}

	/**
	 * Generates post-meta information
	 *
	 * @param object $post The post-object
	 * @param string $post_type The post-type
	 *
	 * @return string The post-meta information
	 */
	private function generate_post_meta( object $post, string $post_type ): string {
		$post_id = is_object( $post ) ? $post->ID : (int) $post;

		$output  = 'Date: ' . get_the_date( 'Y-m-d', $post_id ) . "\n";
		$output .= 'Author: ' . $this->sanitize_text( get_the_author_meta( 'display_name', $post->post_author ) ) . "\n";
		$output .= 'Post Type: ' . $this->sanitize_text( $post_type ) . "\n";

		// Excerpt / summary
		$excerpt = get_the_excerpt( $post );
		$output .= 'Summary: ' . $this->sanitize_text( $excerpt ) . "\n";

		// Categories — return names (safe)
		$cat_names = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'names' ) );
		if ( is_wp_error( $cat_names ) ) {
			$cat_names = array();
		}
		$cat_names = array_map( array( $this, 'sanitize_text' ), $cat_names );
		if ( ! empty( $cat_names ) ) {
			$output .= 'Categories: ' . implode( ', ', $cat_names ) . "\n";
		}

		// Tags — return names (safe)
		$tag_names = wp_get_post_terms( $post_id, 'post_tag', array( 'fields' => 'names' ) );
		if ( is_wp_error( $tag_names ) ) {
			$tag_names = array();
		}
		$tag_names = array_map( array( $this, 'sanitize_text' ), $tag_names );
		if ( ! empty( $tag_names ) ) {
			$output .= 'Tags: ' . implode( ', ', $tag_names ) . "\n";
		}

		// Featured image (use post ID)
		$featured = get_the_post_thumbnail_url( $post_id, 'full' );
		if ( $featured ) {
			$output .= 'Featured Image: ' . esc_url( $featured ) . "\n";
		}

		return $output;
	}

	/**
	 * Generates product meta-information
	 *
	 * @param object $post The post-object
	 *
	 * @return string The product meta-information
	 */
	private function generate_product_meta( object $post ): string {

		if ( ! function_exists( 'wc_get_product' ) ) {
			return "WooCommerce not active.\n";
		}

		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			return "Invalid product.\n";
		}

		// Base product data
		$title   = $post->post_title;
		$type    = $product->get_type();
		$sku     = $product->get_sku();
		$price   = $product->is_type( 'variable' ) ? $product->get_price_html() : $product->get_price();
		$summary = wp_strip_all_tags( $product->get_short_description() );
		$stock   = $product->is_in_stock() ? 'In Stock' : 'Out of Stock';

		// Categories
		$categories = wp_get_post_terms( $post->ID, 'product_cat', array( 'fields' => 'names' ) );
		$categories = $categories ? implode( ', ', $categories ) : '';

		// Tags
		$tags = wp_get_post_terms( $post->ID, 'product_tag', array( 'fields' => 'names' ) );
		$tags = $tags ? implode( ', ', $tags ) : '';

		// Attributes
		$attributes_list = array();
		foreach ( $product->get_attributes() as $attribute ) {
			$name              = wc_attribute_label( $attribute->get_name() );
			$value             = implode( ', ', wc_get_product_terms( $post->ID, $attribute->get_name(), array( 'fields' => 'names' ) ) );
			$attributes_list[] = "$name: $value";
		}

		// Images
		$images = array();
		if ( has_post_thumbnail( $post ) ) {
			$images[] = get_the_post_thumbnail_url( $post, 'full' );
		}
		$gallery = $product->get_gallery_image_ids();
		if ( $gallery ) {
			foreach ( $gallery as $img_id ) {
				$images[] = wp_get_attachment_image_url( $img_id, 'full' );
			}
		}

		// Build YAML-style metadata header
		$output  = "Type: product\n";
		$output .= 'Title: ' . $this->sanitize_text( $title ) . "\n";
		$output .= 'Summary: ' . $this->sanitize_text( $summary ) . "\n";
		$output .= 'Sku: ' . $this->sanitize_text( $sku ) . "\n";
		$output .= 'Price: ' . $this->sanitize_text( $price ) . "\n";
		$output .= 'In Stock: ' . $this->sanitize_text( $stock ) . "\n";
		$output .= 'Product_type: ' . $this->sanitize_text( $type ) . "\n";
		if ( $categories ) {
			$output .= 'Categories: ' . $this->sanitize_text( $categories ) . "\n";
		}
		if ( $tags ) {
			$output .= 'Tags: ' . $this->sanitize_text( $tags ) . "\n";
		}

		if ( ! empty( $attributes_list ) ) {
			$output .= "Attributes:\n";
			foreach ( $attributes_list as $attr ) {
				$output .= '  - ' . $this->sanitize_text( $attr ) . "\n";
			}
		}

		if ( ! empty( $images ) ) {
			$output .= "Images:\n";
			foreach ( $images as $img ) {
				$output .= '  - ' . esc_url( $img ) . "\n";
			}
		}

		// end header
		return $output;
	}
}
