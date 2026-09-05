<?php
/**
 * MPRO Forms uninstall routine.
 *
 * Forms and entries live in dedicated tables and are deliberately kept when the
 * plugin is deleted, so uninstalling and reinstalling — or updating by replacing
 * the folder — never loses a site's data. Everything is removed only when the
 * site owner explicitly opts in under Settings → General.
 *
 * @package MPROForms
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

wp_clear_scheduled_hook( 'mpro_forms_daily_cleanup' );

delete_transient( 'mpro_forms_remote_addons' );
delete_transient( 'mpro_forms_remote_docs' );

// The capability is meaningless clutter on the role once the plugin that
// checks it is gone, so this runs unconditionally rather than only when the
// site owner also opts into deleting forms and entries below.
$mpro_role = get_role( 'administrator' );

if ( $mpro_role instanceof WP_Role ) {
	$mpro_role->remove_cap( 'mpro_manage_forms' );
}

$mpro_forms_settings = get_option( 'mpro_forms_settings', array() );
$mpro_forms_settings = is_array( $mpro_forms_settings ) ? $mpro_forms_settings : array();

if ( empty( $mpro_forms_settings['delete_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
foreach ( array( 'entries', 'forms' ) as $mpro_table ) {
	$mpro_table_name = $wpdb->prefix . 'mpro_' . $mpro_table;
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $mpro_table_name ) );
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange

foreach (
	array(
		'mpro_forms_settings',
		'mpro_forms_schema_version',
		'mpro_forms_version',
		'mpro_forms_installed_at',
		'mpro_forms_show_welcome',
		'mpro_forms_retention_days',
		'mpro_forms_delete_data_on_uninstall',
	) as $mpro_option
) {
	delete_option( $mpro_option );
}

$mpro_patterns = array(
	$wpdb->esc_like( '_transient_mpro_forms_state_' ) . '%',
	$wpdb->esc_like( '_transient_mpro_forms_rate_' ) . '%',
);

foreach ( $mpro_patterns as $mpro_pattern ) {
	// A wildcard lookup is required because state and rate-limit tokens are intentionally opaque.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$mpro_option_names = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
			$mpro_pattern
		)
	);

	foreach ( $mpro_option_names as $mpro_option_name ) {
		delete_transient( substr( $mpro_option_name, strlen( '_transient_' ) ) );
	}
}
