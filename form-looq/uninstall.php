<?php
/**
 * Form LOOQ uninstall routine.
 *
 * Forms and entries live in dedicated tables and are deliberately kept when the
 * plugin is deleted, so uninstalling and reinstalling — or updating by replacing
 * the folder — never loses a site's data. Everything is removed only when the
 * site owner explicitly opts in under Settings → General.
 *
 * @package FormLooq
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

wp_clear_scheduled_hook( 'form_looq_daily_cleanup' );

delete_transient( 'form_looq_remote_addons' );
delete_transient( 'form_looq_remote_docs' );

// The capability is meaningless clutter on the role once the plugin that
// checks it is gone, so this runs unconditionally rather than only when the
// site owner also opts into deleting forms and entries below.
$looq_role = get_role( 'administrator' );

if ( $looq_role instanceof WP_Role ) {
	$looq_role->remove_cap( 'looq_manage_forms' );
}

$form_looq_settings = get_option( 'form_looq_settings', array() );
$form_looq_settings = is_array( $form_looq_settings ) ? $form_looq_settings : array();

if ( empty( $form_looq_settings['delete_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
foreach ( array( 'entries', 'forms' ) as $looq_table ) {
	$looq_table_name = $wpdb->prefix . 'looq_' . $looq_table;
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $looq_table_name ) );
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange

foreach (
	array(
		'form_looq_settings',
		'form_looq_schema_version',
		'form_looq_version',
		'form_looq_installed_at',
		'form_looq_show_welcome',
		'form_looq_retention_days',
		'form_looq_delete_data_on_uninstall',
	) as $looq_option
) {
	delete_option( $looq_option );
}

$looq_patterns = array(
	$wpdb->esc_like( '_transient_form_looq_state_' ) . '%',
	$wpdb->esc_like( '_transient_form_looq_rate_' ) . '%',
);

foreach ( $looq_patterns as $looq_pattern ) {
	// A wildcard lookup is required because state and rate-limit tokens are intentionally opaque.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$looq_option_names = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
			$looq_pattern
		)
	);

	foreach ( $looq_option_names as $looq_option_name ) {
		delete_transient( substr( $looq_option_name, strlen( '_transient_' ) ) );
	}
}
