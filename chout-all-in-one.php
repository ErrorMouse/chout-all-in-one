<?php
/**
 * Plugin Name:       Chout - All in One
 * Description:       A single control panel for enabling small website features only when you need them.
 * Version:           1.1.5
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

// --- Dynamic Update Checker Mode ---
$chout_caio_update_mode = get_option( 'chout_aio_update_mode', 'github' ); // Default to GitHub

// Handle toggle
add_action( 'admin_init', function() use ( $chout_caio_update_mode ) {
	if ( isset( $_GET['chout_aio_toggle_update'] ) && current_user_can( 'manage_options' ) ) {
		check_admin_referer( 'chout_aio_toggle_update' );
		$new_mode = ( $chout_caio_update_mode === 'github' ) ? 'json' : 'github';
		update_option( 'chout_aio_update_mode', $new_mode, false );
		wp_safe_redirect( admin_url( 'plugins.php' ) );
		exit;
	}
});

require __DIR__ . '/vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

if ( $chout_caio_update_mode === 'github' ) {
	$myUpdateChecker = PucFactory::buildUpdateChecker(
		'https://github.com/ErrorMouse/chout-all-in-one/',
		__FILE__,
		'chout-all-in-one'
	);
	$myUpdateChecker->setBranch('main');
} else {
	$myUpdateChecker = PucFactory::buildUpdateChecker(
		'https://raw.githubusercontent.com/ErrorMouse/chout-all-in-one/refs/heads/main/chout-all-in-one.json',
		__FILE__,
		'chout-all-in-one'
	);
}

// Add toggle link to plugin row meta
add_filter( 'plugin_row_meta', function( $links, $file ) use ( $chout_caio_update_mode ) {
	if ( plugin_basename( __FILE__ ) === $file ) {
		$toggle_url = wp_nonce_url( admin_url( 'plugins.php?chout_aio_toggle_update=1' ), 'chout_aio_toggle_update' );
		$checked    = ( $chout_caio_update_mode === 'json' ) ? 'checked' : '';
		
		$toggle_html = '<style>
		.caio-switch{position:relative;display:inline-block;width:32px;height:18px;vertical-align:middle;margin:0 5px 0 0;}
		.caio-switch input{opacity:0;width:0;height:0;}
		.caio-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background-color:#ccc;transition:.3s;border-radius:18px;}
		.caio-slider:before{position:absolute;content:"";height:14px;width:14px;left:2px;bottom:2px;background-color:white;transition:.3s;border-radius:50%;box-shadow:0 1px 2px rgba(0,0,0,0.2);}
		.caio-switch input:checked+.caio-slider{background-color:#22c55e;}
		.caio-switch input:checked+.caio-slider:before{transform:translateX(14px);}
		</style>
		<label class="caio-switch" title="' . esc_attr__( 'Enable to update via static JSON (prevents API 403 errors). Disable to update via GitHub API.', 'chout-all-in-one' ) . '">
			<input type="checkbox" onchange="window.location.href=\'' . esc_js( $toggle_url ) . '\'" ' . $checked . '>
			<span class="caio-slider"></span>
		</label>
		<span style="vertical-align:middle;color:#0073aa;font-weight:500;">' . esc_html__( 'Update via JSON', 'chout-all-in-one' ) . '</span>';
		
		$links[] = $toggle_html;
	}
	return $links;
}, 10, 2 );
// --- End Update Checker ---

if ( ! class_exists( 'Chout_AIO' ) ) {
	final class Chout_AIO {
		const VERSION         = '1.1.5';
		const OPTION_VERSION  = 'chout_aio_db_version';
		const OPTION_FEATURES = 'chout_aio_features';
		const MENU_SLUG       = 'chout-all-in-one';

		public static function init() {
			add_action( 'admin_init', array( __CLASS__, 'upgrade_routine' ) );
			add_action( 'init', array( __CLASS__, 'load_enabled_features' ), 0 );
			add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
			add_action( 'admin_post_chout_aio_save_features', array( __CLASS__, 'save_features' ) ); // Keep for fallback
			add_action( 'wp_ajax_chout_aio_toggle_feature', array( __CLASS__, 'ajax_toggle_feature' ) );
			add_action( 'wp_ajax_chout_aio_toggle_all_features', array( __CLASS__, 'ajax_toggle_all_features' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'add_settings_link' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_styles' ) );
		}

		public static function upgrade_routine() {
			$db_version = get_option( self::OPTION_VERSION, '1.0.0' );
			
			if ( version_compare( $db_version, self::VERSION, '<' ) ) {
				if ( version_compare( $db_version, '1.1.5', '<' ) ) {
					require_once ABSPATH . 'wp-admin/includes/misc.php';
					require_once ABSPATH . 'wp-admin/includes/file.php';
					$htaccess_file = get_home_path() . '.htaccess';
					
					if ( file_exists( $htaccess_file ) && wp_is_writable( $htaccess_file ) ) {
						// 1. Ask WP to clear the inner content
						insert_with_markers( $htaccess_file, 'Chout_AIO_Block_IPs', array() );
						
						// 2. Force remove the empty # BEGIN and # END marker tags left behind
						$content = file_get_contents( $htaccess_file );
						if ( false !== $content ) {
							$content = preg_replace( '/# BEGIN Chout_AIO_Block_IPs.*?# END Chout_AIO_Block_IPs\n?/s', '', $content );
							// Clean up triple newlines caused by deletion
							$content = preg_replace( "/\n{3,}/", "\n\n", $content );
							file_put_contents( $htaccess_file, $content );
						}
					}
				}
				
				update_option( self::OPTION_VERSION, self::VERSION );
			}
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
			wp_enqueue_script(
				'chout-aio-admin-script',
				plugin_dir_url( __FILE__ ) . 'assets/js/admin-settings.js',
				array( 'jquery' ),
				self::VERSION,
				true
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
				'add-featured-image-column'               => array(
					'name'        => __( 'Add Featured Image Column', 'chout-all-in-one' ),
					'description' => __( 'Display the featured image thumbnail in the post list.', 'chout-all-in-one' ),
					'file'        => 'add-featured-image-column/add-featured-image-column.php',
					'class'       => 'Chout_AIO_Add_Featured_Image_Column',
				),
				'add-media-file-size-column'              => array(
					'name'        => __( 'Add Media File Size Column', 'chout-all-in-one' ),
					'description' => __( 'Display file size column in the Media Library list view.', 'chout-all-in-one' ),
					'file'        => 'add-media-file-size-column/add-media-file-size-column.php',
					'class'       => 'Chout_AIO_Add_Media_File_Size_Column',
				),
				'add-signature-to-rss'           		=> array(
					'name'        => __( 'Add Signature to RSS', 'chout-all-in-one' ),
					'description' => __( 'Add a source identifier to content distributed via RSS.', 'chout-all-in-one' ),
					'file'        => 'add-signature-to-rss/add-signature-to-rss.php',
					'class'       => 'Chout_AIO_Add_Signature_To_RSS',
				),
				'allow-svg-files-upload'                  => array(
					'name'        => __( 'Allow SVG Files Upload', 'chout-all-in-one' ),
					'description' => __( 'Allow administrator users to upload SVG files safely.', 'chout-all-in-one' ),
					'file'        => 'allow-svg-files-upload/allow-svg-files-upload.php',
					'class'       => 'Chout_AIO_Allow_SVG_Upload',
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
				'disable-comments'                        => array(
					'name'        => __( 'Disable Comments', 'chout-all-in-one' ),
					'description' => __( 'Completely disable comments and remove the Comments menu from the dashboard.', 'chout-all-in-one' ),
					'file'        => 'disable-comments/disable-comments.php',
					'class'       => 'Chout_AIO_Disable_Comments',
				),
				'disable-emojis'                          => array(
					'name'        => __( 'Disable Emojis', 'chout-all-in-one' ),
					'description' => __( 'Remove WordPress core emoji scripts and styles to improve page loading speed.', 'chout-all-in-one' ),
					'file'        => 'disable-emojis/disable-emojis.php',
					'class'       => 'Chout_AIO_Disable_Emojis',
				),
				'disable-jquery-migrate'                  => array(
					'name'        => __( 'Disable jQuery Migrate', 'chout-all-in-one' ),
					'description' => __( 'Deregister the jquery-migrate script from the frontend to save bandwidth.', 'chout-all-in-one' ),
					'file'        => 'disable-jquery-migrate/disable-jquery-migrate.php',
					'class'       => 'Chout_AIO_Disable_jQuery_Migrate',
				),
				'disable-search-redirect-to-home'		=> array(
					'name'        => __( 'Disable Search & Redirect to Home', 'chout-all-in-one' ),
					'description' => __( 'Disable site search and redirect visitors to the homepage when they attempt a search.', 'chout-all-in-one' ),
					'file'        => 'disable-search-redirect-to-home/disable-search-redirect-to-home.php',
					'class'       => 'Chout_AIO_Disable_Search',
				),
				'disable-xml-rpc'                         => array(
					'name'        => __( 'Disable XML-RPC', 'chout-all-in-one' ),
					'description' => __( 'Completely disable XML-RPC to improve website security and prevent brute force attacks.', 'chout-all-in-one' ),
					'file'        => 'disable-xml-rpc/disable-xml-rpc.php',
					'class'       => 'Chout_AIO_Disable_XML_RPC',
				),
				'display-dashicons'                		=> array(
					'name'        => __( 'Display Dashicons', 'chout-all-in-one' ),
					'description' => __( 'Make WordPress dashicons available on the public-facing site.', 'chout-all-in-one' ),
					'file'        => 'display-dashicons/display-dashicons.php',
					'class'       => 'Chout_AIO_Display_Dashicons',
				),
				'keywords-everywhere'           		=> array(
					'name'        => __( 'Keywords Everywhere', 'chout-all-in-one' ),
					'description' => __( 'Add subtle keyword signals to content.', 'chout-all-in-one' ),
					'file'        => 'keywords-everywhere/keywords-everywhere.php',
					'class'       => 'Chout_AIO_Keywords_Everywhere',
				),
				'redirects-to-the-homepage-upon-logout'	=> array(
					'name'        => __( 'Redirect to Homepage Upon Logout', 'chout-all-in-one' ),
					'description' => __( 'Send users back to the homepage after they log out instead of the login screen.', 'chout-all-in-one' ),
					'file'        => 'redirects-to-the-homepage-upon-logout/redirects-to-the-homepage-upon-logout.php',
					'class'       => 'Chout_AIO_Logout_Redirect',
				),
				'remove-wp-logo-from-admin-bar'           => array(
					'name'        => __( 'Remove WP Logo From Admin Bar', 'chout-all-in-one' ),
					'description' => __( 'Remove the WordPress logo menu from the top admin bar.', 'chout-all-in-one' ),
					'file'        => 'remove-wp-logo-from-admin-bar/remove-wp-logo-from-admin-bar.php',
					'class'       => 'Chout_AIO_Remove_WP_Logo_From_Admin_Bar',
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
				'scroll-progress-bar'                     => array(
					'name'            => __( 'Scroll Progress Bar', 'chout-all-in-one' ),
					'description'     => __( 'Display a reading progress bar at the top or bottom of the screen as users scroll.', 'chout-all-in-one' ),
					'file'            => 'scroll-progress-bar/scroll-progress-bar.php',
					'class'           => 'Chout_AIO_Scroll_Progress_Bar',
					'configurable'    => true,
					'menu_slug'       => 'chout-aio-scroll-progress-bar',
					'config_callback' => array( 'Chout_AIO_Scroll_Progress_Bar', 'settings_page' ),
				),
				'slick-custom'                        	=> array(
					'name'        => __( 'Slick Custom', 'chout-all-in-one' ),
					'description' => __( 'Add support for displaying content in slideshow format on the website.', 'chout-all-in-one' ),
					'file'        => 'slick-custom/slick-custom.php',
					'class'       => 'Chout_AIO_Slick_Custom',
				),
				'snow-effect'                     		=> array(
					'name'        => __( 'Snow Effect', 'chout-all-in-one' ),
					'description' => __( 'Add a gentle snowfall effect to your website for seasonal decoration.', 'chout-all-in-one' ),
					'file'        => 'snow-effect/snow-effect.php',
					'class'       => 'Chout_AIO_Snow_Effect',
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
				'dashicons-fullscreen-exit-alt',
				58
			);

			add_submenu_page(
				self::MENU_SLUG,
				__( 'Settings', 'chout-all-in-one' ),
				__( 'Settings', 'chout-all-in-one' ),
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
			?>

			<div class="chout-background-effect"></div>

			<div id="chout-all-in-one" class="chout-all-in-one">
				<div class="caio-wrap">
					<h1>Chout - All in One</h1>

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
					
					<h2><?php esc_html_e( 'Features', 'chout-all-in-one' ); ?></h2>
					<hr class="hr-h2">

					<div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 30px;">
						<span style="font-weight: bold; color: var(--color-text-1);"><?php esc_html_e( 'Toggle All', 'chout-all-in-one' ); ?></span>
						<label class="caio-switch" title="<?php esc_attr_e( 'Toggle all features', 'chout-all-in-one' ); ?>" style="margin: 0;">
							<input type="checkbox" id="caio-toggle-all">
							<span class="caio-slider"></span>
						</label>
					</div>

					<input type="hidden" id="chout_aio_toggle_nonce" value="<?php echo esc_attr( wp_create_nonce( 'chout_aio_toggle_feature' ) ); ?>">

					<div class="caio-features-flex">
						<?php foreach ( $features as $slug => $feature ) : 
							$is_active = ! empty( $status[ $slug ] );
							$has_settings = ! empty( $feature['configurable'] ) && ! empty( $feature['menu_slug'] );
							?>
							<div class="caio-feature-card" id="card-<?php echo esc_attr( $slug ); ?>">
								<h3><?php echo esc_html( $feature['name'] ); ?></h3>
								<p class="description"><?php echo esc_html( $feature['description'] ); ?></p>
								
								<?php if ( ! empty( $feature['demo_url'] ) ) : ?>
									<div>
										<a class="caio-demo-link" href="<?php echo esc_url( $feature['demo_url'] ); ?>" target="_blank" style="font-size: 13px; text-decoration: none; color: #2271b1; display: inline-flex; align-items: center; gap: 4px;">
											<?php esc_html_e( 'View Demo', 'chout-all-in-one' ); ?> 
											<span class="dashicons dashicons-external" style="font-size: 14px; width: 14px; height: 14px;"></span>
										</a>
									</div>
								<?php endif; ?>

								<div class="caio-feature-actions">
									<label class="caio-switch" title="<?php esc_attr_e( 'Toggle feature', 'chout-all-in-one' ); ?>">
										<input type="checkbox" class="caio-feature-toggle" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $is_active ); ?>>
										<span class="caio-slider"></span>
									</label>

									<?php if ( $has_settings ) : ?>
										<a class="button button-secondary caio-settings-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $feature['menu_slug'] ) ); ?>" style="<?php echo $is_active ? '' : 'display:none;'; ?>">
											<?php esc_html_e( 'Settings', 'chout-all-in-one' ); ?>
										</a>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<script>
			jQuery(document).ready(function($) {
				function checkToggleAllState() {
					var total = $('.caio-feature-toggle').length;
					var checked = $('.caio-feature-toggle:checked').length;
					if (total === checked && total > 0) {
						$('#caio-toggle-all').prop('checked', true);
					} else {
						$('#caio-toggle-all').prop('checked', false);
					}
				}
				checkToggleAllState();

				$('#caio-toggle-all').on('change', function() {
					var isChecked = $(this).is(':checked') ? 1 : 0;
					var nonce = $('#chout_aio_toggle_nonce').val();
					
					$('.caio-features-flex').css('opacity', '0.6');

					$.post(ajaxurl, {
						action: 'chout_aio_toggle_all_features',
						status: isChecked,
						nonce: nonce
					}, function(response) {
						$('.caio-features-flex').css('opacity', '1');
						if (response.success) {
							$('.caio-feature-toggle').prop('checked', isChecked);
							if (isChecked) {
								$('.caio-settings-btn').fadeIn('fast');
							} else {
								$('.caio-settings-btn').fadeOut('fast');
							}
							caioShowToast('<?php esc_html_e( 'All settings updated successfully.', 'chout-all-in-one' ); ?>');
						} else {
							caioShowToast('<?php esc_html_e( 'Error saving settings.', 'chout-all-in-one' ); ?>', true);
							$('#caio-toggle-all').prop('checked', !isChecked);
						}
					}).fail(function() {
						$('.caio-features-flex').css('opacity', '1');
						caioShowToast('<?php esc_html_e( 'Connection error.', 'chout-all-in-one' ); ?>', true);
						$('#caio-toggle-all').prop('checked', !isChecked);
					});
				});

				$('.caio-feature-toggle').on('change', function() {
					var $checkbox = $(this);
					var slug = $checkbox.val();
					var isChecked = $checkbox.is(':checked') ? 1 : 0;
					var nonce = $('#chout_aio_toggle_nonce').val();
					var $card = $('#card-' + slug);

					checkToggleAllState();
					$card.css('opacity', '0.6');

					$.post(ajaxurl, {
						action: 'chout_aio_toggle_feature',
						feature: slug,
						status: isChecked,
						nonce: nonce
					}, function(response) {
						$card.css('opacity', '1');
						if (response.success) {
							if (isChecked) {
								$card.find('.caio-settings-btn').fadeIn('fast');
							} else {
								$card.find('.caio-settings-btn').fadeOut('fast');
							}
							caioShowToast('<?php esc_html_e( 'Setting updated successfully.', 'chout-all-in-one' ); ?>');
						} else {
							caioShowToast('<?php esc_html_e( 'Error saving setting.', 'chout-all-in-one' ); ?>', true);
							$checkbox.prop('checked', !isChecked);
							checkToggleAllState();
						}
					}).fail(function() {
						$card.css('opacity', '1');
						caioShowToast('<?php esc_html_e( 'Connection error.', 'chout-all-in-one' ); ?>', true);
						$checkbox.prop('checked', !isChecked);
						checkToggleAllState();
					});
				});
			});
			</script>
			<?php
		}

		public static function ajax_toggle_all_features() {
			check_ajax_referer( 'chout_aio_toggle_feature', 'nonce' );
			
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'Unauthorized' );
			}

			$status = isset( $_POST['status'] ) ? intval( $_POST['status'] ) : 0;
			$all_features = self::features();
			$current_options = array();

			if ( $status === 1 ) {
				foreach ( $all_features as $slug => $data ) {
					$current_options[ $slug ] = '1';
				}
			}

			update_option( self::OPTION_FEATURES, $current_options, false );
			wp_send_json_success();
		}

		public static function ajax_toggle_feature() {
			check_ajax_referer( 'chout_aio_toggle_feature', 'nonce' );
			
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'Unauthorized' );
			}

			$feature = isset( $_POST['feature'] ) ? sanitize_text_field( wp_unslash( $_POST['feature'] ) ) : '';
			$status  = isset( $_POST['status'] ) ? intval( $_POST['status'] ) : 0;

			$features = self::features();
			if ( ! isset( $features[ $feature ] ) ) {
				wp_send_json_error( 'Invalid feature' );
			}

			$active = self::get_feature_status();
			if ( $status ) {
				$active[ $feature ] = true;
			} else {
				unset( $active[ $feature ] );
			}

			update_option( self::OPTION_FEATURES, $active );
			wp_send_json_success();
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