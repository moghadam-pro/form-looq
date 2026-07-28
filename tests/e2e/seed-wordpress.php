<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

function fmpf_e2e_create_form( string $title, string $definition ): int {
	$form_id = wp_insert_post(
		array(
			'post_type'   => 'fmpf_form',
			'post_status' => 'publish',
			'post_title'  => $title,
		),
		true
	);

	if ( is_wp_error( $form_id ) ) {
		throw new RuntimeException( $form_id->get_error_message() );
	}

	update_post_meta( $form_id, '_fmpf_fields', $definition );
	update_post_meta( $form_id, '_fmpf_submission_type', 'e2e' );

	return (int) $form_id;
}

function fmpf_e2e_create_page( string $title, string $slug, string $content ): int {
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

$english_definition = implode(
	"\n",
	array(
		'section||Contact details|||Complete the form below.',
		'text|full_name|Full name|required||Enter your first and last name.',
		'email|email|Email address|required||We will only use this to reply.',
		'select|topic|Topic|required|Project enquiry,Support,Other|Choose one option.',
		'textarea|message|Message|required||Tell us how we can help.',
		'checkbox|consent|Consent|required||I agree to submit this information.',
	)
);

$persian_definition = implode(
	"\n",
	array(
		'section||اطلاعات تماس|||فرم زیر را تکمیل کنید.',
		'text|full_name|نام و نام خانوادگی|required||نام کامل خود را وارد کنید.',
		'email|email|ایمیل|required||برای پاسخ‌گویی استفاده می‌شود.',
		'radio|contact_method|روش تماس|required|ایمیل,تلفن|یک گزینه را انتخاب کنید.',
		'textarea|message|پیام|required||توضیحات خود را بنویسید.',
		'checkbox|consent|تأیید|required||با ارسال اطلاعات موافقم.',
	)
);

$english_form = fmpf_e2e_create_form( 'English browser form', $english_definition );
$persian_form = fmpf_e2e_create_form( 'Persian browser form', $persian_definition );

fmpf_e2e_create_page(
	'English Form',
	'english-form',
	sprintf( '[free_mpro_form id="%d" dir="ltr" button="Send message"]', $english_form )
);

fmpf_e2e_create_page(
	'فرم فارسی',
	'persian-form',
	sprintf( '[free_mpro_form id="%d" dir="rtl" button="ارسال پیام" yes="موافقم"]', $persian_form )
);

update_option( 'permalink_structure', '/%postname%/' );
flush_rewrite_rules();

echo "Browser fixtures created.\n";
