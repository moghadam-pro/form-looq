<?php
/**
 * Free MPRO Forms uninstall cleanup.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

wp_clear_scheduled_hook( 'fmpf_daily_retention_cleanup' );

if ( ! get_option( 'fmpf_delete_data_on_uninstall', false ) ) {
	return;
}

foreach ( array( 'fmpf_submission', 'fmpf_form' ) as $fmpf_post_type ) {
	do {
		$fmpf_query = new WP_Query(
			array(
				'post_type'              => $fmpf_post_type,
				'post_status'            => 'any',
				'fields'                 => 'ids',
				'posts_per_page'         => 100,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'cache_results'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$fmpf_ids                = array_map( 'absint', $fmpf_query->posts );
		$fmpf_deleted_this_batch = 0;

		foreach ( $fmpf_ids as $fmpf_post_id ) {
			if ( wp_delete_post( $fmpf_post_id, true ) ) {
				++$fmpf_deleted_this_batch;
			}
		}

		if ( $fmpf_ids && 0 === $fmpf_deleted_this_batch ) {
			break;
		}
	} while ( count( $fmpf_ids ) === 100 );
}

delete_option( 'fmpf_retention_days' );
delete_option( 'fmpf_delete_data_on_uninstall' );

global $wpdb;

$fmpf_transient_pattern = $wpdb->esc_like( '_transient_fmpf_state_' ) . '%';
$fmpf_timeout_pattern   = $wpdb->esc_like( '_transient_timeout_fmpf_state_' ) . '%';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall removes plugin-owned transient rows by prefix.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$fmpf_transient_pattern,
		$fmpf_timeout_pattern
	)
);
