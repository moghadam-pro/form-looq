<?php

namespace FreeMPROForms;

defined( 'ABSPATH' ) || exit;

final class Privacy_Manager {
	private const EXPORTER_ID   = 'free-mpro-forms-submissions';
	private const PER_PAGE      = 50;
	private const META_FIELDS   = '_fmpf_fields';
	private const META_FORM_ID  = '_fmpf_form_id';
	private const META_DATA     = '_fmpf_data';
	private const META_TYPE     = '_fmpf_type';
	private const META_STATUS   = '_fmpf_status';

	public static function init(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( self::class, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( self::class, 'register_eraser' ) );
	}

	public static function register_exporter( array $exporters ): array {
		$exporters[ self::EXPORTER_ID ] = array(
			'exporter_friendly_name' => __( 'Free MPRO Forms submissions', 'free-mpro-forms' ),
			'callback'               => array( self::class, 'export_personal_data' ),
		);

		return $exporters;
	}

	public static function register_eraser( array $erasers ): array {
		$erasers[ self::EXPORTER_ID ] = array(
			'eraser_friendly_name' => __( 'Free MPRO Forms submissions', 'free-mpro-forms' ),
			'callback'             => array( self::class, 'erase_personal_data' ),
		);

		return $erasers;
	}

	public static function export_personal_data( string $email_address, int $page = 1 ): array {
		$email_address = sanitize_email( $email_address );
		$page          = max( 1, absint( $page ) );
		$export_items  = array();

		if ( ! $email_address || ! is_email( $email_address ) ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$query = self::submission_query( $page );
		foreach ( $query->posts as $submission_id ) {
			$submission_id = absint( $submission_id );
			$data          = self::submission_data( $submission_id );

			if ( ! self::submission_matches_email( $submission_id, $data, $email_address ) ) {
				continue;
			}

			$export_items[] = array(
				'group_id'    => self::EXPORTER_ID,
				'group_label' => __( 'Form submissions', 'free-mpro-forms' ),
				'item_id'     => 'fmpf-submission-' . $submission_id,
				'data'        => self::export_fields( $submission_id, $data ),
			);
		}

		return array(
			'data' => $export_items,
			'done' => count( $query->posts ) < self::PER_PAGE,
		);
	}

	public static function erase_personal_data( string $email_address, int $page = 1 ): array {
		$email_address = sanitize_email( $email_address );
		$page          = max( 1, absint( $page ) );
		$items_removed = false;
		$items_retained = false;
		$messages      = array();

		if ( ! $email_address || ! is_email( $email_address ) ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$query = self::submission_query( $page );
		foreach ( $query->posts as $submission_id ) {
			$submission_id = absint( $submission_id );
			$data          = self::submission_data( $submission_id );

			if ( ! self::submission_matches_email( $submission_id, $data, $email_address ) ) {
				continue;
			}

			if ( wp_delete_post( $submission_id, true ) ) {
				$items_removed = true;
			} else {
				$items_retained = true;
				$messages[]     = sprintf(
					/* translators: %d: Submission ID. */
					__( 'Submission %d could not be deleted.', 'free-mpro-forms' ),
					$submission_id
				);
			}
		}

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => array_values( array_unique( $messages ) ),
			'done'           => count( $query->posts ) < self::PER_PAGE,
		);
	}

	private static function submission_query( int $page ): \WP_Query {
		return new \WP_Query(
			array(
				'post_type'              => 'fmpf_submission',
				'post_status'            => 'any',
				'fields'                 => 'ids',
				'posts_per_page'         => self::PER_PAGE,
				'paged'                  => $page,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'cache_results'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
	}

	private static function submission_data( int $submission_id ): array {
		$data = get_post_meta( $submission_id, self::META_DATA, true );
		return is_array( $data ) ? $data : array();
	}

	private static function submission_matches_email( int $submission_id, array $data, string $email_address ): bool {
		$target      = strtolower( $email_address );
		$email_names = self::email_field_names( $submission_id );

		foreach ( $email_names as $field_name ) {
			$value = isset( $data[ $field_name ] ) && is_scalar( $data[ $field_name ] )
				? sanitize_email( (string) $data[ $field_name ] )
				: '';

			if ( $value && strtolower( $value ) === $target ) {
				return true;
			}
		}

		if ( $email_names ) {
			return false;
		}

		foreach ( $data as $key => $value ) {
			if ( ! is_scalar( $value ) || false === strpos( strtolower( (string) $key ), 'email' ) ) {
				continue;
			}

			$value = sanitize_email( (string) $value );
			if ( $value && strtolower( $value ) === $target ) {
				return true;
			}
		}

		return false;
	}

	private static function email_field_names( int $submission_id ): array {
		$form_id = absint( get_post_meta( $submission_id, self::META_FORM_ID, true ) );
		if ( ! $form_id || 'fmpf_form' !== get_post_type( $form_id ) ) {
			return array();
		}

		$fields = Field_Validator::parse_definition( (string) get_post_meta( $form_id, self::META_FIELDS, true ) );
		$names  = array();

		foreach ( $fields as $field ) {
			if ( 'email' === $field['type'] ) {
				$names[] = $field['name'];
			}
		}

		return array_values( array_unique( $names ) );
	}

	private static function export_fields( int $submission_id, array $data ): array {
		$form_id = absint( get_post_meta( $submission_id, self::META_FORM_ID, true ) );
		$labels  = self::field_labels( $form_id );
		$post    = get_post( $submission_id );
		$fields  = array(
			array(
				'name'  => __( 'Form', 'free-mpro-forms' ),
				'value' => $form_id ? get_the_title( $form_id ) : __( 'Deleted form', 'free-mpro-forms' ),
			),
			array(
				'name'  => __( 'Received', 'free-mpro-forms' ),
				'value' => $post ? get_date_from_gmt( $post->post_date_gmt, 'Y-m-d H:i:s' ) : '',
			),
			array(
				'name'  => __( 'Submission type', 'free-mpro-forms' ),
				'value' => (string) get_post_meta( $submission_id, self::META_TYPE, true ),
			),
			array(
				'name'  => __( 'Status', 'free-mpro-forms' ),
				'value' => (string) get_post_meta( $submission_id, self::META_STATUS, true ),
			),
		);

		foreach ( $data as $name => $value ) {
			if ( is_array( $value ) ) {
				$value = implode( ', ', array_map( 'strval', $value ) );
			} elseif ( ! is_scalar( $value ) ) {
				continue;
			}

			$fields[] = array(
				'name'  => $labels[ $name ] ?? ucwords( str_replace( array( '-', '_' ), ' ', (string) $name ) ),
				'value' => (string) $value,
			);
		}

		return $fields;
	}

	private static function field_labels( int $form_id ): array {
		if ( ! $form_id || 'fmpf_form' !== get_post_type( $form_id ) ) {
			return array();
		}

		$fields = Field_Validator::parse_definition( (string) get_post_meta( $form_id, self::META_FIELDS, true ) );
		$labels = array();

		foreach ( $fields as $field ) {
			if ( 'section' !== $field['type'] ) {
				$labels[ $field['name'] ] = $field['label'];
			}
		}

		return $labels;
	}
}
