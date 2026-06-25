<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Logout_Redirect' ) ) {
	final class Chout_AIO_Logout_Redirect {
		public static function init() {
			add_action( 'wp_logout', array( __CLASS__, 'redirect_home' ) );
		}

		public static function redirect_home() {
			wp_safe_redirect( home_url() );
			exit;
		}
	}
}
