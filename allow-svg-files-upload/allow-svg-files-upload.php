<?php
/**
 * Allow sanitized SVG uploads for site administrators.
 *
 * @package Chout_All_In_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Allow_SVG_Upload' ) ) {
	final class Chout_AIO_Allow_SVG_Upload {
		const MAX_FILE_SIZE = 1048576; // 1 MiB.

		/**
		 * SVG elements that are safe after their attributes are filtered.
		 *
		 * Keep this allowlist intentionally small. SVG supports active content
		 * through elements such as script, foreignObject, image, animation and CSS.
		 */
		private static $allowed_elements = array(
			'svg',
			'g',
			'path',
			'rect',
			'circle',
			'ellipse',
			'line',
			'polyline',
			'polygon',
			'defs',
			'lineargradient',
			'radialgradient',
			'stop',
			'clippath',
			'mask',
			'pattern',
			'symbol',
			'use',
			'title',
			'desc',
		);

		private static $allowed_attributes = array(
			'id',
			'class',
			'width',
			'height',
			'viewbox',
			'x',
			'y',
			'x1',
			'x2',
			'y1',
			'y2',
			'cx',
			'cy',
			'r',
			'rx',
			'ry',
			'd',
			'points',
			'fill',
			'fill-opacity',
			'fill-rule',
			'stroke',
			'stroke-width',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-opacity',
			'opacity',
			'transform',
			'offset',
			'stop-color',
			'stop-opacity',
			'gradientunits',
			'gradienttransform',
			'spreadmethod',
			'patternunits',
			'patterncontentunits',
			'patterntransform',
			'preserveaspectratio',
			'clip-path',
			'mask',
			'role',
			'aria-label',
			'aria-hidden',
			'focusable',
			'href',
		);

		public static function init() {
			add_filter( 'upload_mimes', array( __CLASS__, 'allow_svg_mimes' ) );
			add_filter( 'wp_handle_upload_prefilter', array( __CLASS__, 'sanitize_svg_upload' ) );
			add_filter( 'wp_check_filetype_and_ext', array( __CLASS__, 'check_svg_filetype' ), 10, 5 );
		}

		/**
		 * Only users who can manage this site's settings may upload SVG files.
		 *
		 * @param array $upload_mimes Allowed MIME types.
		 * @return array
		 */
		public static function allow_svg_mimes( $upload_mimes ) {
			if ( ! self::can_upload_svg() ) {
				return $upload_mimes;
			}

			$upload_mimes['svg'] = 'image/svg+xml';
			return $upload_mimes;
		}

		/**
		 * Sanitize SVG markup before WordPress moves the temporary upload.
		 *
		 * SVGZ is deliberately unsupported. Safely handling compressed XML would
		 * require separate decompression limits and adds no value for this feature.
		 *
		 * @param array $file Uploaded file information.
		 * @return array
		 */
		public static function sanitize_svg_upload( $file ) {
			if ( ! self::is_svg_filename( isset( $file['name'] ) ? $file['name'] : '' ) ) {
				return $file;
			}

			if ( ! self::can_upload_svg() ) {
				$file['error'] = __( 'You are not allowed to upload SVG files.', 'chout-all-in-one' );
				return $file;
			}

			$file_path = isset( $file['tmp_name'] ) ? $file['tmp_name'] : '';
			$sanitized = self::sanitize_svg_file( $file_path );

			if ( is_wp_error( $sanitized ) ) {
				$file['error'] = $sanitized->get_error_message();
				return $file;
			}

			if ( false === file_put_contents( $file_path, $sanitized, LOCK_EX ) ) {
				$file['error'] = __( 'The SVG file could not be sanitized.', 'chout-all-in-one' );
			}

			return $file;
		}

		/**
		 * Let WordPress accept only a valid, sanitized SVG file.
		 *
		 * This deliberately does not override a failed MIME check for arbitrary
		 * image files, which was the unsafe behaviour of the previous filter.
		 *
		 * @param array  $wp_check_filetype_and_ext File type data.
		 * @param string $file                      Full path to the file.
		 * @param string $filename                  File name.
		 * @param array  $mimes                     Allowed MIME types.
		 * @param string $real_mime                 Detected MIME type.
		 * @return array
		 */
		public static function check_svg_filetype( $wp_check_filetype_and_ext, $file, $filename, $mimes, $real_mime ) {
			if ( ! self::is_svg_filename( $filename ) ) {
				return $wp_check_filetype_and_ext;
			}

			if ( ! self::can_upload_svg() || is_wp_error( self::sanitize_svg_file( $file ) ) ) {
				return array(
					'ext'             => false,
					'type'            => false,
					'proper_filename' => false,
				);
			}

			return array(
				'ext'             => 'svg',
				'type'            => 'image/svg+xml',
				'proper_filename' => $filename,
			);
		}

		/**
		 * Parse and sanitize an SVG file with an allowlist of elements/attributes.
		 *
		 * @param string $file_path Path to the temporary SVG upload.
		 * @return string|WP_Error Sanitized SVG markup or a validation error.
		 */
		private static function sanitize_svg_file( $file_path ) {
			if ( ! class_exists( 'DOMDocument' ) ) {
				return new WP_Error( 'chout_aio_svg_dom_missing', __( 'SVG uploads require the PHP DOM extension.', 'chout-all-in-one' ) );
			}

			if ( empty( $file_path ) || ! is_readable( $file_path ) ) {
				return new WP_Error( 'chout_aio_svg_unreadable', __( 'The SVG file could not be read.', 'chout-all-in-one' ) );
			}

			$file_size = filesize( $file_path );
			if ( false === $file_size || $file_size > self::MAX_FILE_SIZE ) {
				return new WP_Error( 'chout_aio_svg_too_large', __( 'SVG files must be 1 MB or smaller.', 'chout-all-in-one' ) );
			}

			$contents = file_get_contents( $file_path );
			if ( false === $contents || '' === trim( $contents ) ) {
				return new WP_Error( 'chout_aio_svg_empty', __( 'The SVG file is empty or invalid.', 'chout-all-in-one' ) );
			}

			$contents = preg_replace( '/^\xEF\xBB\xBF/', '', $contents );
			if ( preg_match( '/<!DOCTYPE|<!ENTITY/i', $contents ) ) {
				return new WP_Error( 'chout_aio_svg_doctype', __( 'SVG files must not contain document types or entities.', 'chout-all-in-one' ) );
			}

			$previous_errors = libxml_use_internal_errors( true );
			$document        = new DOMDocument();
			$is_valid        = $document->loadXML( $contents, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
			libxml_clear_errors();
			libxml_use_internal_errors( $previous_errors );

			if ( ! $is_valid || ! $document->documentElement || 'svg' !== strtolower( $document->documentElement->localName ) ) {
				return new WP_Error( 'chout_aio_svg_invalid', __( 'The uploaded file is not valid SVG markup.', 'chout-all-in-one' ) );
			}

			$root = $document->documentElement;
			$root->setAttribute( 'xmlns', 'http://www.w3.org/2000/svg' );
			self::sanitize_svg_node( $root, $root );

			$sanitized = $document->saveXML( $root );
			if ( false === $sanitized || '' === $sanitized ) {
				return new WP_Error( 'chout_aio_svg_serialize', __( 'The SVG file could not be sanitized.', 'chout-all-in-one' ) );
			}

			return $sanitized;
		}

		/**
		 * Recursively remove active SVG content and unsafe attributes.
		 *
		 * @param DOMElement $element Current SVG element.
		 * @param DOMElement $root    SVG root element.
		 * @return void
		 */
		private static function sanitize_svg_node( DOMElement $element, DOMElement $root ) {
			$element_name = strtolower( $element->localName ? $element->localName : $element->nodeName );

			if ( ! in_array( $element_name, self::$allowed_elements, true ) ) {
				$element->parentNode->removeChild( $element );
				return;
			}

			$attributes = array();
			foreach ( $element->attributes as $attribute ) {
				$attributes[] = $attribute;
			}

			foreach ( $attributes as $attribute ) {
				$attribute_name  = strtolower( $attribute->nodeName );
				$attribute_value = trim( $attribute->nodeValue );

				if ( self::is_allowed_attribute( $attribute_name, $attribute_value, $element === $root ) ) {
					continue;
				}

				$element->removeAttributeNode( $attribute );
			}

			for ( $child = $element->firstChild; $child; $child = $next_child ) {
				$next_child = $child->nextSibling;

				if ( $child instanceof DOMElement ) {
					self::sanitize_svg_node( $child, $root );
				} elseif ( XML_TEXT_NODE !== $child->nodeType && XML_CDATA_SECTION_NODE !== $child->nodeType ) {
					$element->removeChild( $child );
				}
			}
		}

		/**
		 * Check whether an SVG attribute is safe to retain.
		 *
		 * @param string $name    Attribute name.
		 * @param string $value   Attribute value.
		 * @param bool   $is_root Whether the current element is the SVG root.
		 * @return bool
		 */
		private static function is_allowed_attribute( $name, $value, $is_root ) {
			if ( 0 === strpos( $name, 'on' ) || 'style' === $name ) {
				return false;
			}

			if ( $is_root && 'xmlns' === $name ) {
				return 'http://www.w3.org/2000/svg' === $value;
			}

			if ( ! in_array( $name, self::$allowed_attributes, true ) ) {
				return false;
			}

			if ( 'href' === $name ) {
				return (bool) preg_match( '/^#[A-Za-z][A-Za-z0-9_.:-]*$/', $value );
			}

			if ( in_array( $name, array( 'clip-path', 'mask' ), true ) ) {
				return self::is_local_reference( $value );
			}

			if ( in_array( $name, array( 'fill', 'stroke' ), true ) && false !== stripos( $value, 'url(' ) ) {
				return self::is_local_reference( $value );
			}

			return false === stripos( $value, 'javascript:' ) && false === stripos( $value, 'data:' ) && false === stripos( $value, 'expression(' );
		}

		/**
		 * Only permit fragment references such as url(#gradient).
		 *
		 * @param string $value Attribute value.
		 * @return bool
		 */
		private static function is_local_reference( $value ) {
			return (bool) preg_match( '/^url\(\s*#[A-Za-z][A-Za-z0-9_.:-]*\s*\)$/', $value );
		}

		/**
		 * Check the extension without accepting compressed SVG files.
		 *
		 * @param string $filename File name.
		 * @return bool
		 */
		private static function is_svg_filename( $filename ) {
			return 'svg' === strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		}

		/**
		 * Check the capability rather than a role name.
		 *
		 * @return bool
		 */
		private static function can_upload_svg() {
			return current_user_can( 'manage_options' );
		}
	}
}
