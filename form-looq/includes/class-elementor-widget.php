<?php
/**
 * The Elementor widget itself.
 *
 * Loaded only when Elementor is active, because it extends an Elementor class.
 *
 * @package FormLooq
 */

namespace FormLooq;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

final class Elementor_Widget extends \Elementor\Widget_Base {
	public function get_name(): string {
		return 'looq_form';
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
		return array( 'form-looq', 'general' );
	}

	/**
	 * @return array<int, string>
	 */
	public function get_keywords(): array {
		return array( 'form', 'contact', 'entries', 'looq' );
	}

	protected function register_controls(): void {
		$this->start_controls_section(
			'looq_content',
			array(
				'label' => __( 'Form', 'form-looq' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'form_id',
			array(
				'label'       => __( 'Select form', 'form-looq' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => Elementor::form_choices(),
				'default'     => '',
				'label_block' => true,
			)
		);

		$this->add_control(
			'direction',
			array(
				'label'   => __( 'Direction', 'form-looq' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'auto' => __( 'Follow the site', 'form-looq' ),
					'rtl'  => __( 'Right to left', 'form-looq' ),
					'ltr'  => __( 'Left to right', 'form-looq' ),
				),
				'default' => 'auto',
			)
		);

		$this->add_control(
			'looq_notice',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Spacing, colours, and typography are controlled by the Advanced tab and your theme.', 'form-looq' ),
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
				echo '<p>' . esc_html__( 'Select a form to display.', 'form-looq' ) . '</p>';
			}

			return;
		}

		$direction = in_array( ( $settings['direction'] ?? 'auto' ), array( 'rtl', 'ltr' ), true )
			? (string) $settings['direction']
			: 'auto';

		echo do_shortcode(
			sprintf( '[looq_form id="%1$d" dir="%2$s"]', $form_id, esc_attr( $direction ) )
		);
	}
}
