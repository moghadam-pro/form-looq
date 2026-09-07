<?php
/**
 * Plugin settings storage and sanitization.
 *
 * Everything lives in one option so that a single autoloaded row covers the
 * whole plugin instead of a dozen scattered keys.
 *
 * @package FormLooq
 */

namespace FormLooq;

defined( 'ABSPATH' ) || exit;

final class Settings {
	public const OPTION = 'form_looq_settings';

	private const CRON_HOOK = 'form_looq_daily_cleanup';

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
			'editor'  => __( 'Editor', 'form-looq' ),
			'addons'  => __( 'Add-ons', 'form-looq' ),
			'general' => __( 'General', 'form-looq' ),
			'widgets' => __( 'Widgets', 'form-looq' ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			// Editor.
			'default_layout'            => 'one-column',
			// Left untranslated here deliberately: defaults() can run during
			// plugin activation/upgrade (see Install::activate() and
			// maybe_upgrade(), both called on 'plugins_loaded'), which is
			// before the 'init' hook loads this plugin's text domain. Calling
			// __() this early returns the untranslated string anyway and logs
			// a "Translation loading … triggered too early" notice. The
			// translated fallback lives in default_submit_label() instead,
			// which every caller uses in place of reading this key directly.
			'default_submit_label'      => '',
			'confirm_before_leaving'    => true,

			// Add-ons. Off by default: nothing is requested from formlooq.ir
			// until an admin explicitly opts in here, after reading what a
			// request sends (see render_addons_tab() in Page_Settings).
			'addons_remote_enabled'     => false,
			'addons_cache_hours'        => 12,

			// General.
			'output_css'                => true,
			'no_conflict_mode'          => false,
			'currency'                  => 'IRR',
			'retention_days'            => 0,
			'delete_data_on_uninstall'  => false,

			// Widgets.
			'dashboard_widget'          => true,
			'elementor_widget'          => true,
		);
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
	 * The default submit-button label, translated at call time rather than
	 * inside defaults() (see the comment on that key for why).
	 */
	public static function default_submit_label(): string {
		$value = (string) self::get( 'default_submit_label', '' );

		return '' !== $value ? $value : __( 'Submit', 'form-looq' );
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
			'dashboard_widget',
			'elementor_widget',
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
			// An empty value here means "use the translated default" (see
			// default_submit_label()), not the literal English word "Submit".
			$clean['default_submit_label'] = sanitize_text_field( (string) $input['default_submit_label'] );
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
