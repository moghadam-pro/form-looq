<?php

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

function __( string $text, string $domain = '' ): string {
	return $text;
}

function sanitize_key( mixed $value ): string {
	$value = strtolower( (string) $value );
	return preg_replace( '/[^a-z0-9_\-]/', '', $value ) ?? '';
}

function sanitize_text_field( mixed $value ): string {
	$value = strip_tags( (string) $value );
	$value = preg_replace( '/[\r\n\t ]+/', ' ', $value ) ?? '';
	return trim( $value );
}

function sanitize_textarea_field( mixed $value ): string {
	$value = strip_tags( (string) $value );
	$value = preg_replace( "/\r\n?|\r/", "\n", $value ) ?? '';
	return trim( $value );
}

function sanitize_email( mixed $value ): string {
	$value = trim( (string) $value );
	return filter_var( $value, FILTER_VALIDATE_EMAIL ) ? $value : '';
}

function is_email( mixed $value ): string|false {
	$value = trim( (string) $value );
	return filter_var( $value, FILTER_VALIDATE_EMAIL ) ? $value : false;
}

function wp_unslash( mixed $value ): mixed {
	if ( is_array( $value ) ) {
		return array_map( 'wp_unslash', $value );
	}

	return is_string( $value ) ? stripslashes( $value ) : $value;
}

function fmpf_test_fail( string $message ): never {
	fwrite( STDERR, "FAIL: {$message}\n" );
	exit( 1 );
}

function fmpf_assert_true( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fmpf_test_fail( $message );
	}
}

function fmpf_assert_same( mixed $expected, mixed $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fmpf_test_fail(
			$message . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true )
		);
	}
}
