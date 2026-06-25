<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Block_WPAdmin_Area' ) ) {
	final class Chout_AIO_Block_WPAdmin_Area {
		public static function init() {
			add_action( 'admin_init', array( __CLASS__, 'block_non_admin_users' ) );
		}

		public static function block_non_admin_users() {
			if ( wp_doing_ajax() ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_safe_redirect( home_url() );
				exit;
			}
		}
	}
}
