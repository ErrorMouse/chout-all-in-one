<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Scroll_Add_Action' ) ) {
	final class Chout_AIO_Scroll_Add_Action {
		const OPTION_CLASS_NAME = 'chout_aio_scroll_add_action_class';
		const OPTION_CUSTOM_CSS = 'chout_aio_scroll_add_action_custom_css';

		public static function init() {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		}

		public static function enqueue_assets() {
			$class_name = self::get_class_name();
			$custom_css = self::get_custom_css();

			wp_enqueue_style( 'scroll-add-action', plugin_dir_url( __FILE__ ) . 'scroll-add-action.css', array(), '1.0', 'all' );

			if ( ! empty( $custom_css ) ) {
				wp_add_inline_style( 'scroll-add-action', wp_strip_all_tags( $custom_css ) );
			}

			if ( '' === $class_name ) {
				return;
			}

			wp_enqueue_script( 'scroll-add-action-script', plugin_dir_url( __FILE__ ) . 'scroll-add-action.js', array( 'jquery' ), '1.0', true );
			wp_localize_script(
				'scroll-add-action-script',
				'scrollAddAction',
				array(
					'scrollAddActionValue' => $class_name,
				)
			);
		}

		public static function settings_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'chout-all-in-one' ) );
			}

			$saved = false;

			$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';

			if ( 'POST' === $request_method && isset( $_POST['chout_aio_scroll_add_action_nonce'] ) ) {
				check_admin_referer( 'chout_aio_scroll_add_action', 'chout_aio_scroll_add_action_nonce' );

				$class_name = isset( $_POST['scroll_add_action'] ) ? sanitize_html_class( wp_unslash( $_POST['scroll_add_action'] ) ) : '';
				self::save_class_name( $class_name );

				$custom_css = isset( $_POST['scroll_add_action_css'] ) ? wp_strip_all_tags( wp_unslash( $_POST['scroll_add_action_css'] ) ) : '';
				self::save_custom_css( $custom_css );

				$saved = true;
			}

			$class_name = self::get_class_name();
			$custom_css = self::get_custom_css();
			$display_class = $class_name ? $class_name : 'action';
			?>

			<div class="chout-background-effect"></div>

			<div id="chout-aio-scroll-add-action" class="chout-all-in-one">
				<div class="caio-wrap">
					<h1>Scroll Add Action</h1>

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
						<?php wp_nonce_field( 'chout_aio_scroll_add_action', 'chout_aio_scroll_add_action_nonce' ); ?>

						<div class="caio-card">
							<div style="display: flex; flex-direction: column; align-items: center; gap: 15px; margin-bottom: 40px;">
								<div>
									<label for="scroll_add_action"><strong><?php esc_html_e( 'CSS class', 'chout-all-in-one' ); ?></strong></label>
								</div>
								<div style="max-width: 100%;">
									<input
										type="text"
										id="scroll_add_action"
										name="scroll_add_action"
										class="regular-text"
										value="<?php echo esc_attr( $class_name ); ?>"
									>
									<p class="description" style="margin-top: 5px; font-style: italic; text-align: center;"><?php esc_html_e( 'Enter the class name to track, without the leading dot.', 'chout-all-in-one' ); ?></p>
								</div>
							</div>

							<div style="display: flex; flex-direction: column; align-items: center; gap: 15px; margin-bottom: 20px;">
								<div style="width: 100%; text-align: center;">
									<label for="scroll_add_action_css"><strong><?php esc_html_e( 'Custom CSS', 'chout-all-in-one' ); ?></strong></label>
									<p class="description" style="margin-top: 5px; font-style: italic;">
										<?php
										/* translators: %s: CSS class name */
										printf( esc_html__( 'When the user scrolls to the target, the "%s" class will be automatically added to the element. You can write the CSS formatting for that class here:', 'chout-all-in-one' ), esc_html( $display_class ) );
										?>
									</p>
								</div>
								<div style="width: 100%; max-width: 600px;">
									<textarea
										id="scroll_add_action_css"
										name="scroll_add_action_css"
										class="large-text code"
										rows="10"
										placeholder=".<?php echo esc_attr( $display_class ); ?> {&#10;    color: #000;&#10;    /* Add your custom styles here */&#10;}"
									><?php echo esc_textarea( $custom_css ); ?></textarea>
								</div>
							</div>
						</div>

						<p style="text-align: center;"><?php submit_button( __( 'Save Changes', 'chout-all-in-one' ), 'primary', 'submit', false ); ?></p>
					</form>
				</div>
			</div>
			<?php
		}

		private static function get_class_name() {
			$class_name = get_option( self::OPTION_CLASS_NAME, '' );

			return is_string( $class_name ) ? $class_name : '';
		}

		private static function save_class_name( $class_name ) {
			update_option( self::OPTION_CLASS_NAME, $class_name, false );
		}

		private static function get_custom_css() {
			$custom_css = get_option( self::OPTION_CUSTOM_CSS, '' );

			return is_string( $custom_css ) ? $custom_css : '';
		}

		private static function save_custom_css( $custom_css ) {
			update_option( self::OPTION_CUSTOM_CSS, $custom_css, false );
		}
	}
}
