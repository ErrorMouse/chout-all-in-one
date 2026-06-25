<?php
/**
 * Allow SVG Files Upload.
 *
 * @package Chout_All_In_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Allow_SVG_Upload' ) ) {
	class Chout_AIO_Allow_SVG_Upload {
		public static function init() {
			add_filter( 'upload_mimes', array( __CLASS__, 'allow_svg_mimes' ) );
			add_filter( 'wp_check_filetype_and_ext', array( __CLASS__, 'check_svg_filetype' ), 10, 5 );
		}

		public static function allow_svg_mimes( $upload_mimes ) {
			if ( ! current_user_can( 'administrator' ) ) {
				return $upload_mimes;
			}
			$upload_mimes['svg']  = 'image/svg+xml';
			$upload_mimes['svgz'] = 'image/svg+xml';
			return $upload_mimes;
		}

		public static function check_svg_filetype( $wp_check_filetype_and_ext, $file, $filename, $mimes, $real_mime ) {
			if ( ! $wp_check_filetype_and_ext['type'] ) {
				$check_filetype  = wp_check_filetype( $filename, $mimes );
				$ext             = $check_filetype['ext'];
				$type            = $check_filetype['type'];
				$proper_filename = $filename;

				if ( $type && 0 === strpos( $type, 'image/' ) && 'svg' !== $ext ) {
					$ext  = false;
					$type = false;
				}

				$wp_check_filetype_and_ext = compact( 'ext', 'type', 'proper_filename' );
			}
			return $wp_check_filetype_and_ext;
		}
	}
}
