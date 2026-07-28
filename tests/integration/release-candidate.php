<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$plugin_file = WP_PLUGIN_DIR . '/free-mpro-forms/free-mpro-forms.php';
if ( ! is_file( $plugin_file ) ) {
	throw new RuntimeException( 'The packaged plugin main file is missing.' );
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
$plugin_data = get_plugin_data( $plugin_file, false, false );
$version     = (string) ( $plugin_data['Version'] ?? '' );

if ( FREE_MPRO_FORMS_VERSION !== $version ) {
	throw new RuntimeException( 'The runtime version does not match the packaged plugin header.' );
}

$form_id = wp_insert_post(
	array(
		'post_type'   => 'fmpf_form',
		'post_status' => 'publish',
		'post_title'  => 'Release Candidate Form',
	),
	true
);

if ( is_wp_error( $form_id ) ) {
	throw new RuntimeException( $form_id->get_error_message() );
}

update_post_meta(
	$form_id,
	'_fmpf_fields',
	implode(
		"\n",
		array(
			'text|full_name|Full name|required||',
			'email|email|Email|required||',
			'textarea|message|Message|required||',
		)
	)
);

$submission_id = free_mpro_forms_store_submission(
	'release-candidate',
	'Release Candidate Submission',
	array(
		'full_name' => 'Release Candidate Tester',
		'email'     => 'rc@example.com',
		'message'   => 'Packaged plugin verification.',
	),
	(int) $form_id
);

if ( ! $submission_id ) {
	throw new RuntimeException( 'The packaged plugin could not store a submission.' );
}

$stored_data = get_post_meta( $submission_id, '_fmpf_data', true );
if (
	! is_array( $stored_data ) ||
	'rc@example.com' !== ( $stored_data['email'] ?? '' ) ||
	(int) $form_id !== (int) get_post_meta( $submission_id, '_fmpf_form_id', true )
) {
	throw new RuntimeException( 'The packaged plugin stored incomplete submission data.' );
}

$result = array(
	'version'       => $version,
	'form_id'       => (int) $form_id,
	'submission_id' => (int) $submission_id,
);

echo wp_json_encode( $result, JSON_UNESCAPED_SLASHES ) . PHP_EOL;
