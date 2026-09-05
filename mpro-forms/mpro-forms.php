<?php
/**
 * Plugin Name: MPRO Forms
 * Description: Privacy-first WordPress forms with local submissions, a drag-and-drop builder, and first-class RTL/LTR support.
 * Version: 0.3.3
 * Author: Sayid Moghadam
 * Author URI: https://sayid.ir
 * Plugin URI: https://sayid.ir/mpro-forms
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mpro-forms
 * Domain Path: /languages
 *
 * @package MPROForms
 */

defined( 'ABSPATH' ) || exit;

define( 'MPRO_FORMS_VERSION', '0.3.3' );
define( 'MPRO_FORMS_FILE', __FILE__ );
define( 'MPRO_FORMS_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Core classes are loaded eagerly: the whole set is small and every request
 * that touches a form needs most of it anyway.
 */
foreach (
	array(
		'class-plugin',
		'class-db',
		'class-install',
		'class-settings',
		'class-field-validator',
		'class-submission-validator',
		'class-submission-state',
		'class-form-repository',
		'class-entry-repository',
		'class-templates',
		'class-rate-limiter',
		'class-frontend-form',
		'class-privacy-manager',
		'class-privacy-policy',
		'class-dashboard-widget',
		'class-elementor',
	) as $mpro_class
) {
	require_once MPRO_FORMS_DIR . 'includes/' . $mpro_class . '.php';
}

if ( is_admin() ) {
	foreach (
		array(
			'class-admin',
			'class-onboarding',
			'class-exporter',
			'class-forms-list-table',
			'class-entries-list-table',
			'class-page-forms',
			'class-page-new-form',
			'class-page-builder',
			'class-page-inbox',
			'class-page-entry',
			'class-page-settings',
			'class-page-tools',
			'class-page-status',
			'class-page-help',
		) as $mpro_admin_class
	) {
		require_once MPRO_FORMS_DIR . 'includes/admin/' . $mpro_admin_class . '.php';
	}
}

/**
 * Boot every subsystem once WordPress has loaded its own.
 */
function mpro_forms_bootstrap(): void {
	// Self-heals the schema after a manual file update or a multisite upgrade.
	\MPROForms\Install::maybe_upgrade();
	\MPROForms\Settings::init();
	\MPROForms\Rate_Limiter::init();
	\MPROForms\Frontend_Form::init();
	\MPROForms\Privacy_Manager::init();
	\MPROForms\Privacy_Policy::init();
	\MPROForms\Dashboard_Widget::init();
	\MPROForms\Elementor::init();

	if ( is_admin() ) {
		\MPROForms\Admin\Admin::init();
		\MPROForms\Admin\Onboarding::init();
		\MPROForms\Admin\Exporter::init();
		\MPROForms\Admin\Page_Forms::init();
		\MPROForms\Admin\Page_New_Form::init();
		\MPROForms\Admin\Page_Builder::init();
		\MPROForms\Admin\Page_Inbox::init();
		\MPROForms\Admin\Page_Entry::init();
		\MPROForms\Admin\Page_Settings::init();
	}
}
add_action( 'plugins_loaded', 'mpro_forms_bootstrap' );

/**
 * Load the bundled Persian translation and any site-provided override.
 */
function mpro_forms_load_textdomain(): void {
	load_plugin_textdomain( 'mpro-forms', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'mpro_forms_load_textdomain' );

/**
 * Store a submission programmatically.
 *
 * @param array<string, mixed> $data Field name to value map.
 */
function mpro_forms_store_submission( int $form_id, array $data ): int {
	return \MPROForms\Entry_Repository::create( $form_id, $data );
}

register_activation_hook( __FILE__, array( \MPROForms\Install::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \MPROForms\Install::class, 'deactivate' ) );
