<?php

namespace MPROForms;

defined( 'ABSPATH' ) || exit;

final class Submission_Validator {
	private const MAX_SHORT_VALUE_LENGTH = 500;
	private const MAX_LONG_VALUE_LENGTH  = 5000;

	public static function validate( array $fields, array $request ): array {
		$data       = array();
		$values     = array();
		$errors     = array();
		$field_keys = array();

		foreach ( $fields as $field ) {
			if ( 'section' !== $field['type'] ) {
				$field_keys[] = $field['name'];
			}
		}

		$allowed_keys = array_merge(
			$field_keys,
			array( 'action', 'form_id', 'mpro_nonce', 'mpro_started_at', 'website', '_wp_http_referer' )
		);
		$unexpected   = array_diff( array_keys( $request ), $allowed_keys );

		if ( $unexpected ) {
			$errors['_form'] = __( 'The submission contained unexpected fields. Please reload the page and try again.', 'mpro-forms' );
		}

		foreach ( $fields as $field ) {
			if ( 'section' === $field['type'] ) {
				continue;
			}

			$result                   = self::validate_value( $field, $request[ $field['name'] ] ?? '' );
			$values[ $field['name'] ] = $result['display'];
			$data[ $field['name'] ]   = $result['value'];

			if ( ! $result['valid'] ) {
				$errors[ $field['name'] ] = self::error_message( $field, $result['code'] );
			}
		}

		return array(
			'valid'  => ! $errors,
			'data'   => $data,
			'values' => $values,
			'errors' => $errors,
		);
	}

	private static function validate_value( array $field, mixed $raw_value ): array {
		if ( is_array( $raw_value ) || is_object( $raw_value ) ) {
			return self::result( false, '', '', 'invalid' );
		}

		$raw     = is_string( $raw_value ) ? wp_unslash( $raw_value ) : (string) $raw_value;
		$trimmed = trim( $raw );

		if ( 'checkbox' === $field['type'] ) {
			$value = '1' === $trimmed ? '1' : '';
			if ( $field['required'] && '1' !== $value ) {
				return self::result( false, '', $value, 'required' );
			}

			return self::result( true, $value, $value );
		}

		if ( '' === $trimmed ) {
			return $field['required']
				? self::result( false, '', '', 'required' )
				: self::result( true, '', '' );
		}

		switch ( $field['type'] ) {
			case 'email':
				$display = self::limit( sanitize_text_field( $trimmed ), self::MAX_SHORT_VALUE_LENGTH );
				$value   = sanitize_email( $trimmed );
				return $value && is_email( $value )
					? self::result( true, $value, $display )
					: self::result( false, '', $display, 'email' );

			case 'number':
				$display = self::limit( sanitize_text_field( $trimmed ), 64 );
				return is_numeric( $trimmed ) && self::length( $trimmed ) <= 64
					? self::result( true, sanitize_text_field( $trimmed ), $display )
					: self::result( false, '', $display, 'number' );

			case 'select':
			case 'radio':
			case 'scale':
				$value = sanitize_text_field( $trimmed );
				return in_array( $value, $field['options'], true )
					? self::result( true, $value, $value )
					: self::result( false, '', '', 'choice' );

			case 'textarea':
				$value = sanitize_textarea_field( $raw );
				return self::length( $value ) <= self::MAX_LONG_VALUE_LENGTH
					? self::result( true, $value, $value )
					: self::result( false, '', self::limit( $value, self::MAX_LONG_VALUE_LENGTH ), 'length' );

			case 'tel':
				$value = self::limit( sanitize_text_field( $trimmed ), 40 );
				return self::length( $trimmed ) <= 40 && preg_match( '/^[\p{N}+().\-\s#*]+$/u', $trimmed )
					? self::result( true, $value, $value )
					: self::result( false, '', $value, 'tel' );

			default:
				$value = sanitize_text_field( $trimmed );
				return self::length( $value ) <= self::MAX_SHORT_VALUE_LENGTH
					? self::result( true, $value, $value )
					: self::result( false, '', self::limit( $value, self::MAX_SHORT_VALUE_LENGTH ), 'length' );
		}
	}

	private static function error_message( array $field, string $code ): string {
		switch ( $code ) {
			case 'required':
				return sprintf(
					/* translators: %s: Field label. */
					__( '%s is required.', 'mpro-forms' ),
					$field['label']
				);
			case 'email':
				return __( 'Enter a valid email address.', 'mpro-forms' );
			case 'number':
				return __( 'Enter a valid number.', 'mpro-forms' );
			case 'tel':
				return __( 'Enter a valid telephone number.', 'mpro-forms' );
			case 'choice':
				return __( 'Select one of the available options.', 'mpro-forms' );
			case 'length':
				return __( 'This value is longer than the allowed limit.', 'mpro-forms' );
			default:
				return __( 'Review this field and try again.', 'mpro-forms' );
		}
	}

	private static function result( bool $valid, string $value, string $display, string $code = '' ): array {
		return array(
			'valid'   => $valid,
			'value'   => $value,
			'display' => $display,
			'code'    => $code,
		);
	}

	private static function limit( string $value, int $length ): string {
		return self::length( $value ) > $length ? self::slice( $value, 0, $length ) : $value;
	}

	private static function length( string $value ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
	}

	private static function slice( string $value, int $start, int $length ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, $start, $length ) : substr( $value, $start, $length );
	}
}
