<?php
/**
 * Free MPRO Forms uninstall routine.
 *
 * Forms and entries live in dedicated tables and are deliberately kept when the
 * plugin is deleted, so uninstalling and reinstalling — or updating by replacing
 * the folder — never loses a site's data. Everything is removed only when the
 * site owner explicitly opts in under Settings → General.
 *
 * @package FreeMPROForms
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

wp_clear_scheduled_hook( 'fmpf_daily_retention_cleanup' );

delete_transient( 'fmpf_remote_addons' );
delete_transient( 'fmpf_remote_docs' );

$fmpf_settings = get_option( 'fmpf_settings', array() );
$fmpf_settings = is_array( $fmpf_settings ) ? $fmpf_settings : array();

if ( empty( $fmpf_settings['delete_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
foreach ( array( 'entries', 'forms' ) as $fmpf_table ) {
	$fmpf_table_name = $wpdb->prefix . 'fmpf_' . $fmpf_table;
	$wpdb->query( "DROP TABLE IF EXISTS `{$fmpf_table_name}`" );
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange

foreach (
	array(
		'fmpf_settings',
		'fmpf_schema_version',
		'fmpf_version',
		'fmpf_installed_at',
		'fmpf_show_welcome',
		'fmpf_retention_days',
		'fmpf_delete_data_on_uninstall',
	) as $fmpf_option
) {
	delete_option( $fmpf_option );
}

$fmpf_role = get_role( 'administrator' );

if ( $fmpf_role instanceof WP_Role ) {
	$fmpf_role->remove_cap( 'fmpf_manage_forms' );
}

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
		delete_transient( substr( $fmpf_option_name, strlen( '_transient_' ) ) );
	}
}
