<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Keywords_Everywhere' ) ) {
	final class Chout_AIO_Keywords_Everywhere {
		public static function init() {
			add_action( 'wp_head', array( __CLASS__, 'output_meta_keywords' ), 1 );
		}

		public static function output_meta_keywords() {
			$keywords = array();

			if ( is_single() && ! is_singular( 'product' ) ) {
				$post_id            = get_the_ID();
				$rank_math_keywords = get_post_meta( $post_id, 'rank_math_focus_keyword', true );

				if ( ! empty( $rank_math_keywords ) ) {
					self::print_keywords( array_map( 'trim', explode( ',', $rank_math_keywords ) ) );
					return;
				}

				$tags = get_the_tags( $post_id );

				if ( $tags && ! is_wp_error( $tags ) ) {
					foreach ( $tags as $tag ) {
						$keywords[] = $tag->name;
					}
				}

				self::print_keywords( $keywords );
				return;
			}

			if ( is_singular( 'product' ) ) {
				$post_id            = get_the_ID();
				$rank_math_keywords = get_post_meta( $post_id, 'rank_math_focus_keyword', true );

				if ( ! empty( $rank_math_keywords ) ) {
					self::print_keywords( array_map( 'trim', explode( ',', $rank_math_keywords ) ) );
					return;
				}

				$product_tags = get_the_terms( $post_id, 'product_tag' );

				if ( $product_tags && ! is_wp_error( $product_tags ) ) {
					foreach ( $product_tags as $tag ) {
						$keywords[] = $tag->name;
					}
				}

				$product_categories = get_the_terms( $post_id, 'product_cat' );

				if ( $product_categories && ! is_wp_error( $product_categories ) ) {
					foreach ( $product_categories as $category ) {
						$keywords[] = $category->name;
					}
				}

				self::print_keywords( $keywords );
				return;
			}

			if ( is_tax() || is_category() ) {
				$term = get_queried_object();

				if ( $term && ! is_wp_error( $term ) ) {
					$rank_math_keywords = get_term_meta( $term->term_id, 'rank_math_focus_keyword', true );

					if ( ! empty( $rank_math_keywords ) ) {
						$keywords = array_map( 'trim', explode( ',', $rank_math_keywords ) );
					} else {
						$keywords[] = $term->name;
					}

					self::print_keywords( $keywords );
				}

				return;
			}

			add_filter( 'rank_math/frontend/show_keywords', '__return_true' );
		}

		private static function print_keywords( $keywords ) {
			$keywords = array_unique( array_filter( array_map( 'trim', (array) $keywords ) ) );

			if ( empty( $keywords ) ) {
				return;
			}

			echo '<meta name="keywords" content="' . esc_attr( implode( ', ', $keywords ) ) . '" />' . "\n";
		}
	}
}
