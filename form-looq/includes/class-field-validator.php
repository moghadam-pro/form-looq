<?php

namespace FormLooq;

defined( 'ABSPATH' ) || exit;

final class Field_Validator {
	private const ALLOWED_TYPES = array(
		'section',
		'text',
		'email',
		'tel',
		'number',
		'textarea',
		'select',
		'radio',
		'scale',
		'checkbox',
	);

	private const RESERVED_NAMES = array(
		'action',
		'form_id',
		'looq_nonce',
		'looq_started_at',
		'website',
		'submit',
	);

	private const MAX_FIELDS            = 50;
	private const MAX_DEFINITION_LENGTH = 50000;
	private const MAX_FIELD_NAME_LENGTH = 64;
	private const MAX_LABEL_LENGTH      = 160;
	private const MAX_HELP_LENGTH       = 300;
	private const MAX_OPTIONS           = 100;
	private const MAX_OPTION_LENGTH     = 200;

	/**
	 * Field types available in the visual builder, in palette order.
	 *
	 * @return array<string, array{label: string, icon: string, has_options: bool}>
	 */
	public static function types(): array {
		return array(
			'text'     => array(
				'label'       => __( 'Single line text', 'form-looq' ),
				'icon'        => 'editor-textcolor',
				'has_options' => false,
			),
			'textarea' => array(
				'label'       => __( 'Paragraph text', 'form-looq' ),
				'icon'        => 'editor-alignleft',
				'has_options' => false,
			),
			'email'    => array(
				'label'       => __( 'Email', 'form-looq' ),
				'icon'        => 'email',
				'has_options' => false,
			),
			'tel'      => array(
				'label'       => __( 'Phone', 'form-looq' ),
				'icon'        => 'phone',
				'has_options' => false,
			),
			'number'   => array(
				'label'       => __( 'Number', 'form-looq' ),
				'icon'        => 'calculator',
				'has_options' => false,
			),
			'select'   => array(
				'label'       => __( 'Dropdown', 'form-looq' ),
				'icon'        => 'menu',
				'has_options' => true,
			),
			'radio'    => array(
				'label'       => __( 'Radio buttons', 'form-looq' ),
				'icon'        => 'marker',
				'has_options' => true,
			),
			'checkbox' => array(
				'label'       => __( 'Checkbox', 'form-looq' ),
				'icon'        => 'yes',
				'has_options' => false,
			),
			'scale'    => array(
				'label'       => __( 'Rating scale', 'form-looq' ),
				'icon'        => 'star-filled',
				'has_options' => true,
			),
			'section'  => array(
				'label'       => __( 'Section break', 'form-looq' ),
				'icon'        => 'minus',
				'has_options' => false,
			),
		);
	}

	/**
	 * Sanitize a structured field list coming from the visual builder or a template.
	 *
	 * Applies the same rules as the legacy line-based parser: type allowlist,
	 * reserved and duplicate name rejection, and hard limits on size.
	 *
	 * @param array<int, mixed> $fields Raw field definitions.
	 * @return array<int, array<string, mixed>>
	 */
	public static function sanitize_fields( array $fields ): array {
		$clean = array();
		$names = array();

		foreach ( $fields as $field ) {
			if ( count( $clean ) >= self::MAX_FIELDS ) {
				break;
			}

			if ( ! is_array( $field ) ) {
				continue;
			}

			$type  = sanitize_key( (string) ( $field['type'] ?? '' ) );
			$label = self::limit_text( sanitize_text_field( (string) ( $field['label'] ?? '' ) ), self::MAX_LABEL_LENGTH );

			if ( ! in_array( $type, self::ALLOWED_TYPES, true ) || '' === $label ) {
				continue;
			}

			$name = sanitize_key( (string) ( $field['name'] ?? '' ) );

			if ( 'section' !== $type ) {
				if ( '' === $name ) {
					$name = self::generate_name( $label, $names );
				}

				if (
					'' === $name ||
					self::length( $name ) > self::MAX_FIELD_NAME_LENGTH ||
					in_array( $name, self::RESERVED_NAMES, true ) ||
					isset( $names[ $name ] )
				) {
					continue;
				}

				$names[ $name ] = true;
			} else {
				$name = '';
			}

			$options = self::sanitize_option_list( (array) ( $field['options'] ?? array() ) );

			if ( 'scale' === $type && ! $options ) {
				$options = array( '1', '2', '3', '4', '5' );
			}

			if ( in_array( $type, array( 'select', 'radio', 'scale' ), true ) && ! $options ) {
				continue;
			}

			$clean[] = array(
				'type'        => $type,
				'name'        => $name,
				'label'       => $label,
				'required'    => ! empty( $field['required'] ),
				'options'     => $options,
				'help'        => self::limit_text( sanitize_text_field( (string) ( $field['help'] ?? '' ) ), self::MAX_HELP_LENGTH ),
				'placeholder' => self::limit_text( sanitize_text_field( (string) ( $field['placeholder'] ?? '' ) ), self::MAX_LABEL_LENGTH ),
				'width'       => in_array( ( $field['width'] ?? '' ), array( 'full', 'half' ), true ) ? (string) $field['width'] : 'full',
			);
		}

		return $clean;
	}

	/**
	 * Derive a unique machine name from a label when the builder did not supply one.
	 *
	 * @param array<string, bool> $taken Names already in use.
	 */
	private static function generate_name( string $label, array $taken ): string {
		$base = sanitize_key( str_replace( ' ', '_', $label ) );
		$base = '' !== $base ? $base : 'field';
		$base = self::limit_text( $base, self::MAX_FIELD_NAME_LENGTH - 4 );

		if ( ! isset( $taken[ $base ] ) && ! in_array( $base, self::RESERVED_NAMES, true ) ) {
			return $base;
		}

		for ( $suffix = 2; $suffix <= self::MAX_FIELDS + 1; ++$suffix ) {
			$candidate = $base . '_' . $suffix;
			if ( ! isset( $taken[ $candidate ] ) && ! in_array( $candidate, self::RESERVED_NAMES, true ) ) {
				return $candidate;
			}
		}

		return '';
	}

	/**
	 * @param array<int, mixed> $options Raw option list.
	 * @return array<int, string>
	 */
	private static function sanitize_option_list( array $options ): array {
		$clean = array();

		foreach ( $options as $option ) {
			if ( count( $clean ) >= self::MAX_OPTIONS ) {
				break;
			}

			if ( is_array( $option ) || is_object( $option ) ) {
				continue;
			}

			$option = self::limit_text( sanitize_text_field( trim( (string) $option ) ), self::MAX_OPTION_LENGTH );
			if ( '' === $option || in_array( $option, $clean, true ) ) {
				continue;
			}

			$clean[] = $option;
		}

		return $clean;
	}

	public static function sanitize_definition( string $definition ): string {
		$definition = sanitize_textarea_field( $definition );

		if ( self::length( $definition ) > self::MAX_DEFINITION_LENGTH ) {
			$definition = self::slice( $definition, 0, self::MAX_DEFINITION_LENGTH );
		}

		return trim( $definition );
	}

	public static function parse_definition( string $definition ): array {
		$fields = array();
		$names  = array();

		foreach ( preg_split( '/\R/u', $definition ) ?: array() as $line ) {
			if ( count( $fields ) >= self::MAX_FIELDS ) {
				break;
			}

			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}

			$parts   = array_pad( array_map( 'trim', explode( '|', $line, 6 ) ), 6, '' );
			$type    = sanitize_key( $parts[0] );
			$name    = sanitize_key( $parts[1] );
			$label   = self::limit_text( sanitize_text_field( $parts[2] ), self::MAX_LABEL_LENGTH );
			$options = self::sanitize_options( $parts[4] );
			$help    = self::limit_text( sanitize_text_field( $parts[5] ), self::MAX_HELP_LENGTH );

			if ( ! in_array( $type, self::ALLOWED_TYPES, true ) || '' === $label ) {
				continue;
			}

			if ( 'section' !== $type ) {
				if (
					'' === $name ||
					self::length( $name ) > self::MAX_FIELD_NAME_LENGTH ||
					in_array( $name, self::RESERVED_NAMES, true ) ||
					isset( $names[ $name ] )
				) {
					continue;
				}

				$names[ $name ] = true;
			}

			if ( 'scale' === $type && ! $options ) {
				$options = array( '1', '2', '3', '4', '5' );
			}

			if ( in_array( $type, array( 'select', 'radio', 'scale' ), true ) && ! $options ) {
				continue;
			}

			$fields[] = array(
				'type'     => $type,
				'name'     => $name,
				'label'    => $label,
				'required' => 'required' === strtolower( $parts[3] ),
				'options'  => $options,
				'help'     => $help,
			);
		}

		return $fields;
	}

	private static function sanitize_options( string $definition ): array {
		$options = array();

		foreach ( explode( ',', $definition ) as $option ) {
			if ( count( $options ) >= self::MAX_OPTIONS ) {
				break;
			}

			$option = self::limit_text( sanitize_text_field( trim( $option ) ), self::MAX_OPTION_LENGTH );
			if ( '' === $option || in_array( $option, $options, true ) ) {
				continue;
			}

			$options[] = $option;
		}

		return $options;
	}

	private static function limit_text( string $value, int $length ): string {
		return self::length( $value ) > $length ? self::slice( $value, 0, $length ) : $value;
	}

	private static function length( string $value ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
	}

	private static function slice( string $value, int $start, int $length ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, $start, $length ) : substr( $value, $start, $length );
	}
}
