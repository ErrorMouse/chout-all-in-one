<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Disable_Search' ) ) {
	final class Chout_AIO_Disable_Search {
		public static function init() {
			add_action( 'parse_query', array( __CLASS__, 'disable_search' ), 15, 2 );
			add_action( 'widgets_init', array( __CLASS__, 'remove_search_widget' ) );
			add_filter( 'get_search_form', '__return_empty_string', 999 );
			add_action( 'init', array( __CLASS__, 'remove_search_block' ) );
			add_action( 'admin_bar_menu', array( __CLASS__, 'remove_admin_bar_search' ), 11 );
		}

		public static function disable_search( $query, $redirect_to_home = true ) {
			if ( ! $query instanceof WP_Query || ! $query->is_search() || is_admin() ) {
				return;
			}

			$query->is_search       = false;
			$query->query_vars['s'] = false;
			$query->query['s']      = false;

			if ( true === $redirect_to_home ) {
				wp_safe_redirect( home_url() );
				exit;
			}
		}

		public static function remove_search_widget() {
			unregister_widget( 'WP_Widget_Search' );
		}

		public static function remove_search_block() {
			if ( ! function_exists( 'unregister_block_type' ) || ! class_exists( 'WP_Block_Type_Registry' ) ) {
				return;
			}

			$block = 'core/search';

			if ( WP_Block_Type_Registry::get_instance()->is_registered( $block ) ) {
				unregister_block_type( $block );
			}
		}

		public static function remove_admin_bar_search( $wp_admin_bar ) {
			$wp_admin_bar->remove_menu( 'search' );
		}
	}
}
