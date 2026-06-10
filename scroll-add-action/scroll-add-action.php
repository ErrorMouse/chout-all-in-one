<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Scroll_Add_Action' ) ) {
	final class Chout_AIO_Scroll_Add_Action {
		const OPTION_CLASS_NAME = 'chout_aio_scroll_add_action_class';

		public static function init() {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		}

		public static function enqueue_assets() {
			$class_name = self::get_class_name();

			wp_enqueue_style( 'scroll-add-action', plugin_dir_url( __FILE__ ) . 'scroll-add-action.css', array(), '1.0', 'all' );

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
				$saved = true;
			}

			$class_name = self::get_class_name();
			?>
			<div class="wrap">
				<h1>Scroll Add Action</h1>

				<?php if ( $saved ) : ?>
					<div class="notice notice-success is-dismissible">
						<p><?php esc_html_e( 'Changes saved.', 'chout-all-in-one' ); ?></p>
					</div>
				<?php endif; ?>

				<form method="post" action="">
					<?php wp_nonce_field( 'chout_aio_scroll_add_action', 'chout_aio_scroll_add_action_nonce' ); ?>

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="scroll_add_action"><?php esc_html_e( 'CSS class', 'chout-all-in-one' ); ?></label>
								</th>
								<td>
									<input
										type="text"
										id="scroll_add_action"
										name="scroll_add_action"
										class="regular-text"
										value="<?php echo esc_attr( $class_name ); ?>"
									>
									<p class="description"><?php esc_html_e( 'Enter the class name to track, without the leading dot.', 'chout-all-in-one' ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>

					<?php submit_button( __( 'Save Changes', 'chout-all-in-one' ) ); ?>
				</form>
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
	}
}
