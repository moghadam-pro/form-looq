<?php
/**
 * CRUD access to plugin-owned entry rows.
 *
 * @package FreeMPROForms
 */

namespace FreeMPROForms;

defined( 'ABSPATH' ) || exit;

final class Entry_Repository {
	public const STATUS_UNREAD = 'unread';
	public const STATUS_READ   = 'read';

	private const MAX_NOTE_LENGTH = 2000;

	/**
	 * @return array<string, string>
	 */
	public static function statuses(): array {
		return array(
			self::STATUS_UNREAD => __( 'Unread', 'free-mpro-forms' ),
			self::STATUS_READ   => __( 'Read', 'free-mpro-forms' ),
		);
	}

	public static function get( int $entry_id ): ?array {
		global $wpdb;

		if ( $entry_id <= 0 ) {
			return null;
		}

		$table = DB::entries_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $entry_id ), ARRAY_A );

		return $row ? self::hydrate( $row ) : null;
	}

	/**
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<int, array<string, mixed>>
	 */
	public static function query( array $args = array() ): array {
		global $wpdb;

		$args  = self::parse_query_args( $args );
		$table = DB::entries_table();
		$clause = self::build_where( $args );

		$orderby  = self::safe_orderby( (string) $args['orderby'] );
		$order    = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
		$per_page = max( 1, min( 500, absint( $args['per_page'] ) ) );
		$offset   = max( 0, ( max( 1, absint( $args['page'] ) ) - 1 ) * $per_page );

		$params   = $clause['params'];
		$params[] = $per_page;
		$params[] = $offset;

		$sql = 'SELECT * FROM ' . $table . ' WHERE ' . $clause['sql']
			. ' ORDER BY ' . $orderby . ' ' . $order . ' LIMIT %d OFFSET %d';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

		return array_map( array( self::class, 'hydrate' ), is_array( $rows ) ? $rows : array() );
	}

	/**
	 * @param array<string, mixed> $args Query arguments.
	 */
	public static function count( array $args = array() ): int {
		global $wpdb;

		$args   = self::parse_query_args( $args );
		$table  = DB::entries_table();
		$clause = self::build_where( $args );

		$sql = 'SELECT COUNT(*) FROM ' . $table . ' WHERE ' . $clause['sql'];

		if ( $clause['params'] ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $clause['params'] ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Store a validated submission.
	 *
	 * @param array<string, mixed> $data    Field name to value map.
	 * @param array<string, mixed> $context Request metadata.
	 */
	public static function create( int $form_id, array $data, array $context = array() ): int {
		global $wpdb;

		$form = Form_Repository::get( $form_id );
		if ( ! $form ) {
			return 0;
		}

		$store_ip = ! empty( $form['settings']['store_ip'] );

		$inserted = $wpdb->insert(
			DB::entries_table(),
			array(
				'form_id'    => $form_id,
				'status'     => self::STATUS_UNREAD,
				'data'       => DB::encode( self::sanitize_data( $data ) ),
				'note'       => '',
				'ip_hash'    => $store_ip ? self::hash_ip( (string) ( $context['ip'] ?? '' ) ) : '',
				'user_agent' => self::limit( sanitize_text_field( (string) ( $context['user_agent'] ?? '' ) ), 255 ),
				'referer'    => self::limit( esc_url_raw( (string) ( $context['referer'] ?? '' ) ), 255 ),
				'user_id'    => get_current_user_id(),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		if ( ! $inserted ) {
			return 0;
		}

		$entry_id = (int) $wpdb->insert_id;
		Form_Repository::recount_entries( $form_id );

		/**
		 * Fires after an entry is stored.
		 *
		 * @param int $entry_id New entry ID.
		 * @param int $form_id  Parent form ID.
		 */
		do_action( 'free_mpro_forms_entry_created', $entry_id, $form_id );

		return $entry_id;
	}

	public static function set_status( int $entry_id, string $status ): bool {
		global $wpdb;

		$status = array_key_exists( $status, self::statuses() ) ? $status : self::STATUS_UNREAD;

		return false !== $wpdb->update(
			DB::entries_table(),
			array( 'status' => $status ),
			array( 'id' => $entry_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	public static function set_note( int $entry_id, string $note ): bool {
		global $wpdb;

		$note = self::limit( sanitize_textarea_field( $note ), self::MAX_NOTE_LENGTH );

		return false !== $wpdb->update(
			DB::entries_table(),
			array( 'note' => $note ),
			array( 'id' => $entry_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	public static function delete( int $entry_id ): bool {
		global $wpdb;

		$entry = self::get( $entry_id );
		if ( ! $entry ) {
			return false;
		}

		$deleted = $wpdb->delete( DB::entries_table(), array( 'id' => $entry_id ), array( '%d' ) );
		Form_Repository::recount_entries( (int) $entry['form_id'] );

		/**
		 * Fires after an entry is deleted.
		 *
		 * @param int $entry_id Deleted entry ID.
		 */
		do_action( 'free_mpro_forms_entry_deleted', $entry_id );

		return (bool) $deleted;
	}

	/**
	 * Delete entries older than the configured retention window.
	 *
	 * @return int Number of deleted rows.
	 */
	public static function purge_older_than( int $days ): int {
		global $wpdb;

		if ( $days <= 0 ) {
			return 0;
		}

		$table  = DB::entries_table();
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff ) );

		if ( $deleted > 0 ) {
			self::recount_all();
		}

		return $deleted;
	}

	public static function recount_all(): void {
		global $wpdb;

		$forms   = DB::forms_table();
		$entries = DB::entries_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "UPDATE {$forms} f SET f.entries_count = (SELECT COUNT(*) FROM {$entries} e WHERE e.form_id = f.id)" );

		// Available since WordPress 6.1 and only on caches that support groups.
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( 'fmpf_forms' );
		}
	}

	public static function total(): int {
		global $wpdb;

		$table = DB::entries_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/**
	 * @param array<string, mixed> $args Raw query arguments.
	 * @return array<string, mixed>
	 */
	private static function parse_query_args( array $args ): array {
		return wp_parse_args(
			$args,
			array(
				'form_id'  => 0,
				'status'   => '',
				'search'   => '',
				'user_id'  => 0,
				'orderby'  => 'id',
				'order'    => 'DESC',
				'per_page' => 20,
				'page'     => 1,
			)
		);
	}

	/**
	 * @param array<string, mixed> $args Parsed query arguments.
	 * @return array{sql: string, params: array<int, mixed>}
	 */
	private static function build_where( array $args ): array {
		global $wpdb;

		$where  = array( '1=1' );
		$params = array();

		if ( absint( $args['form_id'] ) > 0 ) {
			$where[]  = 'form_id = %d';
			$params[] = absint( $args['form_id'] );
		}

		if ( $args['status'] && array_key_exists( $args['status'], self::statuses() ) ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}

		if ( absint( $args['user_id'] ) > 0 ) {
			$where[]  = 'user_id = %d';
			$params[] = absint( $args['user_id'] );
		}

		if ( '' !== (string) $args['search'] ) {
			$where[]  = 'data LIKE %s';
			$params[] = '%' . $wpdb->esc_like( (string) $args['search'] ) . '%';
		}

		return array(
			'sql'    => implode( ' AND ', $where ),
			'params' => $params,
		);
	}

	/**
	 * @param array<string, mixed> $row Raw database row.
	 * @return array<string, mixed>
	 */
	private static function hydrate( array $row ): array {
		$row['id']      = (int) $row['id'];
		$row['form_id'] = (int) $row['form_id'];
		$row['user_id'] = (int) $row['user_id'];
		$row['data']    = DB::decode( $row['data'] ?? '' );

		return $row;
	}

	private static function safe_orderby( string $orderby ): string {
		$allowed = array( 'id', 'form_id', 'status', 'created_at' );

		return in_array( $orderby, $allowed, true ) ? $orderby : 'id';
	}

	/**
	 * @param array<string, mixed> $data Raw values.
	 * @return array<string, mixed>
	 */
	private static function sanitize_data( array $data ): array {
		$clean = array();

		foreach ( array_slice( $data, 0, 100, true ) as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$clean[ $key ] = array_values( array_filter( array_map( 'sanitize_text_field', array_slice( $value, 0, 100 ) ) ) );
				continue;
			}

			$clean[ $key ] = sanitize_textarea_field( (string) $value );
		}

		return $clean;
	}

	/**
	 * Store a salted hash instead of the raw address so entries stay privacy-friendly.
	 */
	private static function hash_ip( string $ip ): string {
		$ip = filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';

		return '' === $ip ? '' : substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 64 );
	}

	private static function limit( string $value, int $length ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $length ) : substr( $value, 0, $length );
	}
}
