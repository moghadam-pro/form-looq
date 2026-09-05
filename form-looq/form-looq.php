<?php
/**
 * Plugin Name: Form LOOQ
 * Description: Privacy-first WordPress forms with local submissions, a drag-and-drop builder, and first-class RTL/LTR support.
 * Version: 0.4.0
 * Author: Sayid Moghadam
 * Author URI: https://sayid.ir
 * Plugin URI: https://formlooq.ir
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: form-looq
 * Domain Path: /languages
 *
 * @package FormLooq
 */

defined( 'ABSPATH' ) || exit;

define( 'FORM_LOOQ_VERSION', '0.4.0' );
define( 'FORM_LOOQ_FILE', __FILE__ );
define( 'FORM_LOOQ_DIR', plugin_dir_path( __FILE__ ) );

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
	) as $looq_class
) {
	require_once FORM_LOOQ_DIR . 'includes/' . $looq_class . '.php';
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
		) as $looq_admin_class
	) {
		require_once FORM_LOOQ_DIR . 'includes/admin/' . $looq_admin_class . '.php';
	}
}

/**
 * Boot every subsystem once WordPress has loaded its own.
 */
function form_looq_bootstrap(): void {
	// Self-heals the schema after a manual file update or a multisite upgrade.
	\FormLooq\Install::maybe_upgrade();
	\FormLooq\Settings::init();
	\FormLooq\Rate_Limiter::init();
	\FormLooq\Frontend_Form::init();
	\FormLooq\Privacy_Manager::init();
	\FormLooq\Privacy_Policy::init();
	\FormLooq\Dashboard_Widget::init();
	\FormLooq\Elementor::init();

	if ( is_admin() ) {
		\FormLooq\Admin\Admin::init();
		\FormLooq\Admin\Onboarding::init();
		\FormLooq\Admin\Exporter::init();
		\FormLooq\Admin\Page_Forms::init();
		\FormLooq\Admin\Page_New_Form::init();
		\FormLooq\Admin\Page_Builder::init();
		\FormLooq\Admin\Page_Inbox::init();
		\FormLooq\Admin\Page_Entry::init();
		\FormLooq\Admin\Page_Settings::init();
	}
}
add_action( 'plugins_loaded', 'form_looq_bootstrap' );

/**
 * Load the bundled Persian translation and any site-provided override.
 */
function form_looq_load_textdomain(): void {
	load_plugin_textdomain( 'form-looq', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'form_looq_load_textdomain' );

/**
 * Store a submission programmatically.
 *
 * @param array<string, mixed> $data Field name to value map.
 */
function form_looq_store_submission( int $form_id, array $data ): int {
	return \FormLooq\Entry_Repository::create( $form_id, $data );
}

register_activation_hook( __FILE__, array( \FormLooq\Install::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \FormLooq\Install::class, 'deactivate' ) );
