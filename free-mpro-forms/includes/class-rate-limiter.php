<?php
/**
 * Privacy-conscious submission rate limiting.
 *
 * @package FreeMPROForms
 */

namespace FreeMPROForms;

defined( 'ABSPATH' ) || exit;

final class Rate_Limiter {
	private const SHORT_LIMIT  = 5;
	private const SHORT_WINDOW = 600;
	private const LONG_LIMIT   = 20;
	private const LONG_WINDOW  = 3600;

	public static function init(): void {
		add_action( 'admin_post_fmpf_submit', array( self::class, 'guard_submission' ), 5 );
		add_action( 'admin_post_nopriv_fmpf_submit', array( self::class, 'guard_submission' ), 5 );
	}

	public static function guard_submission(): void {
		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$started = isset( $_POST['fmpf_started_at'] ) ? absint( $_POST['fmpf_started_at'] ) : 0;
		$elapsed = $started > 0 ? time() - $started : 0;

		$form = Form_Repository::get( $form_id );

		if (
			! $form ||
			Form_Repository::STATUS_ACTIVE !== $form['status'] ||
			! isset( $_POST['fmpf_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmpf_nonce'] ) ), 'fmpf_submit_' . $form_id ) ||
			! empty( $_POST['website'] ) ||
			$elapsed < 2 ||
			$elapsed > DAY_IN_SECONDS
		) {
			return;
		}

		if ( ! apply_filters( 'free_mpro_forms_rate_limit_enabled', true, $form_id ) ) {
			return;
		}

		$result = self::check_and_record( $form_id, self::client_identity() );
		if ( $result['allowed'] ) {
			return;
		}

		$errors = array(
			'_form' => __( 'Too many submission attempts. Please wait a little and try again.', 'free-mpro-forms' ),
		);
		$token  = Submission_State::create( $form_id, array(), $errors );
		self::redirect( $form_id, $token );
	}

	public static function check_and_record( int $form_id, string $identity ): array {
		$identity = '' !== $identity ? $identity : 'unknown';
		$hash     = hash_hmac( 'sha256', $identity, wp_salt( 'nonce' ) );
		$windows  = apply_filters(
			'free_mpro_forms_rate_limit_windows',
			array(
				'short' => array(
					'limit'  => self::SHORT_LIMIT,
					'window' => self::SHORT_WINDOW,
				),
				'long'  => array(
					'limit'  => self::LONG_LIMIT,
					'window' => self::LONG_WINDOW,
				),
			),
			$form_id
		);

		$retry_after = 0;

		foreach ( $windows as $name => $config ) {
			$limit  = max( 1, absint( $config['limit'] ?? 0 ) );
			$window = max( 1, absint( $config['window'] ?? 0 ) );
			$key    = self::transient_key( $form_id, sanitize_key( (string) $name ), $hash );
			$state  = get_transient( $key );

			if ( ! is_array( $state ) || empty( $state['expires'] ) || (int) $state['expires'] <= time() ) {
				$state = array(
					'count'   => 0,
					'expires' => time() + $window,
				);
			}

			if ( (int) $state['count'] >= $limit ) {
				$retry_after = max( $retry_after, (int) $state['expires'] - time() );
				continue;
			}

			++$state['count'];
			set_transient( $key, $state, max( 1, (int) $state['expires'] - time() ) );
		}

		return array(
			'allowed'     => 0 === $retry_after,
			'retry_after' => $retry_after,
		);
	}

	private static function client_identity(): string {
		$address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$address = filter_var( $address, FILTER_VALIDATE_IP ) ? $address : 'unknown';

		return (string) apply_filters( 'free_mpro_forms_rate_limit_identity', $address );
	}

	private static function transient_key( int $form_id, string $window, string $hash ): string {
		return 'fmpf_rate_' . $form_id . '_' . $window . '_' . substr( $hash, 0, 32 );
	}

	private static function redirect( int $form_id, string $token ): void {
		$url = wp_validate_redirect( wp_get_referer() ?: '', home_url( '/' ) );
		$url = preg_replace( '/#.*$/', '', $url ) ?: home_url( '/' );
		$url = remove_query_arg( array( 'fmpf_status', 'fmpf_state', 'fmpf_form' ), $url );
		$url = add_query_arg(
			array(
				'fmpf_status' => 'error',
				'fmpf_form'   => $form_id,
				'fmpf_state'  => $token,
			),
			$url
		);

		wp_safe_redirect( $url . '#fmpf-form-' . $form_id );
		exit;
	}
}
