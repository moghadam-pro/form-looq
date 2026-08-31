<?php
/**
 * Elementor integration: a widget that drops a form anywhere on the canvas.
 *
 * The widget deliberately renders the plain shortcode output so Elementor's own
 * spacing, typography, and background controls govern its appearance.
 *
 * @package FreeMPROForms
 */

namespace FreeMPROForms;

defined( 'ABSPATH' ) || exit;

final class Elementor {
	public static function init(): void {
		add_action( 'elementor/widgets/register', array( self::class, 'register_widget' ) );
		add_action( 'elementor/elements/categories_registered', array( self::class, 'register_category' ) );
	}

	public static function enabled(): bool {
		return defined( 'ELEMENTOR_VERSION' ) && (bool) Settings::get( 'elementor_widget', true );
	}

	/**
	 * @param mixed $manager Elementor elements manager.
	 */
	public static function register_category( $manager ): void {
		if ( ! self::enabled() || ! is_object( $manager ) || ! method_exists( $manager, 'add_category' ) ) {
			return;
		}

		$manager->add_category(
			'free-mpro-forms',
			array(
				'title' => Plugin::menu_label(),
				'icon'  => 'eicon-form-horizontal',
			)
		);
	}

	/**
	 * @param mixed $widgets_manager Elementor widgets manager.
	 */
	public static function register_widget( $widgets_manager ): void {
		if ( ! self::enabled() || ! is_object( $widgets_manager ) || ! method_exists( $widgets_manager, 'register' ) ) {
			return;
		}

		require_once FREE_MPRO_FORMS_DIR . 'includes/class-elementor-widget.php';

		$widgets_manager->register( new Elementor_Widget() );
	}

	/**
	 * Form choices for the widget's select control.
	 *
	 * @return array<string, string>
	 */
	public static function form_choices(): array {
		$choices = array( '' => __( '— Select a form —', 'free-mpro-forms' ) );

		foreach ( Form_Repository::query( array( 'per_page' => 200 ) ) as $form ) {
			$choices[ (string) $form['id'] ] = (string) $form['title'];
		}

		return $choices;
	}
}
