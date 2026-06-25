<?php
/**
 * Scroll Progress Bar.
 *
 * @package Chout_All_In_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Scroll_Progress_Bar' ) ) {
	class Chout_AIO_Scroll_Progress_Bar {
		public static function init() {
			add_action( 'wp_body_open', array( __CLASS__, 'render_bar_div' ) );
			add_action( 'wp_head', array( __CLASS__, 'render_css' ) );
			add_action( 'wp_footer', array( __CLASS__, 'render_js' ) );
		}

		public static function get_settings() {
			$defaults = array(
				'color'    => '#007bff',
				'height'   => '4',
				'position' => 'top',
			);
			$settings = get_option( 'chout_aio_scroll_progress_bar', $defaults );
			return wp_parse_args( $settings, $defaults );
		}

		public static function render_bar_div() {
			echo '<div id="chout-aio-progress-bar"></div>';
		}

		public static function render_css() {
			$settings = self::get_settings();
			$color    = sanitize_hex_color( $settings['color'] );
			$height   = absint( $settings['height'] );
			$position = 'bottom' === $settings['position'] ? 'bottom: 0;' : 'top: 0;';
			
			$css = '
			<style>
				#chout-aio-progress-bar {
					position: fixed;
					' . $position . '
					left: 0;
					width: 0%;
					height: ' . $height . 'px;
					background-color: ' . $color . ';
					z-index: 99999;
				}
			';
			
			if ( 'top' === $settings['position'] ) {
				$css .= '
				@media (min-width: 783px) {
					.admin-bar #chout-aio-progress-bar {
						top: 32px;
					}
				}
				@media (max-width: 782px) {
					.admin-bar #chout-aio-progress-bar {
						top: 46px;
					}
				}';
			}
			$css .= '</style>';
			
			echo $css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		public static function render_js() {
			?>
			<script>
				document.addEventListener("DOMContentLoaded", function() {
					var progressBar = document.getElementById("chout-aio-progress-bar");
					if (!progressBar) return;
					
					window.addEventListener("scroll", function() {
						var scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
						var scrollHeight = document.documentElement.scrollHeight || document.body.scrollHeight;
						var clientHeight = document.documentElement.clientHeight || document.body.clientHeight;
						var scrolled = (scrollTop / (scrollHeight - clientHeight)) * 100;
						progressBar.style.width = scrolled + "%";
					}, { passive: true });
				});
			</script>
			<?php
		}

		public static function settings_page() {
			$saved = false;

			if ( isset( $_POST['chout_aio_spb_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['chout_aio_spb_nonce'] ) ), 'chout_aio_spb' ) ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( esc_html__( 'Unauthorized user', 'chout-all-in-one' ) );
				}
				
				$settings = array(
					'color'    => isset( $_POST['spb_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['spb_color'] ) ) : '#007bff',
					'height'   => isset( $_POST['spb_height'] ) ? absint( wp_unslash( $_POST['spb_height'] ) ) : 4,
					'position' => isset( $_POST['spb_position'] ) && 'bottom' === sanitize_text_field( wp_unslash( $_POST['spb_position'] ) ) ? 'bottom' : 'top',
				);
				
				update_option( 'chout_aio_scroll_progress_bar', $settings, false );
				$saved = true;
			}

			$settings = self::get_settings();
			?>

			<div class="chout-background-effect"></div>

			<div id="chout-aio-scroll-progress-bar" class="chout-all-in-one">
				<div class="caio-wrap">
					<h1>Chout - Scroll Progress Bar</h1>

					<div id="chout-donate">
						<span class="author">
							By 
							<a href="https://profiles.wordpress.org/nmtnguyen56/" target="_blank" rel="noopener noreferrer">
								Chout
							</a>
						</span>
						<span class="donate">
							<?php chout_caio_donate_link_html(); ?>
						</span>
					</div>

					<?php if ( $saved ) : ?>
						<div id="caio-toast-notification" class="caio-toast show">
							<?php esc_html_e( 'Changes saved.', 'chout-all-in-one' ); ?>
						</div>
						<script>
							setTimeout(function(){
								var toast = document.getElementById("caio-toast-notification");
								if(toast) { toast.className = toast.className.replace("show", ""); }
							}, 3000);
						</script>
					<?php endif; ?>

					<hr class="hr-h2">
					
					<form method="post" action="">
						<?php wp_nonce_field( 'chout_aio_spb', 'chout_aio_spb_nonce' ); ?>

						<div class="caio-card">
							<div style="display: flex; flex-wrap: wrap; align-items: center; gap: 15px; max-width: 100%; margin-bottom: 20px;">
								<div style="flex: 1 1 120px; max-width: 100%;">
									<label for="spb_color"><strong><?php esc_html_e( 'Bar Color', 'chout-all-in-one' ); ?></strong></label>
								</div>
								<div>
									<input type="color" name="spb_color" id="spb_color" value="<?php echo esc_attr( $settings['color'] ); ?>">
								</div>
							</div>

							<div style="display: flex; flex-wrap: wrap; align-items: center; gap: 15px; max-width: 100%; margin-bottom: 20px;">
								<div style="flex: 1 1 120px; max-width: 100%;">
									<label for="spb_height"><strong><?php esc_html_e( 'Bar Height (px)', 'chout-all-in-one' ); ?></strong></label>
								</div>
								<div>
									<input type="number" name="spb_height" id="spb_height" class="small-text" min="1" max="20" step="1" value="<?php echo esc_attr( $settings['height'] ); ?>">
								</div>
							</div>

							<div style="display: flex; flex-wrap: wrap; align-items: center; gap: 15px; max-width: 100%; margin-bottom: 20px;">
								<div style="flex: 1 1 120px; max-width: 100%;">
									<label for="spb_position"><strong><?php esc_html_e( 'Position', 'chout-all-in-one' ); ?></strong></label>
								</div>
								<div>
									<select name="spb_position" id="spb_position">
										<option value="top" <?php selected( $settings['position'], 'top' ); ?>><?php esc_html_e( 'Top', 'chout-all-in-one' ); ?></option>
										<option value="bottom" <?php selected( $settings['position'], 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'chout-all-in-one' ); ?></option>
									</select>
								</div>
							</div>
						</div>

						<p style="margin-top: 40px; text-align:center;"><?php submit_button( __( 'Save Changes', 'chout-all-in-one' ), 'primary', 'submit', false ); ?></p>
					</form>
				</div>
			</div>
			<?php
		}
	}
}
