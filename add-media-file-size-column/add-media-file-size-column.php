<?php
/**
 * Add Media File Size Column.
 *
 * @package Chout_All_In_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Add_Media_File_Size_Column' ) ) {
	class Chout_AIO_Add_Media_File_Size_Column {
		public static function init() {
			add_filter( 'manage_upload_columns', array( __CLASS__, 'add_column' ) );
			add_action( 'manage_media_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
		}

		public static function add_column( $columns ) {
			$columns['file_size'] = esc_html__( 'File Size', 'chout-all-in-one' );
			return $columns;
		}

		public static function render_column( $column_name, $media_item ) {
			if ( 'file_size' !== $column_name || ! wp_attachment_is_image( $media_item ) ) {
				return;
			}
			$file = get_attached_file( $media_item );
			if ( $file && file_exists( $file ) ) {
				$filesize = size_format( filesize( $file ), 2 );
				echo esc_html( $filesize );
			}
		}
	}
}
