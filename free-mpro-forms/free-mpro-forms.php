<?php
/**
 * Plugin Name: Free MPRO Forms
 * Description: Free, privacy-first WordPress forms with local submissions and first-class RTL/LTR support.
 * Version: 0.1.0
 * Author: Sayid Moghadam
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: free-mpro-forms
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'FREE_MPRO_FORMS_VERSION', '0.1.0' );
define( 'FREE_MPRO_FORMS_FILE', __FILE__ );
define( 'FREE_MPRO_FORMS_DIR', plugin_dir_path( __FILE__ ) );

require_once FREE_MPRO_FORMS_DIR . 'includes/class-field-validator.php';
require_once FREE_MPRO_FORMS_DIR . 'includes/class-submission-validator.php';
require_once FREE_MPRO_FORMS_DIR . 'includes/class-submission-state.php';
require_once FREE_MPRO_FORMS_DIR . 'includes/class-rate-limiter.php';
require_once FREE_MPRO_FORMS_DIR . 'includes/class-form-manager.php';
require_once FREE_MPRO_FORMS_DIR . 'includes/class-submission-manager.php';
require_once FREE_MPRO_FORMS_DIR . 'includes/class-frontend-form.php';
require_once FREE_MPRO_FORMS_DIR . 'includes/class-privacy-manager.php';
require_once FREE_MPRO_FORMS_DIR . 'includes/class-privacy-policy.php';
require_once FREE_MPRO_FORMS_DIR . 'includes/class-settings.php';

\FreeMPROForms\Form_Manager::init();
\FreeMPROForms\Submission_Manager::init();
\FreeMPROForms\Rate_Limiter::init();
\FreeMPROForms\Frontend_Form::init();
\FreeMPROForms\Privacy_Manager::init();
\FreeMPROForms\Privacy_Policy::init();
\FreeMPROForms\Settings::init();

function free_mpro_forms_store_submission( string $type, string $title, array $data, int $form_id = 0 ): int {
	return \FreeMPROForms\Submission_Manager::store( $type, $title, $data, $form_id );
}

register_activation_hook(
	__FILE__,
	static function (): void {
		\FreeMPROForms\Form_Manager::register_post_type();
		\FreeMPROForms\Submission_Manager::register_post_type();
		\FreeMPROForms\Settings::activate();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		\FreeMPROForms\Settings::deactivate();
		flush_rewrite_rules();
	}
);
