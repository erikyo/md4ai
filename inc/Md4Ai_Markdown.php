<?php

namespace Md4Ai;

/**
 * Markdown generation and conversion class
 */
class Md4Ai_Markdown {

	/**
	 * Post-meta key for custom Markdown
	 */
	private string $meta_key = '_md4ai_custom_markdown';

	/**
	 * Cache instance
	 */
	private $cache;

	/**
	 * DOM Extractor instance
	 */
	private $dom_extractor;

	public function __construct( $cache ) {
		$this->cache         = $cache;
		$this->dom_extractor = new Md4Ai_DOM_Extractor();
	}

	/**
	 * Get the meta key
	 */
	public function get_meta_key(): string {
		return $this->meta_key;
	}

	/**
	 * Gets markdown for a post - checks custom meta first, then generates
	 */
	public function get_post_markdown( $post ) {
		// Get extraction mode from settings
		$options             = get_option( MD4AI_OPTION );
		$extraction_mode     = $options['extraction_mode'] ?? 'advanced';
		$use_full_extraction = ( $extraction_mode === 'advanced' );

		/**
		 * Filter to modify post arguments
		 *
		 * @param array $args The arguments to pass to the convert_post_to_markdown method
		 */
		$args = apply_filters(
			'md4ai_post_args',
			array(
				'include_navigation'  => true,
				'include_categories'  => true,
				'include_tags'        => true,
				'include_footer'      => true,
				'use_full_extraction' => $use_full_extraction,
			)
		);

		// Check if custom markdown exists
		$args['content'] = get_post_meta( $post->ID, $this->meta_key, true );

		// Generate from post content
		return $this->convert_post_to_markdown( $post, $args );
	}

	/**
	 * Get complete post markdown with full DOM extraction
	 * Extracts everything: schema markup, reviews, meta tags, FAQ, tables, etc.
	 */
	public function get_complete_post_markdown( $post ) {
		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}

		if ( ! $post ) {
			return '';
		}

		// Check if custom markdown exists
		$custom_markdown = get_post_meta( $post->ID, $this->meta_key, true );
		if ( ! empty( $custom_markdown ) ) {
			return $custom_markdown;
		}

		// Extract complete DOM content
		$extracted = $this->dom_extractor->extract_complete_content( $post );

		// Convert to markdown
		$markdown = $this->dom_extractor->convert_to_markdown( $extracted );

		// Add navigation and footer links
		$args = array(
			'include_navigation' => true,
			'include_categories' => false,  // Already included in DOM extraction
			'include_tags'       => false,        // Already included in DOM extraction
			'include_footer'     => true,
		);

		$markdown .= $this->generate_website_links( $args, $post );

		return $markdown;
	}

	/**
	 * Basic HTML to Markdown conversion
	 */
	private function html_to_markdown( $html ) {
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

		// Remove all other HTML tags
		$html = wp_strip_all_tags( $html );

		// Decode HTML entities
		$html = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		// Clean up multiple spaces
		$html = preg_replace( '/\n\s*\n\s*\n/', "\n\n", $html );
		$html = trim( $html );

		return $html;
	}

	/**
	 * Generates website links
	 *
	 * @param array  $args The arguments to pass to the function
	 * @param object $post The post object
	 *
	 * @return string The generated website links
	 */
	public function generate_website_links( $args, $post = false ) {
		$output = '';

		// Categories and tags
		if ( $post && $args['include_categories'] ) {
			$categories = get_the_category( $post->ID );
			if ( ! empty( $categories ) ) {
				$output .= "---\n\n";
				$output .= "## Categories\n\n";
				foreach ( $categories as $cat ) {
					$output .= '- ' . esc_html( $cat->name ) . "\n";
				}
				$output .= "\n";
			}
		}

		// Get header/footer data (cached)
		if ( $args['include_navigation'] === true ) {
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
					$output .= '- ' . esc_html( $tag->name ) . "\n";
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
	 */
	public function convert_post_to_markdown( $post, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'content'             => false,
				'include_navigation'  => false,
				'include_categories'  => false,
				'include_tags'        => false,
				'include_footer'      => false,
				'use_full_extraction' => true,  // Enable full extraction by default
			)
		);

		/**
		 * Filter to enable/disable full DOM extraction
		 *
		 * Set to false to use the basic extraction method
		 */
		$use_full_extraction = apply_filters( 'md4ai_use_full_extraction', $args['use_full_extraction'] );

		// If full extraction is enabled and no custom content, use the complete extractor
		if ( $use_full_extraction && empty( $args['content'] ) ) {
			return $this->get_complete_post_markdown( $post );
		}

		// Otherwise, use the basic extraction method (backward compatibility)
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

			// Title - Decode HTML entities to get clean apostrophes
			$clean_title = html_entity_decode( $post->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$output     .= '# ' . $clean_title . "\n\n";

			// The Page Meta information
			$output .= '**URL:** ' . esc_url( get_permalink( $post ) ) . "\n";

			// Generate meta information based on post type
			if ( $post_type !== 'product' ) {
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

			// Execute shortcodes to generate HTML content (important for product displays)
			$content = do_shortcode( $content );

			// Convert HTML to Markdown (already handles entity decoding)
			$output .= $this->html_to_markdown( $content ) . "\n\n";
		}

		$output .= $this->generate_website_links( $args, $post );

		// Final cleanup: decode any remaining HTML entities (especially for translated content)
		$output = html_entity_decode( $output, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return $output;
	}

	/**
	 * Extracts links from header and footer
	 */
	public function extract_header_footer_links() {
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
	 */
	private function parse_navigation_html( $html, $type = 'header' ) {
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
			// Decode HTML entities (especially for translated content)
			$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$text = trim( preg_replace( '/\s+/', ' ', $text ) );

			// Skip empty links, anchors, and javascript
			if ( empty( $text ) || str_starts_with( $url, '#' ) || str_starts_with( $url, 'javascript:' ) ||
				strlen( $text ) > 100 ) { // Skip very long text (likely not navigation)
				continue;
			}

			// 🔒 SECURITY: Filter out WordPress admin URLs and other sensitive links
			$admin_patterns = array(
				'/wp-admin/',
				'/wp-login.php',
				'?trp-edit-translation',
				'admin.php',
				'edit.php',
				'post-new.php',
				'user-new.php',
				'options-general.php',
				'update-core.php',
				'edit-comments.php',
				'media-new.php',
			);

			// 🔒 Filter out external WordPress/plugin documentation & development links
			$external_patterns = array(
				'wordpress.org',
				'learn.wordpress.org',
				'fr.wordpress.org',
				'wpfr.net',
				'forums.wordpress.com',
				'yoa.st',
				'wpcode.com/docs',
				'twilio.com',
				'compile_sass=',
				'autorecompile=',
				'utm_source=',
				'utm_medium=',
				'utm_campaign=',
				'dpb-mode=',
				'dpb-origin=',
				'dp-ra=',
				'dpb-create-new=',
				'dp-id=',
				'dp-step=',
				'dp-pch=',
				'dpb-oeid=',
				'trp-edit-translation=',
				'_villatheme_nonce=',
				'perfmatters',
				'action=suspend_transients',
				'_wpnonce=',
				'search.google.com',
				'developers.google.com',
				'developers.facebook.com',
				'//developers.',
			);

			$is_filtered_url = false;

			// Check admin patterns
			foreach ( $admin_patterns as $pattern ) {
				if ( strpos( $url, $pattern ) !== false ) {
					$is_filtered_url = true;
					break;
				}
			}

			// Check external/development patterns
			if ( ! $is_filtered_url ) {
				foreach ( $external_patterns as $pattern ) {
					if ( strpos( $url, $pattern ) !== false ) {
						$is_filtered_url = true;
						break;
					}
				}
			}

			// Skip filtered URLs
			if ( $is_filtered_url ) {
				continue;
			}

			$links[] = array(
				'text' => $text,
				'url'  => $url,
			);
		}

		// Remove duplicate links (same URL)
		$unique_links = array();
		$seen_urls    = array();

		foreach ( $links as $link ) {
			if ( ! in_array( $link['url'], $seen_urls ) ) {
				$unique_links[] = $link;
				$seen_urls[]    = $link['url'];
			}
		}

		return $unique_links;
	}

	/**
	 * Formats header/footer links as Markdown
	 *
	 * @return string The formatted Markdown
	 */
	private function format_navigation_markdown( $links, $title ): string {
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
				$post_title   = esc_html( $post->post_title );
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
				$page_title = esc_html( $page->post_title );
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

	private function generate_post_meta( $post, $post_type ) {
		$post_id = is_object( $post ) ? $post->ID : (int) $post;

		$output  = 'Date: ' . get_the_date( 'Y-m-d', $post_id ) . "\n";
		$output .= 'Author: ' . esc_html( get_the_author_meta( 'display_name', $post->post_author ) ) . "\n";
		$output .= 'Post Type: ' . esc_html( $post_type ) . "\n";

		// Excerpt / summary - Clean HTML and decode entities
		$excerpt = get_the_excerpt( $post );
		if ( ! empty( $excerpt ) ) {
			// Strip all HTML tags first
			$excerpt = wp_strip_all_tags( $excerpt );
			// Decode HTML entities (converts &nbsp; to space, &rsquo; to ', etc.)
			$excerpt = html_entity_decode( $excerpt, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			// Trim extra whitespace
			$excerpt = trim( preg_replace( '/\s+/', ' ', $excerpt ) );
			$output .= 'Summary: ' . $excerpt . "\n";
		}

		// Categories — return names (safe)
		$cat_names = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'names' ) );
		if ( is_wp_error( $cat_names ) ) {
			$cat_names = array();
		}
		$cat_names = array_map( 'esc_html', $cat_names );
		if ( ! empty( $cat_names ) ) {
			$output .= 'Categories: ' . implode( ', ', $cat_names ) . "\n";
		}

		// Tags — return names (safe)
		$tag_names = wp_get_post_terms( $post_id, 'post_tag', array( 'fields' => 'names' ) );
		if ( is_wp_error( $tag_names ) ) {
			$tag_names = array();
		}
		$tag_names = array_map( 'esc_html', $tag_names );
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

	private function generate_product_meta( $post ) {

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
		$output  = "---\n";
		$output .= "Type: product\n";
		$output .= 'Title: ' . esc_html( $title ) . "\n";
		$output .= 'Summary: ' . esc_html( $summary ) . "\n";
		$output .= 'Sku: ' . esc_html( $sku ) . "\n";
		$output .= 'Price: ' . esc_html( $price ) . "\n";
		$output .= 'In Stock: ' . esc_html( $stock ) . "\n";
		$output .= 'Product_type: ' . esc_html( $type ) . "\n";
		if ( $categories ) {
			$output .= 'Categories: ' . esc_html( $categories ) . "\n";
		}
		if ( $tags ) {
			$output .= 'Tags: ' . esc_html( $tags ) . "\n";
		}

		if ( ! empty( $attributes_list ) ) {
			$output .= "Attributes:\n";
			foreach ( $attributes_list as $attr ) {
				$output .= '  - ' . esc_html( $attr ) . "\n";
			}
		}

		if ( ! empty( $images ) ) {
			$output .= "Images:\n";
			foreach ( $images as $img ) {
				$output .= '  - ' . esc_url( $img ) . "\n";
			}
		}

		$output .= "---\n\n"; // end header

		return $output;
	}
}
