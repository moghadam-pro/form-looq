<?php
/**
 * Smoke test run against the packaged plugin ZIP.
 *
 * Confirms the packaged version matches the runtime, the schema is created, and
 * a form and entry survive a round trip through the dedicated tables.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

use MPROForms\DB;
use MPROForms\Entry_Repository;
use MPROForms\Form_Repository;

$plugin_file = WP_PLUGIN_DIR . '/mpro-forms/mpro-forms.php';
if ( ! is_file( $plugin_file ) ) {
	throw new RuntimeException( 'The packaged plugin main file is missing.' );
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
$plugin_data = get_plugin_data( $plugin_file, false, false );
$version     = (string) ( $plugin_data['Version'] ?? '' );

if ( MPRO_FORMS_VERSION !== $version ) {
	throw new RuntimeException( 'The runtime version does not match the packaged plugin header.' );
}

foreach ( DB::tables() as $table_key => $table_name ) {
	if ( ! DB::table_exists( $table_name ) ) {
		throw new RuntimeException( sprintf( 'The packaged plugin did not create the %s table.', $table_key ) );
	}
}

$form_id = Form_Repository::create(
	array(
		'title'  => 'Release Candidate Form',
		'status' => Form_Repository::STATUS_ACTIVE,
		'fields' => array(
			array(
				'type'     => 'text',
				'name'     => 'full_name',
				'label'    => 'Full name',
				'required' => true,
			),
			array(
				'type'     => 'email',
				'name'     => 'email',
				'label'    => 'Email',
				'required' => true,
			),
			array(
				'type'     => 'textarea',
				'name'     => 'message',
				'label'    => 'Message',
				'required' => true,
			),
		),
	)
);

if ( $form_id <= 0 ) {
	throw new RuntimeException( 'The packaged plugin could not create a form.' );
}

$stored_form = Form_Repository::get( $form_id );

if ( ! is_array( $stored_form ) || 3 !== count( $stored_form['fields'] ) ) {
	throw new RuntimeException( 'The packaged plugin did not persist the form field definitions.' );
}

$entry_id = mpro_forms_store_submission(
	$form_id,
	array(
		'full_name' => 'Release Candidate Tester',
		'email'     => 'rc@example.com',
		'message'   => 'Packaged plugin verification.',
	)
);

if ( ! $entry_id ) {
	throw new RuntimeException( 'The packaged plugin could not store an entry.' );
}

$stored_entry = Entry_Repository::get( $entry_id );

if (
	! is_array( $stored_entry ) ||
	'rc@example.com' !== ( $stored_entry['data']['email'] ?? '' ) ||
	$form_id !== (int) $stored_entry['form_id'] ||
	Entry_Repository::STATUS_UNREAD !== $stored_entry['status']
) {
	throw new RuntimeException( 'The packaged plugin stored incomplete entry data.' );
}

$recounted = Form_Repository::get( $form_id );

if ( 1 !== (int) $recounted['entries_count'] ) {
	throw new RuntimeException( 'The packaged plugin did not keep the form entry count in step.' );
}

$rendered = do_shortcode( sprintf( '[mpro_form id="%d"]', $form_id ) );

if ( false === strpos( $rendered, 'mpro-form' ) ) {
	throw new RuntimeException( 'The packaged plugin did not render the form shortcode.' );
}

$result = array(
	'version'  => $version,
	'form_id'  => $form_id,
	'entry_id' => $entry_id,
);

echo wp_json_encode( $result, JSON_UNESCAPED_SLASHES ) . PHP_EOL;
