<?php
/**
 * Creates the browser-test fixtures: two forms and two pages that embed them.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

use FormLooq\Form_Repository;

/**
 * @param array<int, array<string, mixed>> $fields Field definitions.
 */
function looq_e2e_create_form( string $title, array $fields ): int {
	$form_id = Form_Repository::create(
		array(
			'title'  => $title,
			'status' => Form_Repository::STATUS_ACTIVE,
			'fields' => $fields,
		)
	);

	if ( $form_id <= 0 ) {
		throw new RuntimeException( 'Could not create the browser-test form: ' . $title );
	}

	return $form_id;
}

function looq_e2e_create_page( string $title, string $slug, string $content ): int {
	$page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
		),
		true
	);

	if ( is_wp_error( $page_id ) ) {
		throw new RuntimeException( $page_id->get_error_message() );
	}

	return (int) $page_id;
}

/**
 * @param array<int, string> $options Choice list.
 * @return array<string, mixed>
 */
function looq_e2e_field( string $type, string $name, string $label, bool $required = false, array $options = array(), string $help = '' ): array {
	return array(
		'type'        => $type,
		'name'        => $name,
		'label'       => $label,
		'required'    => $required,
		'options'     => $options,
		'help'        => $help,
		'placeholder' => '',
		'width'       => 'full',
	);
}

$english_fields = array(
	looq_e2e_field( 'section', '', 'Contact details', false, array(), 'Complete the form below.' ),
	looq_e2e_field( 'text', 'full_name', 'Full name', true, array(), 'Enter your first and last name.' ),
	looq_e2e_field( 'email', 'email', 'Email address', true, array(), 'We will only use this to reply.' ),
	looq_e2e_field( 'select', 'topic', 'Topic', true, array( 'Project enquiry', 'Support', 'Other' ), 'Choose one option.' ),
	looq_e2e_field( 'textarea', 'message', 'Message', true, array(), 'Tell us how we can help.' ),
	looq_e2e_field( 'checkbox', 'consent', 'Consent', true, array(), 'I agree to submit this information.' ),
);

$persian_fields = array(
	looq_e2e_field( 'section', '', 'اطلاعات تماس', false, array(), 'فرم زیر را تکمیل کنید.' ),
	looq_e2e_field( 'text', 'full_name', 'نام و نام خانوادگی', true, array(), 'نام کامل خود را وارد کنید.' ),
	looq_e2e_field( 'email', 'email', 'ایمیل', true, array(), 'برای پاسخ‌گویی استفاده می‌شود.' ),
	looq_e2e_field( 'radio', 'contact_method', 'روش تماس', true, array( 'ایمیل', 'تلفن' ), 'یک گزینه را انتخاب کنید.' ),
	looq_e2e_field( 'textarea', 'message', 'پیام', true, array(), 'توضیحات خود را بنویسید.' ),
	looq_e2e_field( 'checkbox', 'consent', 'تأیید', true, array(), 'با ارسال اطلاعات موافقم.' ),
);

$english_form = looq_e2e_create_form( 'English browser form', $english_fields );
$persian_form = looq_e2e_create_form( 'Persian browser form', $persian_fields );

looq_e2e_create_page(
	'English Form',
	'english-form',
	sprintf( '[looq_form id="%d" dir="ltr" button="Send message"]', $english_form )
);

looq_e2e_create_page(
	'فرم فارسی',
	'persian-form',
	sprintf( '[looq_form id="%d" dir="rtl" button="ارسال پیام" yes="موافقم"]', $persian_form )
);

update_option( 'permalink_structure', '/%postname%/' );
flush_rewrite_rules();

echo "Browser fixtures created.\n";
