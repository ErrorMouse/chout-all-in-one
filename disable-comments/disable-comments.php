<?php
/**
 * Disable Comments.
 *
 * @package Chout_All_In_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Disable_Comments' ) ) {
	class Chout_AIO_Disable_Comments {
		public static function init() {
			add_action( 'admin_init', array( __CLASS__, 'disable_admin_comments' ) );
			add_filter( 'comments_open', '__return_false', 20, 2 );
			add_filter( 'pings_open', '__return_false', 20, 2 );
			add_filter( 'comments_array', '__return_empty_array', 10, 2 );
			add_action( 'admin_menu', array( __CLASS__, 'remove_admin_menu' ) );
			add_action( 'admin_bar_menu', array( __CLASS__, 'remove_admin_bar' ), 0 );
		}

		public static function disable_admin_comments() {
			global $pagenow;
			if ( 'edit-comments.php' === $pagenow ) {
				wp_safe_redirect( admin_url() );
				exit;
			}
			remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
			foreach ( get_post_types() as $post_type ) {
				if ( post_type_supports( $post_type, 'comments' ) ) {
					remove_post_type_support( $post_type, 'comments' );
					remove_post_type_support( $post_type, 'trackbacks' );
				}
			}
		}

		public static function remove_admin_menu() {
			remove_menu_page( 'edit-comments.php' );
		}

		public static function remove_admin_bar() {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
		}
	}
}
