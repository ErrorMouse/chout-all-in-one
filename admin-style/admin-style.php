<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Admin_Style' ) ) {
	final class Chout_AIO_Admin_Style {
		public static function init() {
			add_action( 'enqueue_block_assets', array( __CLASS__, 'enqueue_style' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_style' ) );
		}

		public static function enqueue_style() {
			wp_enqueue_style( 'admin-style', plugin_dir_url( __FILE__ ) . 'admin-style.css', array(), '1.0', 'all' );
		}
	}
}
