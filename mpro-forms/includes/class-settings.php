<?php
/**
 * Plugin settings storage and sanitization.
 *
 * Everything lives in one option so that a single autoloaded row covers the
 * whole plugin instead of a dozen scattered keys.
 *
 * @package MPROForms
 */

namespace MPROForms;

defined( 'ABSPATH' ) || exit;

final class Settings {
	public const OPTION = 'mpro_forms_settings';

	private const CRON_HOOK = 'mpro_forms_daily_cleanup';

	/**
	 * @var array<string, mixed>|null
	 */
	private static ?array $cache = null;

	public static function init(): void {
		add_action( self::CRON_HOOK, array( self::class, 'run_retention_cleanup' ) );
	}

	/**
	 * Setting tabs in display order.
	 *
	 * @return array<string, string>
	 */
	public static function tabs(): array {
		return array(
			'editor'   => __( 'Editor', 'mpro-forms' ),
			'addons'   => __( 'Add-ons', 'mpro-forms' ),
			'license'  => __( 'License', 'mpro-forms' ),
			'general'  => __( 'General', 'mpro-forms' ),
			'widgets'  => __( 'Widgets', 'mpro-forms' ),
			'rest'     => __( 'REST API', 'mpro-forms' ),
			'sms'      => __( 'SMS', 'mpro-forms' ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			// Editor.
			'default_layout'            => 'one-column',
			'default_submit_label'      => __( 'Submit', 'mpro-forms' ),
			'confirm_before_leaving'    => true,

			// Add-ons.
			'addons_remote_enabled'     => true,
			'addons_cache_hours'        => 12,

			// General.
			'output_css'                => true,
			'no_conflict_mode'          => false,
			'currency'                  => 'IRR',
			'retention_days'            => 0,
			'delete_data_on_uninstall'  => false,
			'auto_update'               => false,

			// Widgets.
			'dashboard_widget'          => true,
			'elementor_widget'          => true,

			// REST API.
			'rest_enabled'              => false,

			// SMS.
			'sms_enabled'               => false,
			'sms_provider'              => '',
			'sms_api_key'               => '',
			'sms_sender'                => '',
			'sms_verify_numbers'        => false,
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function sms_providers(): array {
		$providers = array(
			''             => __( 'Not selected', 'mpro-forms' ),
			'kavenegar'    => 'Kavenegar',
			'smsir'        => 'SMS.ir',
			'melipayamak'  => 'MeliPayamak',
			'ghasedak'     => 'Ghasedak',
			'custom'       => __( 'Custom endpoint', 'mpro-forms' ),
		);

		/**
		 * Filter the selectable SMS gateways.
		 *
		 * @param array<string, string> $providers Provider slug to label map.
		 */
		return apply_filters( 'mpro_forms_sms_providers', $providers );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		if ( null === self::$cache ) {
			$stored      = get_option( self::OPTION, array() );
			self::$cache = wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
		}

		return self::$cache;
	}

	/**
	 * @param mixed $fallback Value returned when the key is unknown.
	 * @return mixed
	 */
	public static function get( string $key, $fallback = null ) {
		$settings = self::all();

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $fallback;
	}

	/**
	 * Persist a sanitized settings array and reconcile the retention cron.
	 *
	 * @param array<string, mixed> $input Raw submitted values.
	 */
	public static function save( array $input ): void {
		$clean = self::sanitize( $input );

		update_option( self::OPTION, $clean, true );
		self::$cache = $clean;

		self::sync_cron();
	}

	/**
	 * @param array<string, mixed> $input Raw submitted values.
	 * @return array<string, mixed>
	 */
	public static function sanitize( array $input ): array {
		$current = self::all();
		$clean   = $current;

		$booleans = array(
			'confirm_before_leaving',
			'addons_remote_enabled',
			'output_css',
			'no_conflict_mode',
			'delete_data_on_uninstall',
			'auto_update',
			'dashboard_widget',
			'elementor_widget',
			'rest_enabled',
			'sms_enabled',
			'sms_verify_numbers',
		);

		foreach ( $booleans as $key ) {
			if ( array_key_exists( $key, $input ) ) {
				$clean[ $key ] = ! empty( $input[ $key ] );
			}
		}

		if ( array_key_exists( 'default_layout', $input ) ) {
			$clean['default_layout'] = in_array( $input['default_layout'], array( 'one-column', 'two-column' ), true )
				? (string) $input['default_layout']
				: 'one-column';
		}

		if ( array_key_exists( 'default_submit_label', $input ) ) {
			$label                         = sanitize_text_field( (string) $input['default_submit_label'] );
			$clean['default_submit_label'] = '' !== $label ? $label : __( 'Submit', 'mpro-forms' );
		}

		if ( array_key_exists( 'addons_cache_hours', $input ) ) {
			$clean['addons_cache_hours'] = max( 1, min( 168, absint( $input['addons_cache_hours'] ) ) );
		}

		if ( array_key_exists( 'currency', $input ) ) {
			$currency          = strtoupper( sanitize_text_field( (string) $input['currency'] ) );
			$clean['currency'] = preg_match( '/^[A-Z]{3}$/', $currency ) ? $currency : 'IRR';
		}

		if ( array_key_exists( 'retention_days', $input ) ) {
			$clean['retention_days'] = max( 0, min( 3650, absint( $input['retention_days'] ) ) );
		}

		if ( array_key_exists( 'sms_provider', $input ) ) {
			$provider              = sanitize_key( (string) $input['sms_provider'] );
			$clean['sms_provider'] = array_key_exists( $provider, self::sms_providers() ) ? $provider : '';
		}

		if ( array_key_exists( 'sms_api_key', $input ) ) {
			$clean['sms_api_key'] = sanitize_text_field( (string) $input['sms_api_key'] );
		}

		if ( array_key_exists( 'sms_sender', $input ) ) {
			$clean['sms_sender'] = sanitize_text_field( (string) $input['sms_sender'] );
		}

		return $clean;
	}

	/**
	 * Schedule or clear the retention job to match the saved window.
	 */
	public static function sync_cron(): void {
		$scheduled = wp_next_scheduled( self::CRON_HOOK );

		if ( (int) self::get( 'retention_days', 0 ) > 0 ) {
			if ( ! $scheduled ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
			}

			return;
		}

		if ( $scheduled ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}
	}

	public static function run_retention_cleanup(): void {
		$days = (int) self::get( 'retention_days', 0 );

		if ( $days > 0 ) {
			Entry_Repository::purge_older_than( $days );
		}
	}

	/**
	 * Reset the in-request cache. Used by tests and after direct option writes.
	 */
	public static function flush(): void {
		self::$cache = null;
	}
}
