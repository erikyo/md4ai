<?php

namespace Md4Ai;

/**
 * Enhanced DOM Content Extractor
 * Extracts complete DOM content including JS-rendered elements, reviews, schema markup, etc.
 */
class Md4Ai_DOM_Extractor {

	/**
	 * Extract complete DOM content from a post/page
	 *
	 * @param int|WP_Post $post The post object or ID
	 * @return array Complete extracted content
	 */
	public function extract_complete_content( $post ) {
		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}

		if ( ! $post ) {
			return array();
		}

		$extracted = array(
			'basic_content'    => $this->extract_basic_content( $post ),
			'schema_markup'    => $this->extract_schema_markup( $post ),
			'meta_tags'        => $this->extract_meta_tags( $post ),
			'rendered_content' => $this->extract_rendered_html( $post ),
			'reviews'          => $this->extract_reviews( $post ),
			'product_data'     => $this->extract_product_data( $post ),
			'comments'         => $this->extract_comments( $post ),
			'custom_fields'    => $this->extract_custom_fields( $post ),
			'faq'              => $this->extract_faq_content( $post ),
			'tables'           => $this->extract_tables( $post ),
		);

		return $extracted;
	}

	/**
	 * Extract basic post content
	 */
	private function extract_basic_content( $post ) {
		return array(
			'title'    => html_entity_decode( $post->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			'content'  => apply_filters( 'the_content', $post->post_content ),
			'excerpt'  => get_the_excerpt( $post ),
			'url'      => get_permalink( $post ),
			'type'     => get_post_type( $post ),
			'date'     => get_the_date( 'Y-m-d', $post ),
			'modified' => get_the_modified_date( 'Y-m-d', $post ),
		);
	}

	/**
	 * Extract all schema markup (JSON-LD) from the page
	 */
	private function extract_schema_markup( $post ) {
		$schemas = array();

		// Capture the full page HTML with all plugins active
		$html = $this->get_full_page_html( $post );

		// First, check for dynamically loaded widgets (like Trustmate)
		$widget_schemas = $this->extract_external_widget_schemas( $html );
		if ( ! empty( $widget_schemas ) ) {
			$schemas = array_merge( $schemas, $widget_schemas );
		}

		// Extract all JSON-LD scripts - more flexible pattern to catch variations
		// This pattern matches script tags with type="application/ld+json" regardless of attribute order
		preg_match_all( '/<script\s+[^>]*type\s*=\s*["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches );

		if ( ! empty( $matches[1] ) ) {
			foreach ( $matches[1] as $json ) {
				// Clean up the JSON content
				$json = trim( $json );

				// Decode HTML entities that might be in the JSON
				$json = html_entity_decode( $json, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

				$decoded = json_decode( $json, true );
				if ( $decoded ) {
					$schemas[] = $decoded;
				} else {
					// Log if JSON decode fails for debugging
					error_log( 'MD4AI: Failed to decode JSON-LD: ' . json_last_error_msg() );
				}
			}
		}

		// Also try alternative pattern where type attribute might come first without other attributes
		preg_match_all( '/<script\s+type\s*=\s*["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches2 );

		if ( ! empty( $matches2[1] ) ) {
			foreach ( $matches2[1] as $json ) {
				// Clean up the JSON content
				$json = trim( $json );

				// Decode HTML entities
				$json = html_entity_decode( $json, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

				$decoded = json_decode( $json, true );
				if ( $decoded ) {
					// Check if we already have this schema (avoid duplicates)
					$json_string    = json_encode( $decoded );
					$already_exists = false;
					foreach ( $schemas as $existing ) {
						if ( json_encode( $existing ) === $json_string ) {
							$already_exists = true;
							break;
						}
					}
					if ( ! $already_exists ) {
						$schemas[] = $decoded;
					}
				}
			}
		}

		// Also try to get Yoast SEO schema if available
		if ( function_exists( 'YoastSEO' ) ) {
			$yoast_schema = $this->get_yoast_schema( $post );
			if ( $yoast_schema ) {
				$schemas[] = $yoast_schema;
			}
		}

		return $schemas;
	}

	/**
	 * Extract schemas from external widgets (JavaScript-injected content)
	 * Handles widgets like Trustmate that dynamically inject JSON-LD
	 */
	private function extract_external_widget_schemas( $html ) {
		$schemas = array();

		// Detect Trustmate widget
		// Pattern: <script ... src="https://en.trustmate.io/widget/api/{widget-id}/script" ...>
		if ( preg_match_all( '/https:\/\/(?:en\.)?trustmate\.io\/widget\/api\/([a-f0-9\-]+)\/script/i', $html, $trustmate_matches ) ) {
			foreach ( $trustmate_matches[1] as $widget_id ) {
				$trustmate_data = $this->fetch_trustmate_data( $widget_id );
				if ( $trustmate_data ) {
					$schemas[] = $trustmate_data;
				}
			}
		}

		// Could add more widget detectors here (e.g., Reviews.io, Yotpo, etc.)

		return $schemas;
	}

	/**
	 * Fetch Trustmate review data
	 */
	private function fetch_trustmate_data( $widget_id ) {
		static $cache = array();

		if ( isset( $cache[ $widget_id ] ) ) {
			return $cache[ $widget_id ];
		}

		// Try to fetch the widget JavaScript and extract the data
		$script_url = "https://en.trustmate.io/widget/api/{$widget_id}/script";

		$response = wp_remote_get(
			$script_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'MD4AI: Failed to fetch Trustmate widget: ' . $response->get_error_message() );
			return null;
		}

		$script_content = wp_remote_retrieve_body( $response );

		// Extract JSON-LD from the script content
		// Trustmate typically injects JSON-LD via document.write or innerHTML
		if ( preg_match( '/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $script_content, $json_match ) ) {
			$json = trim( $json_match[1] );

			// Decode any escaped quotes or special characters
			$json = stripslashes( $json );
			$json = html_entity_decode( $json, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

			$decoded = json_decode( $json, true );
			if ( $decoded ) {
				$cache[ $widget_id ] = $decoded;
				return $decoded;
			}
		}

		// Alternative: Try to extract from JavaScript variable assignment
		// Pattern: var someData = {...}
		if ( preg_match( '/(?:var|const|let)\s+\w+\s*=\s*(\{[^;]+\});/s', $script_content, $var_match ) ) {
			$json    = trim( $var_match[1] );
			$decoded = json_decode( $json, true );
			if ( $decoded && isset( $decoded['@type'] ) ) {
				$cache[ $widget_id ] = $decoded;
				return $decoded;
			}
		}

		// Alternative: Extract from document.write or innerHTML
		if ( preg_match( '/(?:document\.write|innerHTML\s*=\s*)["\'].*?(<script[^>]+type=["\']application\/ld\+json["\'][^>]*>.*?<\/script>).*?["\']/', $script_content, $write_match ) ) {
			// Decode escaped content
			$escaped_html = $write_match[1];
			$escaped_html = str_replace( array( '\\/', '\\n', '\\t', '\\"', "\\'" ), array( '/', "\n", "\t", '"', "'" ), $escaped_html );

			if ( preg_match( '/<script[^>]*>(.*?)<\/script>/is', $escaped_html, $json_match ) ) {
				$json = trim( $json_match[1] );
				$json = html_entity_decode( $json, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

				$decoded = json_decode( $json, true );
				if ( $decoded ) {
					$cache[ $widget_id ] = $decoded;
					return $decoded;
				}
			}
		}

		error_log( 'MD4AI: Could not extract JSON-LD from Trustmate widget: ' . $widget_id );
		return null;
	}

	/**
	 * Extract meta tags (Open Graph, Twitter Cards, SEO meta)
	 */
	private function extract_meta_tags( $post ) {
		$html      = $this->get_full_page_html( $post );
		$meta_tags = array();

		// Extract all meta tags
		preg_match_all( '/<meta[^>]+>/i', $html, $matches );

		if ( ! empty( $matches[0] ) ) {
			foreach ( $matches[0] as $meta ) {
				// Extract property/name and content
				if ( preg_match( '/(?:property|name)=["\']([^"\']+)["\']/', $meta, $prop ) &&
					preg_match( '/content=["\']([^"\']+)["\']/', $meta, $content ) ) {
					$meta_tags[ $prop[1] ] = html_entity_decode( $content[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				}
			}
		}

		return $meta_tags;
	}

	/**
	 * Extract rendered HTML content with all shortcodes and dynamic elements
	 */
	private function extract_rendered_html( $post ) {
		global $wp_query, $wp;

		// Backup current query
		$original_query = $wp_query;
		$original_post  = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;

		// Set up post data
		$GLOBALS['post'] = $post;
		setup_postdata( $post );

		// Start output buffering
		ob_start();

		// Get the content with all filters applied (includes shortcodes, gutenberg blocks, etc.)
		$content = apply_filters( 'the_content', $post->post_content );
		echo do_shortcode( $content );

		// If WooCommerce product, also render product content
		if ( get_post_type( $post ) === 'product' && function_exists( 'woocommerce_template_single_price' ) ) {
			echo '<div class="wc-product-additional-info">';
			woocommerce_template_single_price();
			woocommerce_template_single_add_to_cart();
			woocommerce_output_product_data_tabs();
			echo '</div>';
		}

		$rendered = ob_get_clean();

		// Restore original query
		$wp_query        = $original_query;
		$GLOBALS['post'] = $original_post;
		wp_reset_postdata();

		return $rendered;
	}

	/**
	 * Extract reviews/comments/testimonials
	 */
	private function extract_reviews( $post ) {
		$reviews = array();

		// WooCommerce Product Reviews
		if ( get_post_type( $post ) === 'product' && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $post->ID );
			if ( $product ) {
				$review_count   = $product->get_review_count();
				$average_rating = $product->get_average_rating();

				$reviews['woocommerce'] = array(
					'count'          => $review_count,
					'average_rating' => $average_rating,
					'reviews'        => array(),
				);

				// Get actual reviews
				$comments = get_comments(
					array(
						'post_id' => $post->ID,
						'status'  => 'approve',
						'type'    => 'review',
					)
				);

				foreach ( $comments as $comment ) {
					$rating                              = get_comment_meta( $comment->comment_ID, 'rating', true );
					$reviews['woocommerce']['reviews'][] = array(
						'author'   => $comment->comment_author,
						'date'     => $comment->comment_date,
						'rating'   => $rating,
						'content'  => wp_strip_all_tags( $comment->comment_content ),
						'verified' => get_comment_meta( $comment->comment_ID, 'verified', true ),
					);
				}
			}
		}

		return $reviews;
	}

	/**
	 * Extract complete product data for WooCommerce
	 */
	private function extract_product_data( $post ) {
		if ( get_post_type( $post ) !== 'product' || ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			return null;
		}

		$data = array(
			'name'              => $product->get_name(),
			'sku'               => $product->get_sku(),
			'type'              => $product->get_type(),
			'price'             => $product->get_price(),
			'regular_price'     => $product->get_regular_price(),
			'sale_price'        => $product->get_sale_price(),
			'price_html'        => $product->get_price_html(),
			'stock_status'      => $product->get_stock_status(),
			'stock_quantity'    => $product->get_stock_quantity(),
			'weight'            => $product->get_weight(),
			'dimensions'        => array(
				'length' => $product->get_length(),
				'width'  => $product->get_width(),
				'height' => $product->get_height(),
			),
			'short_description' => wp_strip_all_tags( $product->get_short_description() ),
			'description'       => wp_strip_all_tags( $product->get_description() ),
			'categories'        => wp_get_post_terms( $post->ID, 'product_cat', array( 'fields' => 'names' ) ),
			'tags'              => wp_get_post_terms( $post->ID, 'product_tag', array( 'fields' => 'names' ) ),
			'attributes'        => array(),
			'images'            => array(),
			'gallery'           => array(),
		);

		// Extract attributes
		foreach ( $product->get_attributes() as $attribute ) {
			$name   = wc_attribute_label( $attribute->get_name() );
			$values = array();

			if ( $attribute->is_taxonomy() ) {
				$terms  = wc_get_product_terms( $post->ID, $attribute->get_name(), array( 'fields' => 'names' ) );
				$values = $terms;
			} else {
				$values = $attribute->get_options();
			}

			$data['attributes'][ $name ] = $values;
		}

		// Extract images
		if ( has_post_thumbnail( $post->ID ) ) {
			$image_id         = get_post_thumbnail_id( $post->ID );
			$data['images'][] = array(
				'url' => wp_get_attachment_url( $image_id ),
				'alt' => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
			);
		}

		// Gallery images
		$gallery_ids = $product->get_gallery_image_ids();
		foreach ( $gallery_ids as $img_id ) {
			$data['gallery'][] = array(
				'url' => wp_get_attachment_url( $img_id ),
				'alt' => get_post_meta( $img_id, '_wp_attachment_image_alt', true ),
			);
		}

		// Variations for variable products
		if ( $product->is_type( 'variable' ) ) {
			$data['variations'] = array();
			foreach ( $product->get_available_variations() as $variation ) {
				$data['variations'][] = array(
					'id'            => $variation['variation_id'],
					'sku'           => $variation['sku'],
					'price'         => $variation['display_price'],
					'regular_price' => $variation['display_regular_price'],
					'attributes'    => $variation['attributes'],
					'stock_status'  => $variation['is_in_stock'] ? 'instock' : 'outofstock',
				);
			}
		}

		return $data;
	}

	/**
	 * Extract comments/discussions
	 */
	private function extract_comments( $post ) {
		$comments = get_comments(
			array(
				'post_id' => $post->ID,
				'status'  => 'approve',
				'type'    => 'comment',
			)
		);

		$extracted = array();
		foreach ( $comments as $comment ) {
			$extracted[] = array(
				'author'  => $comment->comment_author,
				'date'    => $comment->comment_date,
				'content' => wp_strip_all_tags( $comment->comment_content ),
				'parent'  => $comment->comment_parent,
			);
		}

		return $extracted;
	}

	/**
	 * Extract custom fields and ACF fields
	 */
	private function extract_custom_fields( $post ) {
		$fields = array();

		// Standard custom fields
		$custom_fields = get_post_custom( $post->ID );
		foreach ( $custom_fields as $key => $values ) {
			// Skip private fields and WordPress internal fields
			if ( substr( $key, 0, 1 ) === '_' ) {
				continue;
			}
			$fields[ $key ] = count( $values ) === 1 ? $values[0] : $values;
		}

		// ACF fields if available
		if ( function_exists( 'get_fields' ) ) {
			$acf_fields = get_fields( $post->ID );
			if ( $acf_fields ) {
				$fields['acf'] = $acf_fields;
			}
		}

		return $fields;
	}

	/**
	 * Extract FAQ content (from various FAQ plugins)
	 */
	private function extract_faq_content( $post ) {
		$faqs    = array();
		$content = $post->post_content;

		// Extract FAQ from Gutenberg FAQ blocks
		preg_match_all( '/<!-- wp:yoast-seo\/faq-block.*?-->(.*?)<!-- \/wp:yoast-seo\/faq-block -->/s', $content, $matches );
		if ( ! empty( $matches[1] ) ) {
			foreach ( $matches[1] as $faq_block ) {
				preg_match_all( '/"question":"([^"]+)".*?"answer":"([^"]+)"/s', $faq_block, $qa );
				if ( ! empty( $qa[1] ) ) {
					for ( $i = 0; $i < count( $qa[1] ); $i++ ) {
						$faqs[] = array(
							'question' => html_entity_decode( $qa[1][ $i ], ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
							'answer'   => html_entity_decode( $qa[2][ $i ], ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
						);
					}
				}
			}
		}

		// Extract from schema markup
		$html = $this->get_full_page_html( $post );
		preg_match_all( '/"@type"\s*:\s*"FAQPage".*?"mainEntity"\s*:\s*\[(.*?)\]/s', $html, $schema_faq );
		if ( ! empty( $schema_faq[1] ) ) {
			preg_match_all( '/"name"\s*:\s*"([^"]+)".*?"text"\s*:\s*"([^"]+)"/s', $schema_faq[1][0], $qa );
			if ( ! empty( $qa[1] ) ) {
				for ( $i = 0; $i < count( $qa[1] ); $i++ ) {
					$faqs[] = array(
						'question' => html_entity_decode( $qa[1][ $i ], ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
						'answer'   => html_entity_decode( $qa[2][ $i ], ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					);
				}
			}
		}

		return $faqs;
	}

	/**
	 * Extract tables from content
	 */
	private function extract_tables( $post ) {
		$content = apply_filters( 'the_content', $post->post_content );
		$tables  = array();

		preg_match_all( '/<table[^>]*>(.*?)<\/table>/is', $content, $matches );

		if ( ! empty( $matches[0] ) ) {
			foreach ( $matches[0] as $table_html ) {
				$table_data = $this->parse_html_table( $table_html );
				if ( ! empty( $table_data ) ) {
					$tables[] = $table_data;
				}
			}
		}

		return $tables;
	}

	/**
	 * Parse HTML table to structured data
	 */
	private function parse_html_table( $html ) {
		$rows = array();

		// Extract table rows
		preg_match_all( '/<tr[^>]*>(.*?)<\/tr>/is', $html, $tr_matches );

		foreach ( $tr_matches[1] as $row_html ) {
			$cells = array();

			// Extract cells (th or td)
			preg_match_all( '/<t[hd][^>]*>(.*?)<\/t[hd]>/is', $row_html, $cell_matches );

			foreach ( $cell_matches[1] as $cell_html ) {
				$cells[] = wp_strip_all_tags( $cell_html );
			}

			if ( ! empty( $cells ) ) {
				$rows[] = $cells;
			}
		}

		return $rows;
	}

	/**
	 * Get full page HTML by making an internal request
	 */
	private function get_full_page_html( $post ) {
		static $cache = array();

		if ( isset( $cache[ $post->ID ] ) ) {
			return $cache[ $post->ID ];
		}

		$url = get_permalink( $post->ID );

		// Use wp_remote_get to fetch the full rendered page
		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 15,
				'sslverify' => false, // For local development
				'headers'   => array(
					'User-Agent' => 'WordPress/md4ai-bot',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			// Fallback to basic content
			return apply_filters( 'the_content', $post->post_content );
		}

		$html               = wp_remote_retrieve_body( $response );
		$cache[ $post->ID ] = $html;

		return $html;
	}

	/**
	 * Get Yoast SEO schema if available
	 */
	private function get_yoast_schema( $post ) {
		// Don't try to extract Yoast schema - it will be in the HTML already
		// The JSON-LD extraction from HTML is sufficient
		return null;
	}

	/**
	 * Convert extracted data to markdown format
	 */
	public function convert_to_markdown( $extracted_data ) {
		$markdown = '';

		// Basic content
		if ( ! empty( $extracted_data['basic_content'] ) ) {
			$basic     = $extracted_data['basic_content'];
			$markdown .= '# ' . $basic['title'] . "\n\n";
			$markdown .= '**URL:** ' . $basic['url'] . "\n";
			$markdown .= '**Type:** ' . $basic['type'] . "\n";
			$markdown .= '**Date:** ' . $basic['date'] . "\n";
			$markdown .= '**Modified:** ' . $basic['modified'] . "\n\n";

			if ( ! empty( $basic['excerpt'] ) ) {
				$markdown .= '**Summary:** ' . wp_strip_all_tags( $basic['excerpt'] ) . "\n\n";
			}

			$markdown .= "---\n\n";
		}

		// Meta tags (SEO data)
		if ( ! empty( $extracted_data['meta_tags'] ) ) {
			$markdown .= "## SEO Metadata\n\n";
			foreach ( $extracted_data['meta_tags'] as $name => $content ) {
				// Only include important meta tags
				if ( strpos( $name, 'og:' ) === 0 || strpos( $name, 'twitter:' ) === 0 ||
					in_array( $name, array( 'description', 'keywords', 'author' ) ) ) {
					$markdown .= "- **{$name}:** {$content}\n";
				}
			}
			$markdown .= "\n";
		}

		// Product data
		if ( ! empty( $extracted_data['product_data'] ) ) {
			$markdown .= "## Product Information\n\n";
			$product   = $extracted_data['product_data'];

			$markdown .= '- **Name:** ' . $product['name'] . "\n";
			$markdown .= '- **SKU:** ' . $product['sku'] . "\n";
			$markdown .= '- **Price:** ' . strip_tags( $product['price_html'] ) . "\n";
			$markdown .= '- **Stock:** ' . $product['stock_status'] . "\n";

			if ( ! empty( $product['short_description'] ) ) {
				$markdown .= '- **Description:** ' . $product['short_description'] . "\n";
			}

			if ( ! empty( $product['categories'] ) ) {
				$markdown .= '- **Categories:** ' . implode( ', ', $product['categories'] ) . "\n";
			}

			if ( ! empty( $product['attributes'] ) ) {
				$markdown .= "\n### Attributes\n\n";
				foreach ( $product['attributes'] as $name => $values ) {
					$values_str = is_array( $values ) ? implode( ', ', $values ) : $values;
					$markdown  .= "- **{$name}:** {$values_str}\n";
				}
			}

			$markdown .= "\n";
		}

		// Main content
		if ( ! empty( $extracted_data['rendered_content'] ) ) {
			$markdown .= "## Content\n\n";
			// Use the html_to_markdown from Md4Ai_Markdown if available
			$markdown .= $this->simple_html_to_markdown( $extracted_data['rendered_content'] );
			$markdown .= "\n\n";
		}

		// Reviews
		if ( ! empty( $extracted_data['reviews']['woocommerce']['reviews'] ) ) {
			$reviews   = $extracted_data['reviews']['woocommerce'];
			$markdown .= "## Customer Reviews\n\n";
			$markdown .= '**Average Rating:** ' . $reviews['average_rating'] . " / 5\n";
			$markdown .= '**Total Reviews:** ' . $reviews['count'] . "\n\n";

			foreach ( $reviews['reviews'] as $review ) {
				$markdown .= '### Review by ' . $review['author'] . "\n";
				$markdown .= '**Rating:** ' . $review['rating'] . " / 5\n";
				$markdown .= '**Date:** ' . $review['date'] . "\n";
				if ( $review['verified'] ) {
					$markdown .= "**Verified Purchase**\n";
				}
				$markdown .= "\n" . $review['content'] . "\n\n";
			}
		}

		// FAQ
		if ( ! empty( $extracted_data['faq'] ) ) {
			$markdown .= "## Frequently Asked Questions\n\n";
			foreach ( $extracted_data['faq'] as $faq ) {
				$markdown .= '### ' . $faq['question'] . "\n\n";
				$markdown .= $faq['answer'] . "\n\n";
			}
		}

		// Tables
		if ( ! empty( $extracted_data['tables'] ) ) {
			$markdown .= "## Data Tables\n\n";
			foreach ( $extracted_data['tables'] as $table ) {
				if ( ! empty( $table ) ) {
					// First row as header
					if ( count( $table ) > 0 ) {
						$markdown .= '| ' . implode( ' | ', $table[0] ) . " |\n";
						$markdown .= '|' . str_repeat( ' --- |', count( $table[0] ) ) . "\n";

						for ( $i = 1; $i < count( $table ); $i++ ) {
							$markdown .= '| ' . implode( ' | ', $table[ $i ] ) . " |\n";
						}
						$markdown .= "\n";
					}
				}
			}
		}

		// Schema markup
		if ( ! empty( $extracted_data['schema_markup'] ) ) {
			$markdown .= "## Structured Data (Schema.org)\n\n";
			$markdown .= "```json\n";
			$markdown .= json_encode( $extracted_data['schema_markup'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			$markdown .= "\n```\n\n";
		}

		// Comments
		if ( ! empty( $extracted_data['comments'] ) ) {
			$markdown .= "## Comments\n\n";
			foreach ( $extracted_data['comments'] as $comment ) {
				$markdown .= '**' . $comment['author'] . '** (' . $comment['date'] . "):\n";
				$markdown .= $comment['content'] . "\n\n";
			}
		}

		return $markdown;
	}

	/**
	 * Simple HTML to Markdown conversion
	 */
	private function simple_html_to_markdown( $html ) {
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

		// Links
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

		// Divs and spans
		$html = preg_replace( '/<\/?div[^>]*>/i', "\n", $html );
		$html = preg_replace( '/<\/?span[^>]*>/i', '', $html );

		// Remove all other HTML tags
		$html = wp_strip_all_tags( $html );

		// Decode HTML entities
		$html = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		// Clean up multiple newlines
		$html = preg_replace( '/\n\s*\n\s*\n/', "\n\n", $html );
		$html = trim( $html );

		return $html;
	}
}
