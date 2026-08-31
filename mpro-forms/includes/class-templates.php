<?php
/**
 * Starter form templates offered in the "new form" modal.
 *
 * @package MPROForms
 */

namespace MPROForms;

defined( 'ABSPATH' ) || exit;

final class Templates {
	/**
	 * All templates, keyed by slug. The first entry is always the blank form.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function all(): array {
		$templates = array(
			'blank'           => array(
				'title'       => __( 'Blank form', 'mpro-forms' ),
				'description' => __( 'Start from an empty canvas and add your own fields.', 'mpro-forms' ),
				'icon'        => 'plus-alt2',
				'fields'      => array(),
			),
			'contact-simple'  => array(
				'title'       => __( 'Contact us (simple)', 'mpro-forms' ),
				'description' => __( 'Name, email, and a message. The fastest way to collect enquiries.', 'mpro-forms' ),
				'icon'        => 'email-alt',
				'fields'      => array(
					self::field( 'text', 'full_name', __( 'Full name', 'mpro-forms' ), true ),
					self::field( 'email', 'email_address', __( 'Email address', 'mpro-forms' ), true ),
					self::field( 'textarea', 'message', __( 'Message', 'mpro-forms' ), true ),
				),
			),
			'contact-full'    => array(
				'title'       => __( 'Contact us (complete)', 'mpro-forms' ),
				'description' => __( 'Adds phone, subject, department routing, and a consent checkbox.', 'mpro-forms' ),
				'icon'        => 'businessperson',
				'fields'      => array(
					self::field( 'section', '', __( 'Your details', 'mpro-forms' ) ),
					self::field( 'text', 'full_name', __( 'Full name', 'mpro-forms' ), true, array(), 'half' ),
					self::field( 'email', 'email_address', __( 'Email address', 'mpro-forms' ), true, array(), 'half' ),
					self::field( 'tel', 'phone', __( 'Phone number', 'mpro-forms' ), false, array(), 'half' ),
					self::field(
						'select',
						'department',
						__( 'Department', 'mpro-forms' ),
						true,
						array(
							__( 'Sales', 'mpro-forms' ),
							__( 'Support', 'mpro-forms' ),
							__( 'Billing', 'mpro-forms' ),
							__( 'Other', 'mpro-forms' ),
						),
						'half'
					),
					self::field( 'section', '', __( 'Your message', 'mpro-forms' ) ),
					self::field( 'text', 'subject', __( 'Subject', 'mpro-forms' ), true ),
					self::field( 'textarea', 'message', __( 'Message', 'mpro-forms' ), true ),
					self::field( 'checkbox', 'consent', __( 'I agree to be contacted about this enquiry.', 'mpro-forms' ), true ),
				),
			),
			'product-order'   => array(
				'title'       => __( 'Product order', 'mpro-forms' ),
				'description' => __( 'Collect product choice, quantity, and delivery details without a checkout.', 'mpro-forms' ),
				'icon'        => 'cart',
				'fields'      => array(
					self::field( 'section', '', __( 'Order', 'mpro-forms' ) ),
					self::field(
						'select',
						'product',
						__( 'Product', 'mpro-forms' ),
						true,
						array(
							__( 'Product A', 'mpro-forms' ),
							__( 'Product B', 'mpro-forms' ),
							__( 'Product C', 'mpro-forms' ),
						)
					),
					self::field( 'number', 'quantity', __( 'Quantity', 'mpro-forms' ), true, array(), 'half' ),
					self::field( 'text', 'coupon', __( 'Discount code', 'mpro-forms' ), false, array(), 'half' ),
					self::field( 'section', '', __( 'Delivery', 'mpro-forms' ) ),
					self::field( 'text', 'full_name', __( 'Full name', 'mpro-forms' ), true, array(), 'half' ),
					self::field( 'tel', 'phone', __( 'Phone number', 'mpro-forms' ), true, array(), 'half' ),
					self::field( 'textarea', 'address', __( 'Delivery address', 'mpro-forms' ), true ),
					self::field( 'textarea', 'order_notes', __( 'Order notes', 'mpro-forms' ) ),
				),
			),
			'event-signup'    => array(
				'title'       => __( 'Event registration', 'mpro-forms' ),
				'description' => __( 'Attendee details, session choice, and a satisfaction scale.', 'mpro-forms' ),
				'icon'        => 'calendar-alt',
				'fields'      => array(
					self::field( 'section', '', __( 'Attendee', 'mpro-forms' ) ),
					self::field( 'text', 'full_name', __( 'Full name', 'mpro-forms' ), true, array(), 'half' ),
					self::field( 'email', 'email_address', __( 'Email address', 'mpro-forms' ), true, array(), 'half' ),
					self::field( 'tel', 'phone', __( 'Phone number', 'mpro-forms' ), false, array(), 'half' ),
					self::field( 'text', 'organisation', __( 'Organisation', 'mpro-forms' ), false, array(), 'half' ),
					self::field( 'section', '', __( 'Session', 'mpro-forms' ) ),
					self::field(
						'radio',
						'session',
						__( 'Which session will you attend?', 'mpro-forms' ),
						true,
						array(
							__( 'Morning', 'mpro-forms' ),
							__( 'Afternoon', 'mpro-forms' ),
							__( 'Both', 'mpro-forms' ),
						)
					),
					self::field( 'scale', 'interest', __( 'How interested are you in this topic?', 'mpro-forms' ), false, array( '1', '2', '3', '4', '5' ) ),
					self::field( 'textarea', 'dietary', __( 'Dietary or accessibility requirements', 'mpro-forms' ) ),
				),
			),
		);

		/**
		 * Filter the starter templates shown when creating a form.
		 *
		 * @param array<string, array<string, mixed>> $templates Template definitions.
		 */
		return apply_filters( 'mpro_forms_templates', $templates );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function fields( string $slug ): array {
		$templates = self::all();

		if ( ! isset( $templates[ $slug ] ) ) {
			return array();
		}

		return Field_Validator::sanitize_fields( (array) $templates[ $slug ]['fields'] );
	}

	public static function exists( string $slug ): bool {
		return array_key_exists( $slug, self::all() );
	}

	/**
	 * @param array<int, string> $options Choice list for option-bearing types.
	 * @return array<string, mixed>
	 */
	private static function field( string $type, string $name, string $label, bool $required = false, array $options = array(), string $width = 'full' ): array {
		return array(
			'type'        => $type,
			'name'        => $name,
			'label'       => $label,
			'required'    => $required,
			'options'     => $options,
			'help'        => '',
			'placeholder' => '',
			'width'       => $width,
		);
	}
}
