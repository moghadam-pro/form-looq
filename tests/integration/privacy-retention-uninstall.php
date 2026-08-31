<?php
/**
 * WordPress integration checks for privacy, retention, and uninstall behavior.
 *
 * Runs against the dedicated plugin tables introduced in 0.2.0.
 */

use FreeMPROForms\DB;
use FreeMPROForms\Entry_Repository;
use FreeMPROForms\Form_Repository;
use FreeMPROForms\Privacy_Manager;
use FreeMPROForms\Settings;

function fmpf_test_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function fmpf_test_create_form(): int {
	$form_id = Form_Repository::create(
		array(
			'title'  => 'Integration Test Form',
			'status' => Form_Repository::STATUS_ACTIVE,
			'fields' => array(
				array(
					'type'     => 'email',
					'name'     => 'email',
					'label'    => 'Email',
					'required' => true,
				),
				array(
					'type'     => 'text',
					'name'     => 'name',
					'label'    => 'Name',
					'required' => true,
				),
			),
		)
	);

	fmpf_test_assert( $form_id > 0, 'Could not create the integration-test form.' );

	return $form_id;
}

function fmpf_test_create_entry( int $form_id, string $email, string $name ): int {
	$entry_id = Entry_Repository::create(
		$form_id,
		array(
			'email' => $email,
			'name'  => $name,
		),
		array( 'ip' => '203.0.113.10' )
	);

	fmpf_test_assert( $entry_id > 0, 'Could not create an integration-test entry.' );

	return $entry_id;
}

/*
 * Tables exist after activation.
 */
foreach ( DB::tables() as $fmpf_key => $fmpf_table ) {
	fmpf_test_assert( DB::table_exists( $fmpf_table ), sprintf( 'The %s table was not created.', $fmpf_key ) );
}

$form_id  = fmpf_test_create_form();
$entry_id = fmpf_test_create_entry( $form_id, 'privacy@example.com', 'Privacy Test' );

/*
 * The IP is stored as a salted hash, never in plain text.
 */
$stored_entry = Entry_Repository::get( $entry_id );
fmpf_test_assert( is_array( $stored_entry ), 'The stored entry could not be read back.' );
fmpf_test_assert( '' !== $stored_entry['ip_hash'], 'The entry did not record a hashed IP.' );
fmpf_test_assert( false === strpos( $stored_entry['ip_hash'], '203.0.113' ), 'The entry stored a raw IP address.' );

/*
 * The form's entry count is kept in step with its entries.
 */
$counted_form = Form_Repository::get( $form_id );
fmpf_test_assert( 1 === (int) $counted_form['entries_count'], 'The form entry count was not updated on insert.' );

/*
 * Privacy export and erasure.
 */
$export = Privacy_Manager::export_personal_data( 'privacy@example.com', 1 );
fmpf_test_assert( ! empty( $export['data'] ), 'Privacy exporter did not return the matching entry.' );
fmpf_test_assert( true === $export['done'], 'Privacy exporter did not complete its single-page result.' );

$erasure = Privacy_Manager::erase_personal_data( 'privacy@example.com', 1 );
fmpf_test_assert( true === $erasure['items_removed'], 'Privacy eraser did not report removal.' );

$erased = Entry_Repository::get( $entry_id );
fmpf_test_assert( array() === $erased['data'], 'Privacy eraser did not clear submitted values.' );
fmpf_test_assert( '' === $erased['ip_hash'], 'Privacy eraser did not clear the hashed IP.' );
fmpf_test_assert( '' === $erased['user_agent'], 'Privacy eraser did not clear the user agent.' );

/*
 * Retention deletes entries past the window and leaves fresh ones alone.
 */
global $wpdb;

$old_entry   = fmpf_test_create_entry( $form_id, 'old@example.com', 'Old Test' );
$fresh_entry = fmpf_test_create_entry( $form_id, 'fresh@example.com', 'Fresh Test' );

$wpdb->update(
	DB::entries_table(),
	array( 'created_at' => gmdate( 'Y-m-d H:i:s', time() - ( 40 * DAY_IN_SECONDS ) ) ),
	array( 'id' => $old_entry ),
	array( '%s' ),
	array( '%d' )
);

$deleted = Entry_Repository::purge_older_than( 30 );

fmpf_test_assert( $deleted >= 1, 'Retention cleanup did not report deleting the expired entry.' );
fmpf_test_assert( null === Entry_Repository::get( $old_entry ), 'Retention cleanup did not delete the expired entry.' );
fmpf_test_assert( null !== Entry_Repository::get( $fresh_entry ), 'Retention cleanup deleted a fresh entry.' );

/*
 * Deleting a form removes its entries and nothing else.
 */
$other_form  = fmpf_test_create_form();
$other_entry = fmpf_test_create_entry( $other_form, 'other@example.com', 'Other Test' );

Form_Repository::delete( $other_form );

fmpf_test_assert( null === Form_Repository::get( $other_form ), 'Deleting a form left the form row behind.' );
fmpf_test_assert( null === Entry_Repository::get( $other_entry ), 'Deleting a form left its entries behind.' );
fmpf_test_assert( null !== Entry_Repository::get( $fresh_entry ), 'Deleting a form removed another form\'s entries.' );

/*
 * The default uninstall keeps everything. This is the guarantee that lets a site
 * owner delete and reinstall the plugin without losing data.
 */
$preserved_form  = fmpf_test_create_form();
$preserved_entry = fmpf_test_create_entry( $preserved_form, 'preserve@example.com', 'Preserve Test' );

Settings::save( array( 'delete_data_on_uninstall' => false ) );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	define( 'WP_UNINSTALL_PLUGIN', 'free-mpro-forms/free-mpro-forms.php' );
}

include WP_PLUGIN_DIR . '/free-mpro-forms/uninstall.php';

fmpf_test_assert( DB::table_exists( DB::forms_table() ), 'Default uninstall dropped the forms table.' );
fmpf_test_assert( null !== Form_Repository::get( $preserved_form ), 'Default uninstall deleted a form.' );
fmpf_test_assert( null !== Entry_Repository::get( $preserved_entry ), 'Default uninstall deleted an entry.' );

/*
 * The opt-in uninstall removes the tables, the options, and the transients.
 */
set_transient( 'fmpf_state_integration_test', array( 'temporary' => true ), HOUR_IN_SECONDS );

Settings::save( array( 'delete_data_on_uninstall' => true ) );

include WP_PLUGIN_DIR . '/free-mpro-forms/uninstall.php';

fmpf_test_assert( ! DB::table_exists( DB::forms_table() ), 'Opt-in uninstall left the forms table behind.' );
fmpf_test_assert( ! DB::table_exists( DB::entries_table() ), 'Opt-in uninstall left the entries table behind.' );
fmpf_test_assert( false === get_option( Settings::OPTION, false ), 'Opt-in uninstall left the settings option behind.' );
fmpf_test_assert( false === get_option( 'fmpf_schema_version', false ), 'Opt-in uninstall left the schema version behind.' );
fmpf_test_assert( false === get_transient( 'fmpf_state_integration_test' ), 'Opt-in uninstall left temporary form state behind.' );

WP_CLI::success( 'Privacy, retention, and uninstall integration checks passed.' );
