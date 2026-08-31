<?php
/**
 * CRUD access to plugin-owned form rows.
 *
 * @package MPROForms
 */

namespace MPROForms;

defined( 'ABSPATH' ) || exit;

final class Form_Repository {
	public const STATUS_ACTIVE   = 'active';
	public const STATUS_INACTIVE = 'inactive';
	public const STATUS_DRAFT    = 'draft';

	private const CACHE_GROUP = 'mpro_forms';

	/**
	 * @return array<string, string>
	 */
	public static function statuses(): array {
		return array(
			self::STATUS_ACTIVE   => __( 'Active', 'mpro-forms' ),
			self::STATUS_INACTIVE => __( 'Inactive', 'mpro-forms' ),
			self::STATUS_DRAFT    => __( 'Draft', 'mpro-forms' ),
		);
	}

	public static function normalize_status( string $status ): string {
		return array_key_exists( $status, self::statuses() ) ? $status : self::STATUS_DRAFT;
	}

	public static function get( int $form_id ): ?array {
		global $wpdb;

		if ( $form_id <= 0 ) {
			return null;
		}

		$cached = wp_cache_get( $form_id, self::CACHE_GROUP );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$table = DB::forms_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $form_id ), ARRAY_A );

		if ( ! $row ) {
			return null;
		}

		$form = self::hydrate( $row );
		wp_cache_set( $form_id, $form, self::CACHE_GROUP, HOUR_IN_SECONDS );

		return $form;
	}

	/**
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<int, array<string, mixed>>
	 */
	public static function query( array $args = array() ): array {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'status'   => '',
				'search'   => '',
				'orderby'  => 'id',
				'order'    => 'DESC',
				'per_page' => 20,
				'page'     => 1,
			)
		);

		$table  = DB::forms_table();
		$where  = array( '1=1' );
		$params = array();

		if ( $args['status'] && array_key_exists( $args['status'], self::statuses() ) ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}

		if ( '' !== (string) $args['search'] ) {
			$where[]  = '(title LIKE %s OR description LIKE %s)';
			$like     = '%' . $wpdb->esc_like( (string) $args['search'] ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$orderby  = self::safe_orderby( (string) $args['orderby'] );
		$order    = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
		$per_page = max( 1, min( 200, absint( $args['per_page'] ) ) );
		$offset   = max( 0, ( max( 1, absint( $args['page'] ) ) - 1 ) * $per_page );

		$params[] = $per_page;
		$params[] = $offset;

		$sql = 'SELECT * FROM ' . $table . ' WHERE ' . implode( ' AND ', $where )
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

		$table  = DB::forms_table();
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['status'] ) && array_key_exists( $args['status'], self::statuses() ) ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(title LIKE %s OR description LIKE %s)';
			$like     = '%' . $wpdb->esc_like( (string) $args['search'] ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$sql = 'SELECT COUNT(*) FROM ' . $table . ' WHERE ' . implode( ' AND ', $where );

		if ( $params ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * @param array<string, mixed> $data Form data.
	 */
	public static function create( array $data ): int {
		global $wpdb;

		$now = current_time( 'mysql' );

		$inserted = $wpdb->insert(
			DB::forms_table(),
			array(
				'title'       => self::sanitize_title( (string) ( $data['title'] ?? '' ) ),
				'description' => sanitize_textarea_field( (string) ( $data['description'] ?? '' ) ),
				'status'      => self::normalize_status( (string) ( $data['status'] ?? self::STATUS_ACTIVE ) ),
				'fields'      => DB::encode( Field_Validator::sanitize_fields( (array) ( $data['fields'] ?? array() ) ) ),
				'settings'    => DB::encode( self::sanitize_settings( (array) ( $data['settings'] ?? array() ) ) ),
				'author_id'   => get_current_user_id(),
				'created_at'  => $now,
				'updated_at'  => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return 0;
		}

		$form_id = (int) $wpdb->insert_id;

		/**
		 * Fires immediately after a form is created.
		 *
		 * @param int $form_id New form ID.
		 */
		do_action( 'mpro_forms_form_created', $form_id );

		return $form_id;
	}

	/**
	 * @param array<string, mixed> $data Changed columns.
	 */
	public static function update( int $form_id, array $data ): bool {
		global $wpdb;

		if ( $form_id <= 0 || ! self::get( $form_id ) ) {
			return false;
		}

		$update = array( 'updated_at' => current_time( 'mysql' ) );
		$format = array( '%s' );

		if ( array_key_exists( 'title', $data ) ) {
			$update['title'] = self::sanitize_title( (string) $data['title'] );
			$format[]        = '%s';
		}

		if ( array_key_exists( 'description', $data ) ) {
			$update['description'] = sanitize_textarea_field( (string) $data['description'] );
			$format[]              = '%s';
		}

		if ( array_key_exists( 'status', $data ) ) {
			$update['status'] = self::normalize_status( (string) $data['status'] );
			$format[]         = '%s';
		}

		if ( array_key_exists( 'fields', $data ) ) {
			$update['fields'] = DB::encode( Field_Validator::sanitize_fields( (array) $data['fields'] ) );
			$format[]         = '%s';
		}

		if ( array_key_exists( 'settings', $data ) ) {
			$update['settings'] = DB::encode( self::sanitize_settings( (array) $data['settings'] ) );
			$format[]           = '%s';
		}

		$result = $wpdb->update( DB::forms_table(), $update, array( 'id' => $form_id ), $format, array( '%d' ) );

		self::flush( $form_id );

		/**
		 * Fires after a form row is updated.
		 *
		 * @param int $form_id Updated form ID.
		 */
		do_action( 'mpro_forms_form_updated', $form_id );

		return false !== $result;
	}

	public static function duplicate( int $form_id ): int {
		$form = self::get( $form_id );
		if ( ! $form ) {
			return 0;
		}

		return self::create(
			array(
				/* translators: %s: original form title. */
				'title'       => sprintf( __( '%s (copy)', 'mpro-forms' ), $form['title'] ),
				'description' => $form['description'],
				'status'      => self::STATUS_DRAFT,
				'fields'      => $form['fields'],
				'settings'    => $form['settings'],
			)
		);
	}

	/**
	 * Delete a form and every entry that belongs to it.
	 */
	public static function delete( int $form_id ): bool {
		global $wpdb;

		if ( $form_id <= 0 ) {
			return false;
		}

		$wpdb->delete( DB::entries_table(), array( 'form_id' => $form_id ), array( '%d' ) );
		$deleted = $wpdb->delete( DB::forms_table(), array( 'id' => $form_id ), array( '%d' ) );

		self::flush( $form_id );

		/**
		 * Fires after a form and its entries are deleted.
		 *
		 * @param int $form_id Deleted form ID.
		 */
		do_action( 'mpro_forms_form_deleted', $form_id );

		return (bool) $deleted;
	}

	public static function record_view( int $form_id ): void {
		global $wpdb;

		if ( $form_id <= 0 || ! apply_filters( 'mpro_forms_track_views', true, $form_id ) ) {
			return;
		}

		$table = DB::forms_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET views = views + 1 WHERE id = %d", $form_id ) );

		self::flush( $form_id );
	}

	public static function recount_entries( int $form_id ): void {
		global $wpdb;

		$forms   = DB::forms_table();
		$entries = DB::entries_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$forms} SET entries_count = (SELECT COUNT(*) FROM {$entries} WHERE form_id = %d) WHERE id = %d",
				$form_id,
				$form_id
			)
		);

		self::flush( $form_id );
	}

	/**
	 * @param array<string, mixed> $form Hydrated form row.
	 */
	public static function conversion_rate( array $form ): float {
		$views = (int) ( $form['views'] ?? 0 );
		if ( $views <= 0 ) {
			return 0.0;
		}

		return round( ( (int) ( $form['entries_count'] ?? 0 ) / $views ) * 100, 1 );
	}

	public static function flush( int $form_id ): void {
		wp_cache_delete( $form_id, self::CACHE_GROUP );
	}

	/**
	 * @param array<string, mixed> $row Raw database row.
	 * @return array<string, mixed>
	 */
	private static function hydrate( array $row ): array {
		$row['id']            = (int) $row['id'];
		$row['views']         = (int) $row['views'];
		$row['entries_count'] = (int) $row['entries_count'];
		$row['author_id']     = (int) $row['author_id'];
		$row['fields']        = DB::decode( $row['fields'] ?? '' );
		$row['settings']      = self::sanitize_settings( DB::decode( $row['settings'] ?? '' ) );

		return $row;
	}

	private static function safe_orderby( string $orderby ): string {
		$allowed = array( 'id', 'title', 'status', 'views', 'entries_count', 'created_at', 'updated_at' );

		return in_array( $orderby, $allowed, true ) ? $orderby : 'id';
	}

	private static function sanitize_title( string $title ): string {
		$title = sanitize_text_field( $title );
		$title = '' !== $title ? $title : __( 'Untitled form', 'mpro-forms' );

		return function_exists( 'mb_substr' ) ? mb_substr( $title, 0, 191 ) : substr( $title, 0, 191 );
	}

	/**
	 * @param array<string, mixed> $settings Raw settings.
	 * @return array<string, mixed>
	 */
	public static function sanitize_settings( array $settings ): array {
		$clean = array();

		$clean['submit_label']    = sanitize_text_field( (string) ( $settings['submit_label'] ?? '' ) );
		$clean['success_message'] = sanitize_textarea_field( (string) ( $settings['success_message'] ?? '' ) );
		$clean['layout']          = in_array( ( $settings['layout'] ?? '' ), array( 'one-column', 'two-column' ), true )
			? (string) $settings['layout']
			: 'one-column';
		$clean['store_ip']        = ! empty( $settings['store_ip'] );
		$clean['honeypot']        = ! isset( $settings['honeypot'] ) || ! empty( $settings['honeypot'] );
		$clean['redirect_url']    = esc_url_raw( (string) ( $settings['redirect_url'] ?? '' ) );

		if ( '' === $clean['submit_label'] ) {
			$clean['submit_label'] = __( 'Submit', 'mpro-forms' );
		}

		if ( '' === $clean['success_message'] ) {
			$clean['success_message'] = __( 'Thank you. Your submission has been received.', 'mpro-forms' );
		}

		return $clean;
	}
}
