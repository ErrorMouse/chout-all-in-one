<?php
/**
 * Uninstall Chout - All in One.
 *
 * @package Chout_All_In_One
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete per-user settings notice transients for the current site.
 */
function chout_aio_delete_settings_updated_transients() {
	$chout_aio_user_ids = get_users(
		array(
			'fields' => 'ID',
		)
	);

	foreach ( $chout_aio_user_ids as $chout_aio_user_id ) {
		delete_transient( 'chout_aio_settings_updated_' . $chout_aio_user_id );
	}
}

/**
 * Delete plugin data for the current site.
 */
function chout_aio_uninstall_site() {
	delete_option( 'chout_aio_features' );
	delete_option( 'chout_aio_update_mode' );
	
	// Clean up Scroll Add Action data
	delete_option( 'chout_aio_scroll_add_action_class' );
	delete_option( 'chout_aio_scroll_add_action_custom_css' );
	
	// Clean up Scroll Progress Bar data
	delete_option( 'chout_aio_scroll_progress_bar' );
	
	// Clean up Block IPs data
	delete_option( 'chout_aio_custom_blocked_ips' );
	delete_option( 'chout_aio_use_aio_ips' );
	delete_transient( 'chout_aio_github_blocked_ips' );

	// Remove generated PHP hash file
	$upload_dir = wp_upload_dir();
	$file_path  = $upload_dir['basedir'] . '/chout-aio-blocked-ips.php';
	if ( file_exists( $file_path ) ) {
		unlink( $file_path );
	}

	chout_aio_delete_settings_updated_transients();
}

if ( is_multisite() ) {
	$chout_aio_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $chout_aio_site_ids as $chout_aio_site_id ) {
		switch_to_blog( $chout_aio_site_id );
		chout_aio_uninstall_site();
		restore_current_blog();
	}
} else {
	chout_aio_uninstall_site();
}

delete_site_option( 'external_updates-chout-all-in-one' );
delete_site_transient( 'puc_manual_check_errors-chout-all-in-one' );
