<?php
/**
 * Disable jQuery Migrate.
 *
 * @package Chout_All_In_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Disable_jQuery_Migrate' ) ) {
	class Chout_AIO_Disable_jQuery_Migrate {
		public static function init() {
			add_action( 'wp_default_scripts', array( __CLASS__, 'dequeue_jquery_migrate' ), 150 );
		}

		public static function dequeue_jquery_migrate( $scripts ) {
			if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
				$script = $scripts->registered['jquery'];
				if ( ! empty( $script->deps ) ) {
					$script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
				}
			}
		}
	}
}
