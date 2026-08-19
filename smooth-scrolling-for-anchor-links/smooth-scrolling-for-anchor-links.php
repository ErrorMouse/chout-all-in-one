<?php
/**
 * Smooth Scrolling for Anchor Links.
 *
 * @package Chout_All_In_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Smooth_Scrolling' ) ) {
	class Chout_AIO_Smooth_Scrolling {
		public static function init() {
			add_action( 'wp_footer', array( __CLASS__, 'render_js' ) );
		}

		public static function render_js() {
			?>
			<script>
			document.addEventListener('click', function(e) {
				const anchor = e.target.closest('a');

				if (!anchor) return;

				const href = anchor.getAttribute('href');

				if (href && href.startsWith('#')) {
					e.preventDefault();

					const targetId = href.slice(1);

					if (!targetId) return;

					const targetElement = document.getElementById(targetId);

					if (targetElement) {
						targetElement.scrollIntoView({
							behavior: 'smooth'
						});
					}
				}
			});
			</script>
			<?php
		}
	}
}
