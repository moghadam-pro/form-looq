<?php
/**
 * WordPress personal-data export and erasure integration.
 *
 * @package FormLooq
 */

namespace FormLooq;

defined( 'ABSPATH' ) || exit;

final class Privacy_Manager {
	private const EXPORTER_ID = 'form-looq-submissions';
	private const PER_PAGE    = 50;

	public static function init(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( self::class, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( self::class, 'register_eraser' ) );
	}

	/**
	 * @param array<string, mixed> $exporters Registered exporters.
	 * @return array<string, mixed>
	 */
	public static function register_exporter( array $exporters ): array {
		$exporters[ self::EXPORTER_ID ] = array(
			'exporter_friendly_name' => __( 'Form LOOQ submissions', 'form-looq' ),
			'callback'               => array( self::class, 'export_personal_data' ),
		);

		return $exporters;
	}

	/**
	 * @param array<string, mixed> $erasers Registered erasers.
	 * @return array<string, mixed>
	 */
	public static function register_eraser( array $erasers ): array {
		$erasers[ self::EXPORTER_ID ] = array(
			'eraser_friendly_name' => __( 'Form LOOQ submissions', 'form-looq' ),
			'callback'             => array( self::class, 'erase_personal_data' ),
		);

		return $erasers;
	}

	/**
	 * @return array{data: array<int, mixed>, done: bool}
	 */
	public static function export_personal_data( string $email_address, int $page = 1 ): array {
		$email_address = sanitize_email( $email_address );
		$page          = max( 1, absint( $page ) );

		if ( ! $email_address || ! is_email( $email_address ) ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$entries      = self::entry_page( $page );
		$export_items = array();

		foreach ( $entries as $entry ) {
			if ( ! self::entry_matches_email( $entry, $email_address ) ) {
				continue;
			}

			$export_items[] = array(
				'group_id'    => self::EXPORTER_ID,
				'group_label' => __( 'Form submissions', 'form-looq' ),
				'item_id'     => 'looq-entry-' . $entry['id'],
				'data'        => self::export_fields( $entry ),
			);
		}

		return array(
			'data' => $export_items,
			'done' => count( $entries ) < self::PER_PAGE,
		);
	}

	/**
	 * @return array{items_removed: bool, items_retained: bool, messages: array<int, string>, done: bool}
	 */
	public static function erase_personal_data( string $email_address, int $page = 1 ): array {
		global $wpdb;

		$email_address  = sanitize_email( $email_address );
		$page           = max( 1, absint( $page ) );
		$items_removed  = false;
		$items_retained = false;
		$messages       = array();

		if ( ! $email_address || ! is_email( $email_address ) ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$entries = self::entry_page( $page );

		foreach ( $entries as $entry ) {
			if ( ! self::entry_matches_email( $entry, $email_address ) ) {
				continue;
			}

			$updated = $wpdb->update(
				DB::entries_table(),
				array(
					'data'       => DB::encode( array() ),
					'note'       => '',
					'status'     => Entry_Repository::STATUS_READ,
					'ip_hash'    => '',
					'user_agent' => '',
					'referer'    => '',
					// The account link itself identifies the requester; clearing it
					// (rather than just the fields above) is what makes the entry
					// stop being personal data instead of merely anonymised-looking.
					'user_id'    => 0,
				),
				array( 'id' => (int) $entry['id'] ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%d' ),
				array( '%d' )
			);

			if ( false !== $updated ) {
				$items_removed = true;
				continue;
			}

			$items_retained = true;
			$messages[]     = sprintf(
				/* translators: %d: entry ID. */
				__( 'Personal values in submission %d could not be erased.', 'form-looq' ),
				(int) $entry['id']
			);
		}

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => array_values( array_unique( $messages ) ),
			'done'           => count( $entries ) < self::PER_PAGE,
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function entry_page( int $page ): array {
		return Entry_Repository::query(
			array(
				'per_page' => self::PER_PAGE,
				'page'     => $page,
				'orderby'  => 'id',
				'order'    => 'ASC',
			)
		);
	}

	/**
	 * @param array<string, mixed> $entry Hydrated entry row.
	 */
	private static function entry_matches_email( array $entry, string $email_address ): bool {
		foreach ( (array) $entry['data'] as $value ) {
			if ( is_array( $value ) ) {
				continue;
			}

			if ( 0 === strcasecmp( trim( (string) $value ), $email_address ) ) {
				return true;
			}
		}

		if ( (int) $entry['user_id'] > 0 ) {
			$user = get_userdata( (int) $entry['user_id'] );

			if ( $user && 0 === strcasecmp( (string) $user->user_email, $email_address ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array<string, mixed> $entry Hydrated entry row.
	 * @return array<int, array{name: string, value: string}>
	 */
	private static function export_fields( array $entry ): array {
		$form   = Form_Repository::get( (int) $entry['form_id'] );
		$labels = array();

		foreach ( (array) ( $form['fields'] ?? array() ) as $field ) {
			if ( ! empty( $field['name'] ) ) {
				$labels[ $field['name'] ] = (string) $field['label'];
			}
		}

		$items = array(
			array(
				'name'  => __( 'Form', 'form-looq' ),
				'value' => (string) ( $form['title'] ?? __( 'Deleted form', 'form-looq' ) ),
			),
			array(
				'name'  => __( 'Submitted on', 'form-looq' ),
				'value' => (string) $entry['created_at'],
			),
		);

		foreach ( (array) $entry['data'] as $key => $value ) {
			$items[] = array(
				'name'  => $labels[ $key ] ?? (string) $key,
				'value' => is_array( $value ) ? implode( ', ', $value ) : (string) $value,
			);
		}

		if ( '' !== (string) $entry['referer'] ) {
			$items[] = array(
				'name'  => __( 'Referring page', 'form-looq' ),
				'value' => (string) $entry['referer'],
			);
		}

		if ( '' !== (string) $entry['user_agent'] ) {
			$items[] = array(
				'name'  => __( 'Browser user agent', 'form-looq' ),
				'value' => (string) $entry['user_agent'],
			);
		}

		if ( '' !== (string) $entry['ip_hash'] ) {
			$items[] = array(
				'name'  => __( 'IP address', 'form-looq' ),
				'value' => __( 'Stored as a salted hash, not recoverable in plain form.', 'form-looq' ),
			);
		}

		if ( (int) $entry['user_id'] > 0 ) {
			$user = get_userdata( (int) $entry['user_id'] );

			$items[] = array(
				'name'  => __( 'Submitted while signed in as', 'form-looq' ),
				'value' => $user ? (string) $user->user_login : (string) $entry['user_id'],
			);
		}

		if ( '' !== (string) $entry['note'] ) {
			$items[] = array(
				'name'  => __( 'Admin note', 'form-looq' ),
				'value' => (string) $entry['note'],
			);
		}

		return $items;
	}
}
