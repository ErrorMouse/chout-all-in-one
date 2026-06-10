<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Display_Dashicons' ) ) {
	final class Chout_AIO_Display_Dashicons {
		public static function init() {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_dashicons' ) );
		}

		public static function enqueue_dashicons() {
			wp_enqueue_style( 'dashicons' );
		}
	}
}
