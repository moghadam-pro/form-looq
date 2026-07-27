<?php
/**
 * Free MPRO Forms uninstall cleanup.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

wp_clear_scheduled_hook( 'fmpf_daily_retention_cleanup' );

if ( ! get_option( 'fmpf_delete_data_on_uninstall', false ) ) {
	return;
}

foreach ( array( 'fmpf_submission', 'fmpf_form' ) as $post_type ) {
	do {
		$query = new WP_Query(
			array(
				'post_type'              => $post_type,
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

		$ids                = array_map( 'absint', $query->posts );
		$deleted_this_batch = 0;

		foreach ( $ids as $post_id ) {
			if ( wp_delete_post( $post_id, true ) ) {
				++$deleted_this_batch;
			}
		}

		if ( $ids && 0 === $deleted_this_batch ) {
			break;
		}
	} while ( count( $ids ) === 100 );
}

delete_option( 'fmpf_retention_days' );
delete_option( 'fmpf_delete_data_on_uninstall' );

global $wpdb;

$transient_pattern = $wpdb->esc_like( '_transient_fmpf_state_' ) . '%';
$timeout_pattern   = $wpdb->esc_like( '_transient_timeout_fmpf_state_' ) . '%';

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$transient_pattern,
		$timeout_pattern
	)
);
