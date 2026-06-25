<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Add_Signature_To_RSS' ) ) {
	final class Chout_AIO_Add_Signature_To_RSS {
		public static function init() {
			add_action( 'pre_get_posts', array( __CLASS__, 'feed_filter' ) );
		}

		public static function feed_filter( $query ) {
			if ( ! $query instanceof WP_Query || ! $query->is_feed() ) {
				return $query;
			}

			if ( ! has_filter( 'the_content', array( __CLASS__, 'content_filter' ) ) ) {
				add_filter( 'the_content', array( __CLASS__, 'content_filter' ) );
			}

			return $query;
		}

		public static function content_filter( $content ) {
			$signature = sprintf(
				/* translators: 1: Home URL, 2: Blog name. */
				__( 'This post was published at <a href="%1$s" rel="dofollow">%1$s</a> and belongs to <a href="%1$s" rel="dofollow">%2$s</a>.', 'chout-all-in-one' ),
				esc_url( home_url() ),
				esc_html( get_bloginfo( 'name' ) )
			);

			return $content . '<p>' . $signature . '</p>';
		}
	}
}
