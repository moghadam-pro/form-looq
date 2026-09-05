<?php
/**
 * Schema creation and upgrade routine.
 *
 * @package FormLooq
 */

namespace FormLooq;

defined( 'ABSPATH' ) || exit;

final class Install {
	public const SCHEMA_VERSION = 3;

	private const OPTION_SCHEMA  = 'form_looq_schema_version';
	private const OPTION_VERSION = 'form_looq_version';
	private const OPTION_INSTALLED_AT = 'form_looq_installed_at';

	public static function activate(): void {
		self::migrate_legacy_names();
		self::install_tables();
		self::add_capabilities();

		if ( ! get_option( self::OPTION_INSTALLED_AT ) ) {
			add_option( self::OPTION_INSTALLED_AT, time(), '', false );
			// Matches Admin\Onboarding::OPTION_PENDING, which is not loaded on the front end.
			add_option( 'form_looq_show_welcome', 1, '', false );
		}

		update_option( self::OPTION_SCHEMA, self::SCHEMA_VERSION, false );
		update_option( self::OPTION_VERSION, FORM_LOOQ_VERSION, false );

		// Only schedules the job when a retention window is actually configured;
		// a zero-day default means "keep forever," so there is nothing to run.
		Settings::sync_cron();
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'form_looq_daily_cleanup' );
	}

	public static function maybe_upgrade(): void {
		$stored_version = (string) get_option( self::OPTION_VERSION, '' );

		if ( $stored_version !== FORM_LOOQ_VERSION ) {
			// A site updated by replacing the plugin folder (the common path for
			// a manual ZIP upload) never re-fires register_activation_hook, so an
			// unconditional cron schedule left behind by an older version — like
			// 0.3.2's, which ran regardless of the retention setting — would
			// otherwise survive until something else happened to touch it.
			Settings::sync_cron();
			update_option( self::OPTION_VERSION, FORM_LOOQ_VERSION, false );
		}

		if ( (int) get_option( self::OPTION_SCHEMA, 0 ) === self::SCHEMA_VERSION ) {
			return;
		}

		self::migrate_legacy_names();
		self::install_tables();
		self::add_capabilities();
		update_option( self::OPTION_SCHEMA, self::SCHEMA_VERSION, false );
	}

	/**
	 * Carry data over from every earlier name this plugin shipped under.
	 *
	 * It started as "Free MPRO Forms" (fmpf_ prefix), was renamed to "MPRO
	 * Forms" (mpro_ / mpro_forms_ prefix), and is now "Form LOOQ" (looq_ /
	 * form_looq_ prefix). Nothing beyond a handful of real installs ever ran
	 * under the older names, but leaving their tables, options, cron event,
	 * or capability behind would strand data and litter the database.
	 * Renaming is cheap and runs at most once per generation, because each
	 * guard only fires while its legacy table is still present.
	 */
	public static function migrate_legacy_names(): void {
		global $wpdb;

		$table_generations = array(
			// fmpf_ (Free MPRO Forms) -> mpro_ (MPRO Forms).
			array(
				$wpdb->prefix . 'fmpf_forms'   => $wpdb->prefix . 'mpro_forms',
				$wpdb->prefix . 'fmpf_entries' => $wpdb->prefix . 'mpro_entries',
			),
			// mpro_ (MPRO Forms) -> looq_ (Form LOOQ).
			array(
				$wpdb->prefix . 'mpro_forms'   => DB::forms_table(),
				$wpdb->prefix . 'mpro_entries' => DB::entries_table(),
			),
		);

		foreach ( $table_generations as $pairs ) {
			foreach ( $pairs as $legacy => $current ) {
				if ( ! DB::table_exists( $legacy ) || DB::table_exists( $current ) ) {
					continue;
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( "RENAME TABLE `{$legacy}` TO `{$current}`" );
			}
		}

		$option_generations = array(
			// fmpf_ -> mpro_forms_.
			array(
				'fmpf_settings'       => 'mpro_forms_settings',
				'fmpf_schema_version' => 'mpro_forms_schema_version',
				'fmpf_version'        => 'mpro_forms_version',
				'fmpf_installed_at'   => 'mpro_forms_installed_at',
				'fmpf_show_welcome'   => 'mpro_forms_show_welcome',
			),
			// mpro_forms_ -> form_looq_.
			array(
				'mpro_forms_settings'       => 'form_looq_settings',
				'mpro_forms_schema_version' => 'form_looq_schema_version',
				'mpro_forms_version'        => 'form_looq_version',
				'mpro_forms_installed_at'   => 'form_looq_installed_at',
				'mpro_forms_show_welcome'   => 'form_looq_show_welcome',
			),
		);

		foreach ( $option_generations as $options ) {
			foreach ( $options as $legacy => $current ) {
				$value = get_option( $legacy, null );

				if ( null !== $value && false === get_option( $current, false ) ) {
					update_option( $current, $value, false );
				}

				delete_option( $legacy );
			}
		}

		wp_clear_scheduled_hook( 'fmpf_daily_retention_cleanup' );
		wp_clear_scheduled_hook( 'mpro_forms_daily_cleanup' );

		$role = get_role( 'administrator' );

		if ( $role instanceof \WP_Role ) {
			foreach ( array( 'fmpf_manage_forms', 'mpro_manage_forms' ) as $legacy_cap ) {
				if ( $role->has_cap( $legacy_cap ) ) {
					$role->remove_cap( $legacy_cap );
				}
			}
		}
	}

	/**
	 * Create or update plugin tables with dbDelta.
	 *
	 * dbDelta is additive: existing rows survive plugin deletion and reinstallation
	 * because the tables are never dropped outside of an explicit opt-in uninstall.
	 */
	public static function install_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$forms           = DB::forms_table();
		$entries         = DB::entries_table();

		dbDelta(
			"CREATE TABLE {$forms} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				title varchar(191) NOT NULL DEFAULT '',
				description text NULL,
				status varchar(20) NOT NULL DEFAULT 'active',
				fields longtext NULL,
				settings longtext NULL,
				views bigint(20) unsigned NOT NULL DEFAULT 0,
				entries_count bigint(20) unsigned NOT NULL DEFAULT 0,
				author_id bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY  (id),
				KEY status (status),
				KEY author_id (author_id)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$entries} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				form_id bigint(20) unsigned NOT NULL DEFAULT 0,
				status varchar(20) NOT NULL DEFAULT 'unread',
				data longtext NULL,
				note text NULL,
				ip_hash varchar(64) NOT NULL DEFAULT '',
				user_agent varchar(255) NOT NULL DEFAULT '',
				referer varchar(255) NOT NULL DEFAULT '',
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY  (id),
				KEY form_id_status (form_id,status),
				KEY created_at (created_at),
				KEY user_id (user_id)
			) {$charset_collate};"
		);
	}

	/**
	 * Grant the plugin capability to roles that already administer the site.
	 */
	public static function add_capabilities(): void {
		foreach ( array( 'administrator' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( $role instanceof \WP_Role ) {
				$role->add_cap( Plugin::CAPABILITY );
			}
		}
	}

	public static function installed_at(): int {
		return (int) get_option( self::OPTION_INSTALLED_AT, 0 );
	}
}
