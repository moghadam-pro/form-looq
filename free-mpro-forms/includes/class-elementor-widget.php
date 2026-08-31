<?php
/**
 * The Elementor widget itself.
 *
 * Loaded only when Elementor is active, because it extends an Elementor class.
 *
 * @package FreeMPROForms
 */

namespace FreeMPROForms;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

final class Elementor_Widget extends \Elementor\Widget_Base {
	public function get_name(): string {
		return 'free_mpro_form';
	}

	public function get_title(): string {
		return Plugin::menu_label();
	}

	public function get_icon(): string {
		return 'eicon-form-horizontal';
	}

	/**
	 * @return array<int, string>
	 */
	public function get_categories(): array {
		return array( 'free-mpro-forms', 'general' );
	}

	/**
	 * @return array<int, string>
	 */
	public function get_keywords(): array {
		return array( 'form', 'contact', 'entries', 'mpro' );
	}

	protected function register_controls(): void {
		$this->start_controls_section(
			'fmpf_content',
			array(
				'label' => __( 'Form', 'free-mpro-forms' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'form_id',
			array(
				'label'       => __( 'Select form', 'free-mpro-forms' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => Elementor::form_choices(),
				'default'     => '',
				'label_block' => true,
			)
		);

		$this->add_control(
			'direction',
			array(
				'label'   => __( 'Direction', 'free-mpro-forms' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'auto' => __( 'Follow the site', 'free-mpro-forms' ),
					'rtl'  => __( 'Right to left', 'free-mpro-forms' ),
					'ltr'  => __( 'Left to right', 'free-mpro-forms' ),
				),
				'default' => 'auto',
			)
		);

		$this->add_control(
			'fmpf_notice',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Spacing, colours, and typography are controlled by the Advanced tab and your theme.', 'free-mpro-forms' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$form_id  = absint( $settings['form_id'] ?? 0 );

		if ( $form_id <= 0 ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'Select a form to display.', 'free-mpro-forms' ) . '</p>';
			}

			return;
		}

		$direction = in_array( ( $settings['direction'] ?? 'auto' ), array( 'rtl', 'ltr' ), true )
			? (string) $settings['direction']
			: 'auto';

		echo do_shortcode(
			sprintf( '[free_mpro_form id="%1$d" dir="%2$s"]', $form_id, esc_attr( $direction ) )
		);
	}
}
