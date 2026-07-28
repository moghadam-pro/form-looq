<?php

namespace FreeMPROForms;

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
		'fmpf_nonce',
		'fmpf_started_at',
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
