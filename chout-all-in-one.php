<?php
/**
 * Plugin Name:       Chout - All in One
 * Description:       A single control panel for enabling small website features only when you need them.
 * Version:           1.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Chout
 * Author URI:        https://profiles.wordpress.org/nmtnguyen56/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       chout-all-in-one
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin Update Checker
require __DIR__ . '/vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/ErrorMouse/chout-all-in-one/', // Option 1
	// 'https://raw.githubusercontent.com/ErrorMouse/chout-all-in-one/refs/heads/main/chout-all-in-one.json', // Option 2
	__FILE__,
	'chout-all-in-one'
);
$myUpdateChecker->setBranch('main'); // Option 1
// End

if ( ! class_exists( 'Chout_AIO' ) ) {
	final class Chout_AIO {
		const VERSION         = '1.1.0';
		const OPTION_FEATURES = 'chout_aio_features';
		const MENU_SLUG       = 'chout-all-in-one';

		public static function init() {
			add_action( 'init', array( __CLASS__, 'load_enabled_features' ), 0 );
			add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
			add_action( 'admin_post_chout_aio_save_features', array( __CLASS__, 'save_features' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'add_settings_link' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_styles' ) );
		}

		public static function enqueue_admin_styles( $hook_suffix ) {
			if ( strpos( $hook_suffix, self::MENU_SLUG ) === false && strpos( $hook_suffix, 'chout-aio' ) === false ) {
				return;
			}
			wp_enqueue_style(
				'chout-aio-admin-style',
				plugin_dir_url( __FILE__ ) . 'assets/css/admin-settings.css',
				array(),
				self::VERSION
			);
		}

		public static function activate() {
			if ( false === get_option( self::OPTION_FEATURES, false ) ) {
				$defaults = array();

				foreach ( self::feature_slugs() as $slug ) {
					$defaults[ $slug ] = 0;
				}

				update_option( self::OPTION_FEATURES, $defaults, false );
			}

		}

		public static function features() {
			return array(
				'admin-style'                			=> array(
					'name'        => __( 'Admin Style', 'chout-all-in-one' ),
					'description' => __( 'Refine the admin and editing interfaces to make content easier to read and manage.', 'chout-all-in-one' ),
					'file'        => 'admin-style/admin-style.php',
					'class'       => 'Chout_AIO_Admin_Style',
				),
				'add-signature-to-rss'           		=> array(
					'name'        => __( 'Add Signature to RSS', 'chout-all-in-one' ),
					'description' => __( 'Add a source identifier to content distributed via RSS.', 'chout-all-in-one' ),
					'file'        => 'add-signature-to-rss/add-signature-to-rss.php',
					'class'       => 'Chout_AIO_Add_Signature_To_RSS',
				),
				'block-ips'                           	=> array(
					'name'            => __( 'Block IPs', 'chout-all-in-one' ),
					'description'     => __( 'Block specific IP addresses from accessing the website, with support for AIO community blocklist.', 'chout-all-in-one' ),
					'file'            => 'block-ips/block-ips.php',
					'class'           => 'Chout_AIO_Block_IPs',
					'configurable'    => true,
					'menu_slug'       => 'chout-aio-block-ips',
					'config_callback' => array( 'Chout_AIO_Block_IPs', 'settings_page' ),
				),
				'block-wpadmin-area-from-non-admin'		=> array(
					'name'        => __( 'Block WP-Admin Area from Non-Administrators', 'chout-all-in-one' ),
					'description' => __( 'Keep the admin area limited to users with website management privileges.', 'chout-all-in-one' ),
					'file'        => 'block-wpadmin-area-from-non-admin/block-wpadmin-area-from-non-admin.php',
					'class'       => 'Chout_AIO_Block_WPAdmin_Area',
				),
				'disable-search-redirect-to-home'		=> array(
					'name'        => __( 'Disable Search & Redirect to Home', 'chout-all-in-one' ),
					'description' => __( 'Disable site search and redirect visitors to the homepage when they attempt a search.', 'chout-all-in-one' ),
					'file'        => 'disable-search-redirect-to-home/disable-search-redirect-to-home.php',
					'class'       => 'Chout_AIO_Disable_Search',
				),
				'display-dashicons'                		=> array(
					'name'        => __( 'Display Dashicons', 'chout-all-in-one' ),
					'description' => __( 'Display familiar WordPress icons on the public-facing site.', 'chout-all-in-one' ),
					'file'        => 'display-dashicons/display-dashicons.php',
					'class'       => 'Chout_AIO_Display_Dashicons',
				),
				'keywords-everywhere'           		=> array(
					'name'        => __( 'Keywords Everywhere', 'chout-all-in-one' ),
					'description' => __( 'Automatically add relevant keywords to help content be easier to recognize.', 'chout-all-in-one' ),
					'file'        => 'keywords-everywhere/keywords-everywhere.php',
					'class'       => 'Chout_AIO_Keywords_Everywhere',
				),
				'redirects-to-the-homepage-upon-logout'	=> array(
					'name'        => __( 'Redirect to Homepage Upon Logout', 'chout-all-in-one' ),
					'description' => __( 'Return users to the homepage after they log out.', 'chout-all-in-one' ),
					'file'        => 'redirects-to-the-homepage-upon-logout/redirects-to-the-homepage-upon-logout.php',
					'class'       => 'Chout_AIO_Logout_Redirect',
				),
				'scroll-add-action'            			=> array(
					'name'            => __( 'Scroll Add Action', 'chout-all-in-one' ),
					'description'     => __( 'Create a state-change effect for content as visitors scroll.', 'chout-all-in-one' ),
					'file'            => 'scroll-add-action/scroll-add-action.php',
					'class'           => 'Chout_AIO_Scroll_Add_Action',
					'configurable'    => true,
					'menu_slug'       => 'chout-aio-scroll-add-action',
					'config_callback' => array( 'Chout_AIO_Scroll_Add_Action', 'settings_page' ),
				),
				'snow-effect'                     		=> array(
					'name'        => __( 'Snow Effect', 'chout-all-in-one' ),
					'description' => __( 'Add a gentle snowfall effect to your website for seasonal decoration.', 'chout-all-in-one' ),
					'file'        => 'snow-effect/snow-effect.php',
					'class'       => 'Chout_AIO_Snow_Effect',
				),
				'slick-custom'                        	=> array(
					'name'        => __( 'Slick Custom', 'chout-all-in-one' ),
					'description' => __( 'Add support for displaying content in slideshow format on the website.', 'chout-all-in-one' ),
					'file'        => 'slick-custom/slick-custom.php',
					'class'       => 'Chout_AIO_Slick_Custom',
				)
			);
		}

		private static function feature_slugs() {
			return array(
				'keywords-everywhere',
				'scroll-add-action',
				'admin-style',
				'block-wpadmin-area-from-non-admin',
				'disable-search-redirect-to-home',
				'display-dashicons',
				'snow-effect',
				'add-signature-to-rss',
				'redirects-to-the-homepage-upon-logout',
				'slick-custom',
				'block-ips',
			);
		}

		public static function get_feature_status() {
			$saved    = get_option( self::OPTION_FEATURES );
			$features = self::features();
			$status   = array();

			foreach ( $features as $slug => $feature ) {
				$status[ $slug ] = is_array( $saved ) && array_key_exists( $slug, $saved ) ? (bool) $saved[ $slug ] : false;
			}

			return $status;
		}

		public static function is_feature_enabled( $slug ) {
			$status = self::get_feature_status();

			return ! empty( $status[ $slug ] );
		}

		public static function load_enabled_features() {
			$status = self::get_feature_status();

			foreach ( self::features() as $slug => $feature ) {
				if ( empty( $status[ $slug ] ) || empty( $feature['file'] ) ) {
					continue;
				}

				$file = plugin_dir_path( __FILE__ ) . $feature['file'];

				if ( ! is_readable( $file ) ) {
					continue;
				}

				require_once $file;

				if ( ! empty( $feature['class'] ) && class_exists( $feature['class'] ) && method_exists( $feature['class'], 'init' ) ) {
					call_user_func( array( $feature['class'], 'init' ) );
				}
			}
		}

		public static function register_admin_menu() {
			add_menu_page(
				'Chout - All in One',
				'Chout AIO',
				'manage_options',
				self::MENU_SLUG,
				array( __CLASS__, 'settings_page' ),
				'dashicons-index-card',
				58
			);

			add_submenu_page(
				self::MENU_SLUG,
				'Settings',
				'Settings',
				'manage_options',
				self::MENU_SLUG,
				array( __CLASS__, 'settings_page' )
			);

			$status = self::get_feature_status();

			foreach ( self::features() as $slug => $feature ) {
				if ( empty( $status[ $slug ] ) || empty( $feature['configurable'] ) || empty( $feature['menu_slug'] ) || empty( $feature['config_callback'] ) ) {
					continue;
				}

				if ( ! is_callable( $feature['config_callback'] ) ) {
					continue;
				}

				add_submenu_page(
					self::MENU_SLUG,
					$feature['name'],
					$feature['name'],
					'manage_options',
					$feature['menu_slug'],
					$feature['config_callback']
				);
			}
		}

		public static function settings_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'chout-all-in-one' ) );
			}

			$features = self::features();
			$status   = self::get_feature_status();
			$updated  = (bool) get_transient( self::settings_updated_transient_key() );

			if ( $updated ) {
				delete_transient( self::settings_updated_transient_key() );
			}
			?>
			<div id="chout-all-in-one" class="chout-all-in-one">
				<div class="caio-wrap">
					<h1>Chout - All in One<span class="author">by <a href="https://profiles.wordpress.org/nmtnguyen56/" target="_blank" rel="noopener noreferrer">Chout</a></span><span class="donate"><?php chout_caio_donate_link_html(); ?></span></h1>

					<?php if ( $updated ) : ?>
						<div class="notice notice-success is-dismissible">
							<p><?php esc_html_e( 'Changes saved.', 'chout-all-in-one' ); ?></p>
						</div>
					<?php endif; ?>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="chout_aio_save_features">
						<?php wp_nonce_field( 'chout_aio_save_features' ); ?>

						<table class="widefat striped" style="max-width: 980px; margin-top: 20px;">
							<thead>
								<tr>
									<th scope="col" style="width: 60px;text-align: center;"><?php esc_html_e( 'Status', 'chout-all-in-one' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Features', 'chout-all-in-one' ); ?></th>
									<th scope="col" style="width: 80px;"></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $features as $slug => $feature ) : ?>
									<tr>
										<td style="text-align: center;">
											<label>
												<input
													type="checkbox"
													id="chout-aio-feature-<?php echo esc_attr( $slug ); ?>"
													name="features[]"
													value="<?php echo esc_attr( $slug ); ?>"
													<?php checked( ! empty( $status[ $slug ] ) ); ?>
												>
												<!-- <?php esc_html_e( 'Enable', 'chout-all-in-one' ); ?> -->
											</label>
										</td>
										<td>
											<label for="chout-aio-feature-<?php echo esc_attr( $slug ); ?>">
												<strong><?php echo esc_html( $feature['name'] ); ?></strong>
											</label>
											<p class="description"><?php echo esc_html( $feature['description'] ); ?></p>
										</td>
										<td>
											<?php if ( ! empty( $feature['configurable'] ) && ! empty( $status[ $slug ] ) && ! empty( $feature['menu_slug'] ) ) : ?>
												<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $feature['menu_slug'] ) ); ?>">
													<?php esc_html_e( 'Customize', 'chout-all-in-one' ); ?>
												</a>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<?php submit_button( __( 'Save Changes', 'chout-all-in-one' ) ); ?>
					</form>
				</div>
			</div>
			<?php
		}

		public static function save_features() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You are not authorized to perform this action.', 'chout-all-in-one' ) );
			}

			check_admin_referer( 'chout_aio_save_features' );

			$selected = filter_input( INPUT_POST, 'features', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
			$selected = is_array( $selected ) ? array_map( 'sanitize_key', wp_unslash( $selected ) ) : array();
			$selected = array_flip( $selected );
			$status   = array();

			foreach ( self::features() as $slug => $feature ) {
				$status[ $slug ] = isset( $selected[ $slug ] ) ? 1 : 0;
			}

			update_option( self::OPTION_FEATURES, $status, false );
			set_transient( self::settings_updated_transient_key(), 1, MINUTE_IN_SECONDS );

			wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
			exit;
		}

		private static function settings_updated_transient_key() {
			return 'chout_aio_settings_updated_' . get_current_user_id();
		}

		public static function add_settings_link( $links ) {
			array_unshift(
				$links,
				'<a href="' . esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ) . '">' . esc_html__( 'Settings', 'chout-all-in-one' ) . '</a>'
			);

			return $links;
		}
	}
}

register_activation_hook( __FILE__, array( 'Chout_AIO', 'activate' ) );
Chout_AIO::init();

/* Donate */
function chout_caio_donate_link_html() {
    $donate_url = 'https://chout.id.vn/donate';
    printf(
        '<a href="%1$s" target="_blank" rel="noopener noreferrer" class="err-donate-link" aria-label="%2$s"><span>%3$s 🚀</span></a>',
        esc_url( $donate_url ),
        esc_attr__( 'Donate to support this plugin', 'chout-all-in-one' ),
        esc_html__( 'Donate', 'chout-all-in-one' )
    );
}