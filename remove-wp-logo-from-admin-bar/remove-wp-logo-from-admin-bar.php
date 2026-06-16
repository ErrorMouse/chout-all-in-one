<?php
/**
 * Remove WP Logo From Admin Bar.
 *
 * @package Chout_All_In_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Remove_WP_Logo_From_Admin_Bar' ) ) {
	class Chout_AIO_Remove_WP_Logo_From_Admin_Bar {
		public static function init() {
			add_action( 'wp_before_admin_bar_render', array( __CLASS__, 'remove_wp_logo' ), 0 );
		}

		public static function remove_wp_logo() {
			global $wp_admin_bar;
			$wp_admin_bar->remove_menu( 'wp-logo' );
		}
	}
}
