<?php
/**
 * WordPress integration checks for privacy, retention, and uninstall behavior.
 */

use FreeMPROForms\Privacy_Manager;
use FreeMPROForms\Settings;
use FreeMPROForms\Submission_Manager;

function fmpf_test_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function fmpf_test_create_form(): int {
	$form_id = wp_insert_post(
		array(
		'post_type'   => 'fmpf_form',
		'post_status' => 'publish',
		'post_title'  => 'Integration Test Form',
		'post_content' => '',
		'post_excerpt' => '',
		'post_author' => 1,
		'ping_status' => 'closed',
		'comment_status' => 'closed',
		'meta_input'  => array(
			'_fmpf_fields' => "email|email|Email|required||\ntext|name|Name|required||",
			'_fmpf_submission_type' => 'integration-test',
		),
	),
	true
	);

	fmpf_test_assert( ! is_wp_error( $form_id ), 'Could not create the integration-test form.' );
	return (int) $form_id;
}

function fmpf_test_create_submission( int $form_id, string $email, string $name ): int {
	$submission_id = Submission_Manager::store(
		'integration-test',
		'Integration submission',
		array(
			'email' => $email,
			'name'  => $name,
		),
		$form_id
	);

	fmpf_test_assert( $submission_id > 0, 'Could not create an integration-test submission.' );
	return $submission_id;
}

$form_id       = fmpf_test_create_form();
$submission_id = fmpf_test_create_submission( $form_id, 'privacy@example.com', 'Privacy Test' );

$export = Privacy_Manager::export_personal_data( 'privacy@example.com', 1 );
fmpf_test_assert( ! empty( $export['data'] ), 'Privacy exporter did not return the matching submission.' );
fmpf_test_assert( true === $export['done'], 'Privacy exporter did not complete its single-page result.' );

$erasure = Privacy_Manager::erase_personal_data( 'privacy@example.com', 1 );
fmpf_test_assert( true === $erasure['items_removed'], 'Privacy eraser did not report removal.' );
fmpf_test_assert( array() === get_post_meta( $submission_id, '_fmpf_data', true ), 'Privacy eraser did not clear submitted values.' );
fmpf_test_assert( 'erased' === get_post_meta( $submission_id, '_fmpf_status', true ), 'Privacy eraser did not mark the retained shell as erased.' );

$old_submission   = fmpf_test_create_submission( $form_id, 'old@example.com', 'Old Test' );
$fresh_submission = fmpf_test_create_submission( $form_id, 'fresh@example.com', 'Fresh Test' );
$old_date         = gmdate( 'Y-m-d H:i:s', time() - ( 40 * DAY_IN_SECONDS ) );

wp_update_post(
	array(
		'ID'            => $old_submission,
		'post_date'     => get_date_from_gmt( $old_date ),
		'post_date_gmt' => $old_date,
	)
);

update_option( Settings::OPTION_RETENTION_DAYS, 30 );
$deleted = Settings::cleanup_expired_submissions();

fmpf_test_assert( $deleted >= 1, 'Retention cleanup did not report deleting the expired submission.' );
fmpf_test_assert( null === get_post( $old_submission ), 'Retention cleanup did not delete the expired submission.' );
fmpf_test_assert( null !== get_post( $fresh_submission ), 'Retention cleanup deleted a fresh submission.' );

$preserved_form       = fmpf_test_create_form();
$preserved_submission = fmpf_test_create_submission( $preserved_form, 'preserve@example.com', 'Preserve Test' );
update_option( Settings::OPTION_DELETE_ON_UNINSTALL, 0 );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	define( 'WP_UNINSTALL_PLUGIN', 'free-mpro-forms/free-mpro-forms.php' );
}

include WP_PLUGIN_DIR . '/free-mpro-forms/uninstall.php';

fmpf_test_assert( null !== get_post( $preserved_form ), 'Default uninstall behavior deleted a form.' );
fmpf_test_assert( null !== get_post( $preserved_submission ), 'Default uninstall behavior deleted a submission.' );

set_transient( 'fmpf_state_integration_test', array( 'temporary' => true ), HOUR_IN_SECONDS );
update_option( Settings::OPTION_DELETE_ON_UNINSTALL, 1 );
include WP_PLUGIN_DIR . '/free-mpro-forms/uninstall.php';

$remaining_forms = get_posts(
	array(
		'post_type'   => 'fmpf_form',
		'post_status' => 'any',
		'numberposts' => 1,
		'fields'      => 'ids',
	)
);
$remaining_submissions = get_posts(
	array(
		'post_type'   => 'fmpf_submission',
		'post_status' => 'any',
		'numberposts' => 1,
		'fields'      => 'ids',
	)
);

fmpf_test_assert( array() === $remaining_forms, 'Opt-in uninstall cleanup left form records behind.' );
fmpf_test_assert( array() === $remaining_submissions, 'Opt-in uninstall cleanup left submission records behind.' );
fmpf_test_assert( false === get_option( Settings::OPTION_RETENTION_DAYS, false ), 'Opt-in uninstall cleanup left the retention option behind.' );
fmpf_test_assert( false === get_option( Settings::OPTION_DELETE_ON_UNINSTALL, false ), 'Opt-in uninstall cleanup left its delete-data option behind.' );
fmpf_test_assert( false === get_transient( 'fmpf_state_integration_test' ), 'Opt-in uninstall cleanup left temporary form state behind.' );

WP_CLI::success( 'Privacy, retention, and uninstall integration checks passed.' );
