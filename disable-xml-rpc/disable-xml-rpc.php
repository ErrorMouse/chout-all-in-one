<?php
/**
 * Disable XML-RPC.
 *
 * @package Chout_All_In_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Disable_XML_RPC' ) ) {
	class Chout_AIO_Disable_XML_RPC {
		public static function init() {
			add_filter( 'xmlrpc_enabled', '__return_false' );
		}
	}
}
