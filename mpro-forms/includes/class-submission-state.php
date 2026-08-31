<?php

namespace MPROForms;

defined( 'ABSPATH' ) || exit;

final class Submission_State {
	private const TRANSIENT_PREFIX = 'mpro_forms_state_';
	private const TTL              = 300;

	public static function create( int $form_id, array $values, array $errors ): string {
		if ( $form_id <= 0 || ! $errors ) {
			return '';
		}

		try {
			$token = bin2hex( random_bytes( 24 ) );
		} catch ( \Throwable $exception ) {
			$token = wp_generate_password( 48, false, false );
		}

		$token = preg_replace( '/[^A-Za-z0-9]/', '', $token ) ?: '';
		if ( strlen( $token ) < 32 ) {
			return '';
		}

		$state = array(
			'form_id'    => $form_id,
			'values'     => self::sanitize_values( $values ),
			'errors'     => self::sanitize_errors( $errors ),
			'created_at' => time(),
		);

		if ( ! set_transient( self::key( $token ), $state, self::TTL ) ) {
			return '';
		}

		return $token;
	}

	public static function consume( string $token, int $form_id ): array {
		if ( ! preg_match( '/^[A-Za-z0-9]{32,64}$/', $token ) || $form_id <= 0 ) {
			return array();
		}

		$key   = self::key( $token );
		$state = get_transient( $key );

		if ( ! is_array( $state ) || (int) ( $state['form_id'] ?? 0 ) !== $form_id ) {
			return array();
		}

		delete_transient( $key );

		return array(
			'values' => is_array( $state['values'] ?? null ) ? $state['values'] : array(),
			'errors' => is_array( $state['errors'] ?? null ) ? $state['errors'] : array(),
		);
	}

	private static function key( string $token ): string {
		return self::TRANSIENT_PREFIX . hash( 'sha256', $token );
	}

	private static function sanitize_values( array $values ): array {
		$clean = array();

		foreach ( array_slice( $values, 0, 50, true ) as $name => $value ) {
			$name = sanitize_key( (string) $name );
			if ( '' === $name || is_array( $value ) || is_object( $value ) ) {
				continue;
			}

			$clean[ $name ] = sanitize_textarea_field( (string) $value );
		}

		return $clean;
	}

	private static function sanitize_errors( array $errors ): array {
		$clean = array();

		foreach ( array_slice( $errors, 0, 51, true ) as $name => $message ) {
			$name = '_form' === $name ? '_form' : sanitize_key( (string) $name );
			if ( '' === $name || is_array( $message ) || is_object( $message ) ) {
				continue;
			}

			$clean[ $name ] = sanitize_text_field( (string) $message );
		}

		return $clean;
	}
}
