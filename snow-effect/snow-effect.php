<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Snow_Effect' ) ) {
	final class Chout_AIO_Snow_Effect {
		public static function init() {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_script' ) );
		}

		public static function enqueue_script() {
			wp_enqueue_script( 'snow-effect', plugin_dir_url( __FILE__ ) . 'snow-effect.js', array( 'jquery' ), '1.0', true );
		}
	}
}
