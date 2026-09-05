<?php
/**
 * WordPress integration checks for submission rate limiting.
 */

use FormLooq\Form_Repository;
use FormLooq\Rate_Limiter;

function form_looq_rate_test_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$form_id = Form_Repository::create(
	array(
		'title'  => 'Rate Limit Test Form',
		'status' => Form_Repository::STATUS_ACTIVE,
		'fields' => array(
			array(
				'type'     => 'text',
				'name'     => 'full_name',
				'label'    => 'Full name',
				'required' => true,
			),
		),
	)
);

form_looq_rate_test_assert( $form_id > 0, 'Could not create the rate-limit test form.' );

add_filter(
	'form_looq_rate_limit_windows',
	static function (): array {
		return array(
			'test' => array(
				'limit'  => 2,
				'window' => 60,
			),
		);
	}
);

$identity = 'integration-test-client';
$first    = Rate_Limiter::check_and_record( $form_id, $identity );
$second   = Rate_Limiter::check_and_record( $form_id, $identity );
$third    = Rate_Limiter::check_and_record( $form_id, $identity );

form_looq_rate_test_assert( true === $first['allowed'], 'The first submission attempt was unexpectedly blocked.' );
form_looq_rate_test_assert( true === $second['allowed'], 'The second submission attempt was unexpectedly blocked.' );
form_looq_rate_test_assert( false === $third['allowed'], 'The configured rate limit did not block the third attempt.' );
form_looq_rate_test_assert( $third['retry_after'] > 0, 'The blocked response did not include a retry interval.' );

WP_CLI::success( 'Submission rate-limit integration checks passed.' );
