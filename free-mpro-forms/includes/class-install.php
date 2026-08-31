<?php
/**
 * Schema creation and upgrade routine.
 *
 * @package FreeMPROForms
 */

namespace FreeMPROForms;

defined( 'ABSPATH' ) || exit;

final class Install {
	public const SCHEMA_VERSION = 2;

	private const OPTION_SCHEMA  = 'fmpf_schema_version';
	private const OPTION_VERSION = 'fmpf_version';
	private const OPTION_INSTALLED_AT = 'fmpf_installed_at';

	public static function activate(): void {
		self::install_tables();
		self::add_capabilities();

		if ( ! get_option( self::OPTION_INSTALLED_AT ) ) {
			add_option( self::OPTION_INSTALLED_AT, time(), '', false );
			// Matches Admin\Onboarding::OPTION_PENDING, which is not loaded on the front end.
			add_option( 'fmpf_show_welcome', 1, '', false );
		}

		update_option( self::OPTION_SCHEMA, self::SCHEMA_VERSION, false );
		update_option( self::OPTION_VERSION, FREE_MPRO_FORMS_VERSION, false );

		if ( ! wp_next_scheduled( 'fmpf_daily_retention_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'fmpf_daily_retention_cleanup' );
		}
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'fmpf_daily_retention_cleanup' );
	}

	public static function maybe_upgrade(): void {
		if ( (int) get_option( self::OPTION_SCHEMA, 0 ) === self::SCHEMA_VERSION ) {
			return;
		}

		self::install_tables();
		self::add_capabilities();
		update_option( self::OPTION_SCHEMA, self::SCHEMA_VERSION, false );
		update_option( self::OPTION_VERSION, FREE_MPRO_FORMS_VERSION, false );
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
