<?php
/**
 * Plugin Name: Free MPRO Forms
 * Description: Free, privacy-first WordPress forms with local submissions and first-class RTL/LTR support.
 * Version: 0.1.0-dev
 * Author: Sayid Moghadam
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Update URI: https://github.com/moghadam-pro/free-forms-wp-plugin
 * Text Domain: free-mpro-forms
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'FREE_MPRO_FORMS_VERSION', '0.1.0-dev' );
define( 'FREE_MPRO_FORMS_FILE', __FILE__ );
define( 'FREE_MPRO_FORMS_DIR', plugin_dir_path( __FILE__ ) );

require_once FREE_MPRO_FORMS_DIR . 'includes/class-field-validator.php';
require_once FREE_MPRO_FORMS_DIR . 'includes/class-submission-validator.php';
require_once FREE_MPRO_FORMS_DIR . 'includes/class-submission-state.php';
require_once FREE_MPRO_FORMS_DIR . 'includes/class-form-manager.php';
require_once FREE_MPRO_FORMS_DIR . 'includes/class-submission-manager.php';
require_once FREE_MPRO_FORMS_DIR . 'includes/class-frontend-form.php';

\FreeMPROForms\Form_Manager::init();
\FreeMPROForms\Submission_Manager::init();
\FreeMPROForms\Frontend_Form::init();

function free_mpro_forms_store_submission( string $type, string $title, array $data, int $form_id = 0 ): int {
	return \FreeMPROForms\Submission_Manager::store( $type, $title, $data, $form_id );
}

register_activation_hook(
	__FILE__,
	static function (): void {
		\FreeMPROForms\Form_Manager::register_post_type();
		\FreeMPROForms\Submission_Manager::register_post_type();
		flush_rewrite_rules();
	}
);

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
