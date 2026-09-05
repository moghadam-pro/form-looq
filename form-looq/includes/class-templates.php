<?php
/**
 * Starter form templates offered in the "new form" modal.
 *
 * @package FormLooq
 */

namespace FormLooq;

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
				'title'       => __( 'Blank form', 'form-looq' ),
				'description' => __( 'Start from an empty canvas and add your own fields.', 'form-looq' ),
				'icon'        => 'plus-alt2',
				'fields'      => array(),
			),
			'contact-simple'  => array(
				'title'       => __( 'Contact us (simple)', 'form-looq' ),
				'description' => __( 'Name, email, and a message. The fastest way to collect enquiries.', 'form-looq' ),
				'icon'        => 'email-alt',
				'fields'      => array(
					self::field( 'text', 'full_name', __( 'Full name', 'form-looq' ), true ),
					self::field( 'email', 'email_address', __( 'Email address', 'form-looq' ), true ),
					self::field( 'textarea', 'message', __( 'Message', 'form-looq' ), true ),
				),
			),
			'contact-full'    => array(
				'title'       => __( 'Contact us (complete)', 'form-looq' ),
				'description' => __( 'Adds phone, subject, department routing, and a consent checkbox.', 'form-looq' ),
				'icon'        => 'businessperson',
				'fields'      => array(
					self::field( 'section', '', __( 'Your details', 'form-looq' ) ),
					self::field( 'text', 'full_name', __( 'Full name', 'form-looq' ), true, array(), 'half' ),
					self::field( 'email', 'email_address', __( 'Email address', 'form-looq' ), true, array(), 'half' ),
					self::field( 'tel', 'phone', __( 'Phone number', 'form-looq' ), false, array(), 'half' ),
					self::field(
						'select',
						'department',
						__( 'Department', 'form-looq' ),
						true,
						array(
							__( 'Sales', 'form-looq' ),
							__( 'Support', 'form-looq' ),
							__( 'Billing', 'form-looq' ),
							__( 'Other', 'form-looq' ),
						),
						'half'
					),
					self::field( 'section', '', __( 'Your message', 'form-looq' ) ),
					self::field( 'text', 'subject', __( 'Subject', 'form-looq' ), true ),
					self::field( 'textarea', 'message', __( 'Message', 'form-looq' ), true ),
					self::field( 'checkbox', 'consent', __( 'I agree to be contacted about this enquiry.', 'form-looq' ), true ),
				),
			),
			'product-order'   => array(
				'title'       => __( 'Product order', 'form-looq' ),
				'description' => __( 'Collect product choice, quantity, and delivery details without a checkout.', 'form-looq' ),
				'icon'        => 'cart',
				'fields'      => array(
					self::field( 'section', '', __( 'Order', 'form-looq' ) ),
					self::field(
						'select',
						'product',
						__( 'Product', 'form-looq' ),
						true,
						array(
							__( 'Product A', 'form-looq' ),
							__( 'Product B', 'form-looq' ),
							__( 'Product C', 'form-looq' ),
						)
					),
					self::field( 'number', 'quantity', __( 'Quantity', 'form-looq' ), true, array(), 'half' ),
					self::field( 'text', 'coupon', __( 'Discount code', 'form-looq' ), false, array(), 'half' ),
					self::field( 'section', '', __( 'Delivery', 'form-looq' ) ),
					self::field( 'text', 'full_name', __( 'Full name', 'form-looq' ), true, array(), 'half' ),
					self::field( 'tel', 'phone', __( 'Phone number', 'form-looq' ), true, array(), 'half' ),
					self::field( 'textarea', 'address', __( 'Delivery address', 'form-looq' ), true ),
					self::field( 'textarea', 'order_notes', __( 'Order notes', 'form-looq' ) ),
				),
			),
			'event-signup'    => array(
				'title'       => __( 'Event registration', 'form-looq' ),
				'description' => __( 'Attendee details, session choice, and a satisfaction scale.', 'form-looq' ),
				'icon'        => 'calendar-alt',
				'fields'      => array(
					self::field( 'section', '', __( 'Attendee', 'form-looq' ) ),
					self::field( 'text', 'full_name', __( 'Full name', 'form-looq' ), true, array(), 'half' ),
					self::field( 'email', 'email_address', __( 'Email address', 'form-looq' ), true, array(), 'half' ),
					self::field( 'tel', 'phone', __( 'Phone number', 'form-looq' ), false, array(), 'half' ),
					self::field( 'text', 'organisation', __( 'Organisation', 'form-looq' ), false, array(), 'half' ),
					self::field( 'section', '', __( 'Session', 'form-looq' ) ),
					self::field(
						'radio',
						'session',
						__( 'Which session will you attend?', 'form-looq' ),
						true,
						array(
							__( 'Morning', 'form-looq' ),
							__( 'Afternoon', 'form-looq' ),
							__( 'Both', 'form-looq' ),
						)
					),
					self::field( 'scale', 'interest', __( 'How interested are you in this topic?', 'form-looq' ), false, array( '1', '2', '3', '4', '5' ) ),
					self::field( 'textarea', 'dietary', __( 'Dietary or accessibility requirements', 'form-looq' ) ),
				),
			),
		);

		/**
		 * Filter the starter templates shown when creating a form.
		 *
		 * @param array<string, array<string, mixed>> $templates Template definitions.
		 */
		return apply_filters( 'form_looq_templates', $templates );
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
