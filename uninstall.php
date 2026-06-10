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
 * Delete plugin data for the current site.
 */
function chout_aio_uninstall_site() {
	delete_option( 'chout_aio_features' );
	delete_option( 'chout_aio_scroll_add_action_class' );

	global $wpdb;

	$transient_like = $wpdb->esc_like( '_transient_chout_aio_settings_updated_' ) . '%';
	$timeout_like   = $wpdb->esc_like( '_transient_timeout_chout_aio_settings_updated_' ) . '%';

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$transient_like,
			$timeout_like
		)
	);
}

if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		chout_aio_uninstall_site();
		restore_current_blog();
	}
} else {
	chout_aio_uninstall_site();
}
