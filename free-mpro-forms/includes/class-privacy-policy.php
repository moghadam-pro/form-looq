<?php

namespace FreeMPROForms;

defined( 'ABSPATH' ) || exit;

final class Privacy_Policy {
	public static function init(): void {
		add_action( 'admin_init', array( self::class, 'add_content' ) );
	}

	public static function add_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content  = '<p>' . esc_html__( 'When visitors submit a form created with Free MPRO Forms, the submitted values are stored in this website’s WordPress database. The specific personal data collected depends on the fields configured by the website administrator.', 'free-mpro-forms' ) . '</p>';
		$content .= '<p>' . esc_html__( 'The core plugin does not send submissions to the plugin author, does not require a cloud account, and does not enable analytics or telemetry. Optional integrations added in future versions may process data through another service and should be disclosed separately when enabled.', 'free-mpro-forms' ) . '</p>';
		$content .= '<p>' . esc_html__( 'If a submission fails validation, sanitized field values may be stored temporarily in this WordPress installation for up to five minutes so the visitor can correct the form. The browser receives an opaque token rather than the submitted values.', 'free-mpro-forms' ) . '</p>';
		$content .= '<p>' . esc_html__( 'Website administrators can choose a submission-retention period, use WordPress personal-data export and erasure tools for matching email addresses, delete submissions manually, and choose whether plugin data should be permanently removed during uninstall.', 'free-mpro-forms' ) . '</p>';

		wp_add_privacy_policy_content(
			__( 'Free MPRO Forms', 'free-mpro-forms' ),
			wp_kses_post( $content )
		);
	}
}
