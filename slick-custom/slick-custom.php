<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Slick_Custom' ) ) {
	final class Chout_AIO_Slick_Custom {
		public static function init() {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_style' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_script' ), 20 );
		}

		public static function enqueue_style() {
			wp_enqueue_style( 'slick', plugin_dir_url( __FILE__ ) . 'slick.css', array(), '1.0', 'all' );
		}

		public static function enqueue_script() {
			wp_enqueue_script( 'slick', plugin_dir_url( __FILE__ ) . 'slick.js', array( 'jquery' ), '1.0', true );
		}
	}
}
