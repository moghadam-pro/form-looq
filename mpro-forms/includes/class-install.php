<?php
/**
 * Schema creation and upgrade routine.
 *
 * @package MPROForms
 */

namespace MPROForms;

defined( 'ABSPATH' ) || exit;

final class Install {
	public const SCHEMA_VERSION = 3;

	private const OPTION_SCHEMA  = 'mpro_forms_schema_version';
	private const OPTION_VERSION = 'mpro_forms_version';
	private const OPTION_INSTALLED_AT = 'mpro_forms_installed_at';

	public static function activate(): void {
		self::migrate_legacy_names();
		self::install_tables();
		self::add_capabilities();

		if ( ! get_option( self::OPTION_INSTALLED_AT ) ) {
			add_option( self::OPTION_INSTALLED_AT, time(), '', false );
			// Matches Admin\Onboarding::OPTION_PENDING, which is not loaded on the front end.
			add_option( 'mpro_forms_show_welcome', 1, '', false );
		}

		update_option( self::OPTION_SCHEMA, self::SCHEMA_VERSION, false );
		update_option( self::OPTION_VERSION, MPRO_FORMS_VERSION, false );

		// Only schedules the job when a retention window is actually configured;
		// a zero-day default means "keep forever," so there is nothing to run.
		Settings::sync_cron();
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'mpro_forms_daily_cleanup' );
	}

	public static function maybe_upgrade(): void {
		$stored_version = (string) get_option( self::OPTION_VERSION, '' );

		if ( $stored_version !== MPRO_FORMS_VERSION ) {
			// A site updated by replacing the plugin folder (the common path for
			// a manual ZIP upload) never re-fires register_activation_hook, so an
			// unconditional cron schedule left behind by an older version — like
			// 0.3.2's, which ran regardless of the retention setting — would
			// otherwise survive until something else happened to touch it.
			Settings::sync_cron();
			update_option( self::OPTION_VERSION, MPRO_FORMS_VERSION, false );
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
	 * Carry data over from the pre-rename fmpf_ names.
	 *
	 * The plugin shipped as "Free MPRO Forms" with an fmpf_ prefix before the
	 * rename to MPRO Forms. Nothing was ever publicly released under that name,
	 * but early installs exist, and leaving their tables behind would strand the
	 * data and litter the database. Renaming is cheap and runs at most once,
	 * because the guard only fires while a legacy table is still present.
	 *
	 * This routine can be dropped once no install predates 0.3.0.
	 */
	public static function migrate_legacy_names(): void {
		global $wpdb;

		$pairs = array(
			$wpdb->prefix . 'fmpf_forms'   => DB::forms_table(),
			$wpdb->prefix . 'fmpf_entries' => DB::entries_table(),
		);

		foreach ( $pairs as $legacy => $current ) {
			if ( ! DB::table_exists( $legacy ) || DB::table_exists( $current ) ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "RENAME TABLE `{$legacy}` TO `{$current}`" );
		}

		$options = array(
			'fmpf_settings'        => 'mpro_forms_settings',
			'fmpf_schema_version'  => 'mpro_forms_schema_version',
			'fmpf_version'         => 'mpro_forms_version',
			'fmpf_installed_at'    => 'mpro_forms_installed_at',
			'fmpf_show_welcome'    => 'mpro_forms_show_welcome',
		);

		foreach ( $options as $legacy => $current ) {
			$value = get_option( $legacy, null );

			if ( null !== $value && false === get_option( $current, false ) ) {
				update_option( $current, $value, false );
			}

			delete_option( $legacy );
		}

		wp_clear_scheduled_hook( 'fmpf_daily_retention_cleanup' );

		$role = get_role( 'administrator' );

		if ( $role instanceof \WP_Role && $role->has_cap( 'fmpf_manage_forms' ) ) {
			$role->remove_cap( 'fmpf_manage_forms' );
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
