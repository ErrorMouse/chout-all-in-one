<?php
/**
 * Smooth Scroll by Lenis.
 *
 * @package Chout_All_In_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Smooth_Scroll_Lenis' ) ) {
	class Chout_AIO_Smooth_Scroll_Lenis {
		const LENIS_VERSION = '1.3.26';

		public static function init() {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		}

		public static function enqueue_assets() {
			wp_enqueue_script(
				'lenis',
				// phpcs:ignore PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent
				'https://unpkg.com/lenis@' . self::LENIS_VERSION . '/dist/lenis.min.js',
				array(),
				self::LENIS_VERSION,
				true
			);

			wp_add_inline_script(
				'lenis',
				'const mainLenis = new Lenis({ autoRaf: true });'
			);
		}
	}
}
