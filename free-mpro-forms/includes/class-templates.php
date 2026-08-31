<?php
/**
 * Starter form templates offered in the "new form" modal.
 *
 * @package FreeMPROForms
 */

namespace FreeMPROForms;

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
				'title'       => __( 'Blank form', 'free-mpro-forms' ),
				'description' => __( 'Start from an empty canvas and add your own fields.', 'free-mpro-forms' ),
				'icon'        => 'plus-alt2',
				'fields'      => array(),
			),
			'contact-simple'  => array(
				'title'       => __( 'Contact us (simple)', 'free-mpro-forms' ),
				'description' => __( 'Name, email, and a message. The fastest way to collect enquiries.', 'free-mpro-forms' ),
				'icon'        => 'email-alt',
				'fields'      => array(
					self::field( 'text', 'full_name', __( 'Full name', 'free-mpro-forms' ), true ),
					self::field( 'email', 'email_address', __( 'Email address', 'free-mpro-forms' ), true ),
					self::field( 'textarea', 'message', __( 'Message', 'free-mpro-forms' ), true ),
				),
			),
			'contact-full'    => array(
				'title'       => __( 'Contact us (complete)', 'free-mpro-forms' ),
				'description' => __( 'Adds phone, subject, department routing, and a consent checkbox.', 'free-mpro-forms' ),
				'icon'        => 'businessperson',
				'fields'      => array(
					self::field( 'section', '', __( 'Your details', 'free-mpro-forms' ) ),
					self::field( 'text', 'full_name', __( 'Full name', 'free-mpro-forms' ), true, array(), 'half' ),
					self::field( 'email', 'email_address', __( 'Email address', 'free-mpro-forms' ), true, array(), 'half' ),
					self::field( 'tel', 'phone', __( 'Phone number', 'free-mpro-forms' ), false, array(), 'half' ),
					self::field(
						'select',
						'department',
						__( 'Department', 'free-mpro-forms' ),
						true,
						array(
							__( 'Sales', 'free-mpro-forms' ),
							__( 'Support', 'free-mpro-forms' ),
							__( 'Billing', 'free-mpro-forms' ),
							__( 'Other', 'free-mpro-forms' ),
						),
						'half'
					),
					self::field( 'section', '', __( 'Your message', 'free-mpro-forms' ) ),
					self::field( 'text', 'subject', __( 'Subject', 'free-mpro-forms' ), true ),
					self::field( 'textarea', 'message', __( 'Message', 'free-mpro-forms' ), true ),
					self::field( 'checkbox', 'consent', __( 'I agree to be contacted about this enquiry.', 'free-mpro-forms' ), true ),
				),
			),
			'product-order'   => array(
				'title'       => __( 'Product order', 'free-mpro-forms' ),
				'description' => __( 'Collect product choice, quantity, and delivery details without a checkout.', 'free-mpro-forms' ),
				'icon'        => 'cart',
				'fields'      => array(
					self::field( 'section', '', __( 'Order', 'free-mpro-forms' ) ),
					self::field(
						'select',
						'product',
						__( 'Product', 'free-mpro-forms' ),
						true,
						array(
							__( 'Product A', 'free-mpro-forms' ),
							__( 'Product B', 'free-mpro-forms' ),
							__( 'Product C', 'free-mpro-forms' ),
						)
					),
					self::field( 'number', 'quantity', __( 'Quantity', 'free-mpro-forms' ), true, array(), 'half' ),
					self::field( 'text', 'coupon', __( 'Discount code', 'free-mpro-forms' ), false, array(), 'half' ),
					self::field( 'section', '', __( 'Delivery', 'free-mpro-forms' ) ),
					self::field( 'text', 'full_name', __( 'Full name', 'free-mpro-forms' ), true, array(), 'half' ),
					self::field( 'tel', 'phone', __( 'Phone number', 'free-mpro-forms' ), true, array(), 'half' ),
					self::field( 'textarea', 'address', __( 'Delivery address', 'free-mpro-forms' ), true ),
					self::field( 'textarea', 'order_notes', __( 'Order notes', 'free-mpro-forms' ) ),
				),
			),
			'event-signup'    => array(
				'title'       => __( 'Event registration', 'free-mpro-forms' ),
				'description' => __( 'Attendee details, session choice, and a satisfaction scale.', 'free-mpro-forms' ),
				'icon'        => 'calendar-alt',
				'fields'      => array(
					self::field( 'section', '', __( 'Attendee', 'free-mpro-forms' ) ),
					self::field( 'text', 'full_name', __( 'Full name', 'free-mpro-forms' ), true, array(), 'half' ),
					self::field( 'email', 'email_address', __( 'Email address', 'free-mpro-forms' ), true, array(), 'half' ),
					self::field( 'tel', 'phone', __( 'Phone number', 'free-mpro-forms' ), false, array(), 'half' ),
					self::field( 'text', 'organisation', __( 'Organisation', 'free-mpro-forms' ), false, array(), 'half' ),
					self::field( 'section', '', __( 'Session', 'free-mpro-forms' ) ),
					self::field(
						'radio',
						'session',
						__( 'Which session will you attend?', 'free-mpro-forms' ),
						true,
						array(
							__( 'Morning', 'free-mpro-forms' ),
							__( 'Afternoon', 'free-mpro-forms' ),
							__( 'Both', 'free-mpro-forms' ),
						)
					),
					self::field( 'scale', 'interest', __( 'How interested are you in this topic?', 'free-mpro-forms' ), false, array( '1', '2', '3', '4', '5' ) ),
					self::field( 'textarea', 'dietary', __( 'Dietary or accessibility requirements', 'free-mpro-forms' ) ),
				),
			),
		);

		/**
		 * Filter the starter templates shown when creating a form.
		 *
		 * @param array<string, array<string, mixed>> $templates Template definitions.
		 */
		return apply_filters( 'free_mpro_forms_templates', $templates );
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
