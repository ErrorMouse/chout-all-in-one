<?php
/**
 * Disable Emojis.
 *
 * @package Chout_All_In_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Disable_Emojis' ) ) {
	class Chout_AIO_Disable_Emojis {
		public static function init() {
			add_action( 'init', array( __CLASS__, 'disable_emojis' ) );
		}

		public static function disable_emojis() {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

			add_filter( 'tiny_mce_plugins', array( __CLASS__, 'disable_emojis_tinymce' ) );
			add_filter( 'wp_resource_hints', array( __CLASS__, 'disable_emojis_remove_dns_prefetch' ), 10, 2 );
		}

		public static function disable_emojis_tinymce( $plugins ) {
			if ( is_array( $plugins ) ) {
				return array_diff( $plugins, array( 'wpemoji' ) );
			}
			return array();
		}

		public static function disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {
			if ( 'dns-prefetch' === $relation_type ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is a core WordPress filter.
				$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
				$urls          = array_diff( $urls, array( $emoji_svg_url ) );
			}
			return $urls;
		}
	}
}
