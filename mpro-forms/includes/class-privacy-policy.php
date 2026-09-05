<?php

namespace MPROForms;

defined( 'ABSPATH' ) || exit;

final class Privacy_Policy {
	public static function init(): void {
		add_action( 'admin_init', array( self::class, 'add_content' ) );
	}

	public static function add_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content  = '<p>' . esc_html__( 'When visitors submit a form created with MPRO Forms, the submitted values are stored in this website’s WordPress database. The specific personal data collected depends on the fields configured by the website administrator.', 'mpro-forms' ) . '</p>';
		$content .= '<p>' . esc_html__( 'Alongside the submitted values, each entry also stores the submission time, a salted hash of the visitor’s IP address (only when the form owner enables it), the referring page address, the browser’s user agent string, and — when the visitor was signed in — their WordPress user account. A staff member reviewing the entry may also add a private note.', 'mpro-forms' ) . '</p>';
		$content .= '<p>' . esc_html__( 'The plugin does not send submissions anywhere else, does not require a cloud account, and does not enable analytics or telemetry.', 'mpro-forms' ) . '</p>';
		$content .= '<p>' . esc_html__( 'If a submission fails validation, sanitized field values may be stored temporarily in this WordPress installation for up to five minutes so the visitor can correct the form. The browser receives an opaque token rather than the submitted values.', 'mpro-forms' ) . '</p>';
		$content .= '<p>' . esc_html__( 'Website administrators can choose a submission-retention period, use WordPress personal-data export and erasure tools for matching email addresses, delete submissions manually, and choose whether plugin data should be permanently removed during uninstall.', 'mpro-forms' ) . '</p>';

		wp_add_privacy_policy_content(
			__( 'MPRO Forms', 'mpro-forms' ),
			wp_kses_post( $content )
		);
	}
}
