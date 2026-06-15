<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Chout_AIO_Block_IPs' ) ) {
	final class Chout_AIO_Block_IPs {
		const OPTION_CUSTOM_IPS  = 'chout_aio_custom_blocked_ips';
		const OPTION_USE_AIO_IPS = 'chout_aio_use_aio_ips';
		const TRANSIENT_AIO_IPS  = 'chout_aio_github_blocked_ips';
		const AIO_IPS_URL        = 'https://raw.githubusercontent.com/ErrorMouse/chout-all-in-one/refs/heads/main/List-Block-IPs.txt';

		public static function init() {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
			add_action( 'wp_ajax_chout_aio_block_ips_action', array( __CLASS__, 'ajax_handler' ) );
			
			// Block via PHP as fallback for Nginx/IIS
			add_action( 'init', array( __CLASS__, 'check_and_block_ip' ), 0 );
			
			// Schedule cron to update AIO list daily if enabled
			if ( ! wp_next_scheduled( 'chout_aio_daily_ip_update' ) ) {
				wp_schedule_event( time(), 'daily', 'chout_aio_daily_ip_update' );
			}
			add_action( 'chout_aio_daily_ip_update', array( __CLASS__, 'force_fetch_aio_ips_cron' ) );
		}

		public static function force_fetch_aio_ips_cron() {
			self::fetch_aio_ips( true );
		}

		public static function enqueue_assets( $hook_suffix ) {
			// Only enqueue on our specific settings page
			if ( strpos( $hook_suffix, 'chout-aio-block-ips' ) === false ) {
				return;
			}

			wp_enqueue_style( 'chout-aio-block-ips', plugin_dir_url( __FILE__ ) . 'block-ips.css', array(), '1.0', 'all' );
			wp_enqueue_script( 'chout-aio-block-ips-script', plugin_dir_url( __FILE__ ) . 'block-ips.js', array( 'jquery' ), '1.0', true );
			
			wp_localize_script(
				'chout-aio-block-ips-script',
				'choutAioBlockIps',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'chout_aio_block_ips_nonce' ),
					'texts'    => array(
						'confirm_delete' => __( 'Are you sure you want to delete the selected IP(s)?', 'chout-all-in-one' ),
						'error'          => __( 'An error occurred. Please try again.', 'chout-all-in-one' ),
						'empty_ip'       => __( 'Please enter an IP address.', 'chout-all-in-one' ),
						'empty_file'     => __( 'Please select a CSV file.', 'chout-all-in-one' ),
					),
				)
			);
		}

		// --- Ajax Handlers ---

		public static function ajax_handler() {
			check_ajax_referer( 'chout_aio_block_ips_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Unauthorized', 'chout-all-in-one' ) ) );
			}

			$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';

			switch ( $type ) {
				case 'save_aio_setting':
					$use_aio = isset( $_POST['use_aio'] ) ? (bool) $_POST['use_aio'] : false;
					update_option( self::OPTION_USE_AIO_IPS, $use_aio, false );
					if ( $use_aio ) {
						self::fetch_aio_ips( true ); // force fetch
					}
					self::update_htaccess();
					wp_send_json_success( array( 'message' => __( 'Settings saved.', 'chout-all-in-one' ) ) );
					break;

				case 'add_ip':
					$ip   = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '';
					$note = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
					
					// Basic validation (allow partial like 206.189. or full IPv4/IPv6)
					$ip = trim( $ip );
					if ( empty( $ip ) ) {
						wp_send_json_error( array( 'message' => __( 'IP cannot be empty.', 'chout-all-in-one' ) ) );
					}

					if ( ! self::is_valid_ip_or_partial( $ip ) ) {
						wp_send_json_error( array( 'message' => __( 'Invalid IP format.', 'chout-all-in-one' ) ) );
					}

					$ip = self::format_ip_for_storage( $ip );

					$custom_ips = self::get_custom_ips();
					if ( isset( $custom_ips[ $ip ] ) ) {
						wp_send_json_error( array( 'message' => __( 'IP already exists.', 'chout-all-in-one' ) ) );
					}

					$custom_ips[ $ip ] = array(
						'date' => current_time( 'mysql' ),
						'note' => $note,
					);
					update_option( self::OPTION_CUSTOM_IPS, $custom_ips, false );
					self::update_htaccess();
					wp_send_json_success( array( 'message' => __( 'IP added successfully.', 'chout-all-in-one' ) ) );
					break;

				case 'delete_ips':
					$ips_to_delete = isset( $_POST['ips'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ips'] ) ) : array();
					if ( empty( $ips_to_delete ) ) {
						wp_send_json_error( array( 'message' => __( 'No IPs selected.', 'chout-all-in-one' ) ) );
					}

					$custom_ips = self::get_custom_ips();
					$changed    = false;
					foreach ( $ips_to_delete as $ip ) {
						if ( isset( $custom_ips[ $ip ] ) ) {
							unset( $custom_ips[ $ip ] );
							$changed = true;
						}
					}

					if ( $changed ) {
						update_option( self::OPTION_CUSTOM_IPS, $custom_ips, false );
						self::update_htaccess();
					}
					wp_send_json_success( array( 'message' => __( 'IPs deleted successfully.', 'chout-all-in-one' ) ) );
					break;

				case 'upload_csv':
					if ( empty( $_FILES['csv_file'] ) || ! isset( $_FILES['csv_file']['error'] ) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
						wp_send_json_error( array( 'message' => __( 'File upload failed.', 'chout-all-in-one' ) ) );
					}
					
					$file_tmp = isset( $_FILES['csv_file']['tmp_name'] ) ? sanitize_text_field( wp_unslash( $_FILES['csv_file']['tmp_name'] ) ) : '';
					
					require_once ABSPATH . 'wp-admin/includes/file.php';
					WP_Filesystem();
					global $wp_filesystem;

					if ( empty( $file_tmp ) || ! $wp_filesystem->exists( $file_tmp ) ) {
						wp_send_json_error( array( 'message' => __( 'File upload failed.', 'chout-all-in-one' ) ) );
					}

					$content = $wp_filesystem->get_contents( $file_tmp );
					if ( false === $content ) {
						wp_send_json_error( array( 'message' => __( 'Could not read file.', 'chout-all-in-one' ) ) );
					}

					$custom_ips = self::get_custom_ips();
					$added      = 0;
					$lines      = explode( "\n", $content );

					foreach ( $lines as $line_str ) {
						$data = str_getcsv( trim( $line_str ) );
						if ( empty( $data ) || ! isset( $data[0] ) || empty( trim( $data[0] ) ) ) {
							continue;
						}
						
						$ip = trim( $data[0] );
						
						// Basic skip header if it says 'ip' or 'ip address'
						if ( strtolower( $ip ) === 'ip' || strtolower( $ip ) === 'ip address' ) {
							continue;
						}
						
						// Validate IP (prevent 500 errors from invalid .htaccess rules)
						if ( ! self::is_valid_ip_or_partial( $ip ) ) {
							continue;
						}
						
						$ip = self::format_ip_for_storage( $ip );

						if ( ! isset( $custom_ips[ $ip ] ) ) {
							$custom_ips[ $ip ] = array(
								'date' => current_time( 'mysql' ),
								'note' => isset( $data[1] ) ? sanitize_textarea_field( wp_unslash( trim( $data[1] ) ) ) : '',
							);
							$added++;
						}
					}

					if ( $added > 0 ) {
						update_option( self::OPTION_CUSTOM_IPS, $custom_ips, false );
						self::update_htaccess();
						/* translators: %d: number of IPs added */
						wp_send_json_success( array( 'message' => sprintf( __( 'Successfully added %d IPs from CSV.', 'chout-all-in-one' ), $added ) ) );
					} else {
						wp_send_json_error( array( 'message' => __( 'No new IPs found or added from the CSV file.', 'chout-all-in-one' ) ) );
					}
					break;

				default:
					wp_send_json_error( array( 'message' => __( 'Invalid action.', 'chout-all-in-one' ) ) );
			}
		}

		// --- Data Retrieval ---

		public static function get_custom_ips() {
			$ips = get_option( self::OPTION_CUSTOM_IPS, array() );
			return is_array( $ips ) ? $ips : array();
		}

		public static function fetch_aio_ips( $force = false ) {
			if ( ! get_option( self::OPTION_USE_AIO_IPS, false ) ) {
				return array();
			}

			$cached = get_transient( self::TRANSIENT_AIO_IPS );
			if ( ! $force && false !== $cached ) {
				return $cached;
			}

			$response = wp_remote_get( self::AIO_IPS_URL, array( 'timeout' => 10 ) );
			if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
				return is_array( $cached ) ? $cached : array(); // Fallback to cache if request fails
			}

			$body = wp_remote_retrieve_body( $response );
			$lines = explode( "\n", $body );
			$ips   = array();
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( ! empty( $line ) && self::is_valid_ip_or_partial( $line ) ) {
					$ips[] = self::format_ip_for_storage( $line );
				}
			}

			$ips = array_unique( $ips );
			set_transient( self::TRANSIENT_AIO_IPS, $ips, DAY_IN_SECONDS );
			self::update_htaccess(); // Update htaccess when fetched new list
			return $ips;
		}

		public static function get_aio_ips() {
			if ( ! get_option( self::OPTION_USE_AIO_IPS, false ) ) {
				return array();
			}
			$ips = get_transient( self::TRANSIENT_AIO_IPS );
			if ( false === $ips ) {
				return self::fetch_aio_ips();
			}
			return is_array( $ips ) ? $ips : array();
		}

		public static function get_all_blocked_ips() {
			$custom_ips = array_keys( self::get_custom_ips() );
			$aio_ips    = self::get_aio_ips();
			return array_unique( array_merge( $custom_ips, $aio_ips ) );
		}

		public static function is_valid_ip_or_partial( $ip ) {
			$ip = rtrim( $ip, '.' ); // remove trailing dot for check
			
			if ( empty( $ip ) ) {
				return false;
			}
			
			// Full IP check
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return true;
			}
			
			// IPv4 partial check
			if ( strpos( $ip, '.' ) !== false || is_numeric( $ip ) ) {
				$parts = explode( '.', $ip );
				if ( count( $parts ) > 0 && count( $parts ) <= 4 ) {
					foreach ( $parts as $part ) {
						if ( $part === '' || ! is_numeric( $part ) || intval( $part ) < 0 || intval( $part ) > 255 ) {
							return false;
						}
					}
					return true;
				}
			}
			
			// IPv6 partial check (at least one colon, valid hex characters)
			if ( strpos( $ip, ':' ) !== false && preg_match( '/^[0-9a-fA-F:]+$/', $ip ) ) {
				$parts = explode( ':', $ip );
				if ( count( $parts ) <= 8 ) {
					return true;
				}
			}
			
			return false;
		}

		public static function format_ip_for_storage( $ip ) {
			$ip = trim( $ip );
			
			// IPv6
			if ( strpos( $ip, ':' ) !== false ) {
				return rtrim( $ip, ':' ); // Remove trailing colon if any, just in case
			}
			
			// IPv4
			$ip = rtrim( $ip, '.' );
			if ( substr_count( $ip, '.' ) === 3 ) {
				return $ip; // Full IP
			}
			
			return $ip . '.'; // Partial IP
		}

		// --- Core Blocking Logic ---

		public static function check_and_block_ip() {
			// Do not block in CLI or admin (optional: maybe block admin too? Usually safer to allow admin or check specific roles, but for true IP block it should block everywhere. Let's block everywhere except maybe allow bypass if we can't reliably get IP).
			if ( wp_is_cli() ) {
				return;
			}

			$user_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			if ( empty( $user_ip ) ) {
				return;
			}

			$blocked_ips = self::get_all_blocked_ips();
			if ( empty( $blocked_ips ) ) {
				return;
			}

			foreach ( $blocked_ips as $blocked_ip ) {
				// Direct match or partial match (for e.g., 206.189.)
				if ( $user_ip === $blocked_ip || strpos( $user_ip, $blocked_ip ) === 0 ) {
					wp_die( esc_html__( 'Your IP address has been blocked from accessing this website.', 'chout-all-in-one' ), esc_html__( 'Access Denied', 'chout-all-in-one' ), array( 'response' => 403 ) );
				}
			}
		}

		public static function update_htaccess() {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';

			$htaccess_file = get_home_path() . '.htaccess';
			
			// Only update if htaccess is writable or we can create it
			if ( ( file_exists( $htaccess_file ) && wp_is_writable( $htaccess_file ) ) || wp_is_writable( dirname( $htaccess_file ) ) ) {
				$blocked_ips = self::get_all_blocked_ips();
				$rules       = array();

				if ( ! empty( $blocked_ips ) ) {
					$rules[] = '<IfModule mod_authz_core.c>';
					$rules[] = '    <RequireAll>';
					$rules[] = '        Require all granted';
					foreach ( $blocked_ips as $ip ) {
						// Apache 2.4 "Require ip" prefers partial IPs without trailing dot (e.g. 10.1 instead of 10.1.)
						$clean_ip = rtrim( $ip, '.' );
						$rules[] = '        Require not ip ' . $clean_ip;
					}
					$rules[] = '    </RequireAll>';
					$rules[] = '</IfModule>';
					$rules[] = '<IfModule !mod_authz_core.c>';
					$rules[] = '    Order Allow,Deny';
					$rules[] = '    Allow from all';
					foreach ( $blocked_ips as $ip ) {
						$rules[] = '    Deny from ' . $ip;
					}
					$rules[] = '</IfModule>';
				}

				insert_with_markers( $htaccess_file, 'Chout_AIO_Block_IPs', $rules );
			}
		}

		// --- Settings Page ---

		public static function settings_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'chout-all-in-one' ) );
			}

			$use_aio_ips = get_option( self::OPTION_USE_AIO_IPS, false );
			$aio_ips     = self::get_aio_ips();
			$custom_ips  = self::get_custom_ips();
			?>
			<div id="chout-aio-block-ips" class="chout-all-in-one">
				<div class="caio-wrap">
					<h1><?php esc_html_e( 'Chout - Block IPs', 'chout-all-in-one' ); ?><span class="author">by <a href="https://profiles.wordpress.org/nmtnguyen56/" target="_blank" rel="noopener noreferrer">Chout</a></span><span class="donate"><?php chout_caio_donate_link_html(); ?></span></h1>
					
					<div class="notice notice-success is-dismissible" id="caio-message" style="display: none;">
						<p></p>
					</div>
					<div class="notice notice-error is-dismissible" id="caio-error" style="display: none;">
						<p></p>
					</div>

					<div class="caio-card">
						<h2><?php esc_html_e( 'AIO Blocklist', 'chout-all-in-one' ); ?></h2>
						<p><?php esc_html_e( 'Use the community-provided blacklist to block known malicious IPs automatically.', 'chout-all-in-one' ); ?></p>
						
						<label class="caio-switch">
							<input type="checkbox" id="caio_use_aio_ips" <?php checked( $use_aio_ips ); ?>>
							<?php esc_html_e( 'Use the IP address list detected by AIO.', 'chout-all-in-one' ); ?>
						</label>
						
						<div class="caio-aio-list-container" <?php echo $use_aio_ips ? '' : 'style="display:none;"'; ?>>
							<p><strong><?php esc_html_e( 'AIO Provided IPs (Read-only):', 'chout-all-in-one' ); ?></strong></p>
							<textarea readonly class="large-text" rows="5"><?php echo esc_textarea( implode( "\n", $aio_ips ) ); ?></textarea>
						</div>
					</div>

					<div class="caio-card">
						<h2><?php esc_html_e( 'Custom IP Blocklist', 'chout-all-in-one' ); ?></h2>
						<p><?php esc_html_e( 'Add specific IPs you want to block. You can use full IPs or partial IPs (e.g., 206.189.).', 'chout-all-in-one' ); ?></p>
						
						<div class="caio-add-ip-form">
							<input type="text" id="caio_new_ip" placeholder="<?php esc_attr_e( 'IP Address', 'chout-all-in-one' ); ?>" class="regular-text">
							<input type="text" id="caio_new_note" placeholder="<?php esc_attr_e( 'Note (Optional)', 'chout-all-in-one' ); ?>" class="regular-text">
							<button type="button" id="caio_add_ip_btn" class="button button-primary"><?php esc_html_e( 'Add IP', 'chout-all-in-one' ); ?></button>
							<button type="button" id="caio_upload_csv_btn" class="button button-secondary"><?php esc_html_e( 'Upload CSV', 'chout-all-in-one' ); ?></button>
							<input type="file" id="caio_csv_file" accept=".csv" style="display:none;">
						</div>
						<p class="description"><?php esc_html_e( 'Upload a CSV file containing IPs (Column 1: IP, Column 2: Note).', 'chout-all-in-one' ); ?></p>

						<hr>

						<div class="caio-table-toolbar">
							<button type="button" id="caio_bulk_delete_btn" class="button button-secondary action" style="display:none;"><?php esc_html_e( 'Delete Selected', 'chout-all-in-one' ); ?></button>
							<input type="text" id="caio_search_ip" placeholder="<?php esc_attr_e( 'Search IPs...', 'chout-all-in-one' ); ?>">
						</div>

						<div style="overflow-x: auto;">
							<table class="wp-list-table widefat fixed striped" id="caio_ips_table">
								<thead>
									<tr>
										<td id="cb" class="manage-column column-cb check-column">
											<input id="cb-select-all" type="checkbox">
										</td>
										<th scope="col" class="manage-column sortable desc" data-sort="ip">
											<a href="#"><span><?php esc_html_e( 'IP Address', 'chout-all-in-one' ); ?></span><span class="sorting-indicator"></span></a>
										</th>
										<th scope="col" class="manage-column sortable desc" data-sort="date">
											<a href="#"><span><?php esc_html_e( 'Date Added', 'chout-all-in-one' ); ?></span><span class="sorting-indicator"></span></a>
										</th>
										<th scope="col" class="manage-column"><?php esc_html_e( 'Note', 'chout-all-in-one' ); ?></th>
										<th scope="col" class="manage-column" style="width: 80px;"><?php esc_html_e( 'Actions', 'chout-all-in-one' ); ?></th>
									</tr>
								</thead>
								<tbody id="the-list">
									<?php if ( empty( $custom_ips ) ) : ?>
										<tr class="no-items"><td class="colspanchange" colspan="5"><?php esc_html_e( 'No IPs added yet.', 'chout-all-in-one' ); ?></td></tr>
									<?php else : ?>
										<?php foreach ( $custom_ips as $ip => $data ) : ?>
											<tr data-ip="<?php echo esc_attr( $ip ); ?>">
												<th scope="row" class="check-column">
													<input type="checkbox" name="ip[]" value="<?php echo esc_attr( $ip ); ?>">
												</th>
												<td class="ip-col"><strong><?php echo esc_html( $ip ); ?></strong></td>
												<td class="date-col"><?php echo esc_html( $data['date'] ); ?></td>
												<td class="note-col"><?php echo esc_html( $data['note'] ); ?></td>
												<td>
													<button type="button" class="button button-secondary caio-delete-btn" data-ip="<?php echo esc_attr( $ip ); ?>"><?php esc_html_e( 'Delete', 'chout-all-in-one' ); ?></button>
												</td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}
}
