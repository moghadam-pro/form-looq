<?php
/**
 * Free MPRO Forms uninstall cleanup.
 *
 * @package FreeMPROForms
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
		$fmpf_batch_size         = count( $fmpf_ids );

		foreach ( $fmpf_ids as $fmpf_post_id ) {
			if ( wp_delete_post( $fmpf_post_id, true ) ) {
				++$fmpf_deleted_this_batch;
			}
		}

		if ( $fmpf_ids && 0 === $fmpf_deleted_this_batch ) {
			break;
		}
	} while ( 100 === $fmpf_batch_size );
}

delete_option( 'fmpf_retention_days' );
delete_option( 'fmpf_delete_data_on_uninstall' );

global $wpdb;

$fmpf_patterns = array(
	$wpdb->esc_like( '_transient_fmpf_state_' ) . '%',
	$wpdb->esc_like( '_transient_fmpf_rate_' ) . '%',
);

foreach ( $fmpf_patterns as $fmpf_pattern ) {
	// A wildcard lookup is required because state and rate-limit tokens are intentionally opaque.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$fmpf_option_names = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
			$fmpf_pattern
		)
	);

	foreach ( $fmpf_option_names as $fmpf_option_name ) {
		$fmpf_transient_name = substr( $fmpf_option_name, strlen( '_transient_' ) );
		delete_transient( $fmpf_transient_name );
	}
}
