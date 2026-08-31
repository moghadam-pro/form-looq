<?php
/**
 * Plugin Name: Free MPRO Forms
 * Description: Free, privacy-first WordPress forms with local submissions, a drag-and-drop builder, and first-class RTL/LTR support.
 * Version: 0.2.0
 * Author: Sayid Moghadam
 * Author URI: https://sayid.ir
 * Plugin URI: https://sayid.ir/free-forms-plugin
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: free-mpro-forms
 * Domain Path: /languages
 *
 * @package FreeMPROForms
 */

defined( 'ABSPATH' ) || exit;

define( 'FREE_MPRO_FORMS_VERSION', '0.2.0' );
define( 'FREE_MPRO_FORMS_FILE', __FILE__ );
define( 'FREE_MPRO_FORMS_DIR', plugin_dir_path( __FILE__ ) );

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
		'class-remote-content',
		'class-dashboard-widget',
		'class-elementor',
	) as $fmpf_class
) {
	require_once FREE_MPRO_FORMS_DIR . 'includes/' . $fmpf_class . '.php';
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
			'class-page-addons',
			'class-page-status',
			'class-page-help',
		) as $fmpf_admin_class
	) {
		require_once FREE_MPRO_FORMS_DIR . 'includes/admin/' . $fmpf_admin_class . '.php';
	}
}

/**
 * Boot every subsystem once WordPress has loaded its own.
 */
function free_mpro_forms_bootstrap(): void {
	// Self-heals the schema after a manual file update or a multisite upgrade.
	\FreeMPROForms\Install::maybe_upgrade();
	\FreeMPROForms\Settings::init();
	\FreeMPROForms\Rate_Limiter::init();
	\FreeMPROForms\Frontend_Form::init();
	\FreeMPROForms\Privacy_Manager::init();
	\FreeMPROForms\Privacy_Policy::init();
	\FreeMPROForms\Dashboard_Widget::init();
	\FreeMPROForms\Elementor::init();

	if ( is_admin() ) {
		\FreeMPROForms\Admin\Admin::init();
		\FreeMPROForms\Admin\Onboarding::init();
		\FreeMPROForms\Admin\Exporter::init();
		\FreeMPROForms\Admin\Page_Forms::init();
		\FreeMPROForms\Admin\Page_New_Form::init();
		\FreeMPROForms\Admin\Page_Builder::init();
		\FreeMPROForms\Admin\Page_Inbox::init();
		\FreeMPROForms\Admin\Page_Entry::init();
		\FreeMPROForms\Admin\Page_Settings::init();
	}
}
add_action( 'plugins_loaded', 'free_mpro_forms_bootstrap' );

/**
 * Load the bundled Persian translation and any site-provided override.
 */
function free_mpro_forms_load_textdomain(): void {
	load_plugin_textdomain( 'free-mpro-forms', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'free_mpro_forms_load_textdomain' );

/**
 * Store a submission programmatically.
 *
 * @param array<string, mixed> $data Field name to value map.
 */
function free_mpro_forms_store_submission( int $form_id, array $data ): int {
	return \FreeMPROForms\Entry_Repository::create( $form_id, $data );
}

register_activation_hook( __FILE__, array( \FreeMPROForms\Install::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \FreeMPROForms\Install::class, 'deactivate' ) );
