<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/free-mpro-forms/includes/class-field-validator.php';
require_once dirname( __DIR__ ) . '/free-mpro-forms/includes/class-submission-validator.php';

use FreeMPROForms\Field_Validator;
use FreeMPROForms\Submission_Validator;

$definition = implode(
	"\n",
	array(
		'section||Contact details|||Tell us how to reach you.',
		'email|email|Email address|required||Used only to reply.',
		'tel|phone|Phone number|||',
		'select|topic|Topic|required|Support,Sales|',
		'scale|rating|Rating|required||',
		'text|action|Reserved name|||',
		'text|email|Duplicate name|||',
	)
);

$fields = Field_Validator::parse_definition( $definition );

fmpf_assert_same( 5, count( $fields ), 'Parser should keep valid fields and reject reserved or duplicate names.' );
fmpf_assert_same( 'section', $fields[0]['type'], 'First parsed field should be the section.' );
fmpf_assert_same( 'email', $fields[1]['name'], 'Email field name should be preserved.' );
fmpf_assert_same( array( 'Support', 'Sales' ), $fields[3]['options'], 'Select options should be parsed exactly.' );
fmpf_assert_same( array( '1', '2', '3', '4', '5' ), $fields[4]['options'], 'Scale should receive default options.' );
fmpf_assert_same( 'Tell us how to reach you.', $fields[0]['help'], 'Unicode-safe help text should be preserved.' );

$many_fields = array();
for ( $index = 1; $index <= 55; ++$index ) {
	$many_fields[] = 'text|field_' . $index . '|Field ' . $index . '|||';
}
fmpf_assert_same( 50, count( Field_Validator::parse_definition( implode( "\n", $many_fields ) ) ), 'Parser should enforce the 50-field limit.' );

$valid_request = array(
	'action'          => 'fmpf_submit',
	'form_id'         => '42',
	'fmpf_nonce'      => 'nonce',
	'fmpf_started_at' => '123456',
	'website'         => '',
	'email'           => 'sayid@example.com',
	'phone'           => '+98 912 123 4567',
	'topic'           => 'Support',
	'rating'          => '5',
);

$valid = Submission_Validator::validate( $fields, $valid_request );
fmpf_assert_true( $valid['valid'], 'A valid request should pass.' );
fmpf_assert_same( 'sayid@example.com', $valid['data']['email'], 'Validated email should be stored.' );
fmpf_assert_same( 'Support', $valid['data']['topic'], 'Configured option should be stored.' );

$invalid_email            = $valid_request;
$invalid_email['email']   = 'not-an-email';
$invalid_email_validation = Submission_Validator::validate( $fields, $invalid_email );
fmpf_assert_true( ! $invalid_email_validation['valid'], 'Invalid email should fail.' );
fmpf_assert_true( isset( $invalid_email_validation['errors']['email'] ), 'Invalid email should produce a field-specific error.' );

$forged_choice          = $valid_request;
$forged_choice['topic'] = 'Injected option';
$forged_validation      = Submission_Validator::validate( $fields, $forged_choice );
fmpf_assert_true( ! $forged_validation['valid'], 'A forged select option should fail.' );
fmpf_assert_true( isset( $forged_validation['errors']['topic'] ), 'Forged option should produce a field-specific error.' );

$unexpected          = $valid_request;
$unexpected['admin'] = '1';
$unexpected_result   = Submission_Validator::validate( $fields, $unexpected );
fmpf_assert_true( ! $unexpected_result['valid'], 'Unexpected request keys should fail.' );
fmpf_assert_true( isset( $unexpected_result['errors']['_form'] ), 'Unexpected request keys should produce a form-level error.' );

$checkbox_fields = Field_Validator::parse_definition( 'checkbox|consent|I agree|required||' );
$checkbox_result = Submission_Validator::validate( $checkbox_fields, array() );
fmpf_assert_true( ! $checkbox_result['valid'], 'Missing required checkbox should fail.' );
fmpf_assert_true( isset( $checkbox_result['errors']['consent'] ), 'Required checkbox should produce a field-specific error.' );

$array_value          = $valid_request;
$array_value['email'] = array( 'sayid@example.com' );
$array_result         = Submission_Validator::validate( $fields, $array_value );
fmpf_assert_true( ! $array_result['valid'], 'Array input for a scalar field should fail.' );

$textarea_fields = Field_Validator::parse_definition( 'textarea|message|Message|required||' );
$long_text       = str_repeat( 'a', 5001 );
$textarea_result = Submission_Validator::validate( $textarea_fields, array( 'message' => $long_text ) );
fmpf_assert_true( ! $textarea_result['valid'], 'Textarea values longer than 5,000 characters should fail.' );
fmpf_assert_same( 5000, strlen( $textarea_result['values']['message'] ), 'Displayed textarea value should be safely truncated.' );

fwrite( STDOUT, "Validator tests passed.\n" );
