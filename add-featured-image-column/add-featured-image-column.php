<?php
/**
 * Add Featured Image Column.
 *
 * @package Chout_All_In_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Add_Featured_Image_Column' ) ) {
	class Chout_AIO_Add_Featured_Image_Column {
		public static function init() {
			add_filter( 'manage_posts_columns', array( __CLASS__, 'add_column' ) );
			add_action( 'manage_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
			add_action( 'admin_head', array( __CLASS__, 'add_admin_styles' ) );
		}

		public static function add_column( $columns ) {
			$move_after     = 'title';
			$move_after_key = array_search( $move_after, array_keys( $columns ), true );

			if ( false === $move_after_key ) {
				$columns['featured_image'] = __( 'Featured Image', 'chout-all-in-one' );
				return $columns;
			}

			$first_columns = array_slice( $columns, 0, $move_after_key + 1 );
			$last_columns  = array_slice( $columns, $move_after_key + 1 );

			return array_merge(
				$first_columns,
				array( 'featured_image' => __( 'Featured Image', 'chout-all-in-one' ) ),
				$last_columns
			);
		}

		public static function render_column( $column, $post_id ) {
			if ( 'featured_image' === $column ) {
				echo get_the_post_thumbnail( $post_id, array( 80, 80 ) );
			}
		}

		public static function add_admin_styles() {
			echo '<style>.column-featured_image { width: 100px; }</style>';
		}
	}
}
