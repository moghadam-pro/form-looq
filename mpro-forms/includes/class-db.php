<?php
/**
 * Dedicated database table access for MPRO Forms.
 *
 * Data lives in plugin-owned tables so that deactivating, deleting, or
 * reinstalling the plugin never destroys a site's forms or entries.
 *
 * @package MPROForms
 */

namespace MPROForms;

defined( 'ABSPATH' ) || exit;

final class DB {
	/**
	 * Table name suffix shared by every plugin-owned table.
	 */
	public const PREFIX = 'mpro_';

	public static function forms_table(): string {
		global $wpdb;

		return $wpdb->prefix . self::PREFIX . 'forms';
	}

	public static function entries_table(): string {
		global $wpdb;

		return $wpdb->prefix . self::PREFIX . 'entries';
	}

	/**
	 * All plugin-owned tables, keyed by a stable identifier.
	 *
	 * @return array<string, string>
	 */
	public static function tables(): array {
		return array(
			'forms'   => self::forms_table(),
			'entries' => self::entries_table(),
		);
	}

	public static function table_exists( string $table ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
	}

	/**
	 * Decode a JSON column into an array without ever throwing.
	 *
	 * @param mixed $value Raw column value.
	 */
	public static function decode( $value ): array {
		if ( is_array( $value ) ) {
			return $value;
		}

		if ( ! is_string( $value ) || '' === $value ) {
			return array();
		}

		$decoded = json_decode( $value, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	public static function encode( array $value ): string {
		$encoded = wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		return is_string( $encoded ) ? $encoded : '[]';
	}
}
