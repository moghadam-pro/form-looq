<?php
/**
 * Full CSV and XML export of a form's entries.
 *
 * @package FreeMPROForms
 */

namespace FreeMPROForms\Admin;

use FreeMPROForms\Entry_Repository;
use FreeMPROForms\Form_Repository;
use FreeMPROForms\Plugin;

defined( 'ABSPATH' ) || exit;

final class Exporter {
	private const ACTION    = 'fmpf_export';
	private const BATCH     = 200;
	private const MAX_BATCH = 500;

	public static function init(): void {
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'handle_export' ) );
	}

	public static function handle_export(): void {
		Admin::guard();
		check_admin_referer( self::ACTION );

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$format  = isset( $_POST['format'] ) ? sanitize_key( wp_unslash( $_POST['format'] ) ) : 'csv';
		$format  = in_array( $format, array( 'csv', 'xml' ), true ) ? $format : 'csv';

		$form = Form_Repository::get( $form_id );

		if ( ! $form ) {
			wp_safe_redirect( Plugin::admin_url( Plugin::MENU_SLUG . '-tools', array( 'fmpf_notice' => 'error' ) ) );
			exit;
		}

		$columns  = self::columns( $form );
		$filename = sanitize_file_name( 'entries-' . $form_id . '-' . gmdate( 'Ymd-His' ) . '.' . $format );

		nocache_headers();
		header( 'Content-Type: ' . ( 'csv' === $format ? 'text/csv' : 'application/xml' ) . '; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		if ( 'csv' === $format ) {
			self::stream_csv( $form_id, $columns );
		} else {
			self::stream_xml( $form, $columns );
		}

		exit;
	}

	/**
	 * Column keys and headers, derived from the form definition.
	 *
	 * @param array<string, mixed> $form Hydrated form row.
	 * @return array<string, string>
	 */
	private static function columns( array $form ): array {
		$columns = array();

		foreach ( (array) $form['fields'] as $field ) {
			if ( 'section' === $field['type'] || '' === (string) $field['name'] ) {
				continue;
			}

			$columns[ $field['name'] ] = (string) $field['label'];
		}

		return $columns;
	}

	/**
	 * @param array<string, string> $columns Field key to label map.
	 */
	private static function stream_csv( int $form_id, array $columns ): void {
		$handle = fopen( 'php://output', 'w' );

		if ( ! $handle ) {
			return;
		}

		// UTF-8 BOM so Excel opens Persian and Arabic text correctly.
		echo "\xEF\xBB\xBF";

		$header = array_merge(
			array( __( 'Entry ID', 'free-mpro-forms' ), __( 'Submitted on', 'free-mpro-forms' ), __( 'Status', 'free-mpro-forms' ) ),
			array_values( $columns ),
			array( __( 'Admin note', 'free-mpro-forms' ) )
		);

		fputcsv( $handle, $header );

		foreach ( self::batches( $form_id ) as $entry ) {
			$row = array( (string) $entry['id'], (string) $entry['created_at'], (string) $entry['status'] );

			foreach ( array_keys( $columns ) as $key ) {
				$value = $entry['data'][ $key ] ?? '';
				$row[] = is_array( $value ) ? implode( ' | ', $value ) : (string) $value;
			}

			$row[] = (string) $entry['note'];

			fputcsv( $handle, $row );
		}

		fclose( $handle );
	}

	/**
	 * @param array<string, mixed>  $form    Hydrated form row.
	 * @param array<string, string> $columns Field key to label map.
	 */
	private static function stream_xml( array $form, array $columns ): void {
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<entries form-id="' . esc_attr( (string) $form['id'] ) . '" form-title="' . esc_attr( (string) $form['title'] ) . '" exported-at="' . esc_attr( gmdate( 'c' ) ) . '">' . "\n";

		foreach ( self::batches( (int) $form['id'] ) as $entry ) {
			echo "\t" . '<entry id="' . esc_attr( (string) $entry['id'] ) . '" status="' . esc_attr( (string) $entry['status'] ) . '">' . "\n";
			echo "\t\t" . '<submitted-on>' . esc_html( (string) $entry['created_at'] ) . '</submitted-on>' . "\n";

			foreach ( $columns as $key => $label ) {
				$value = $entry['data'][ $key ] ?? '';
				$value = is_array( $value ) ? implode( ' | ', $value ) : (string) $value;

				echo "\t\t" . '<field name="' . esc_attr( $key ) . '" label="' . esc_attr( $label ) . '">'
					. esc_html( $value ) . '</field>' . "\n";
			}

			if ( '' !== (string) $entry['note'] ) {
				echo "\t\t" . '<note>' . esc_html( (string) $entry['note'] ) . '</note>' . "\n";
			}

			echo "\t" . '</entry>' . "\n";
		}

		echo '</entries>' . "\n";
	}

	/**
	 * Yield entries in pages so large forms do not exhaust memory.
	 *
	 * @return \Generator<int, array<string, mixed>>
	 */
	private static function batches( int $form_id ): \Generator {
		$page = 1;

		do {
			$entries = Entry_Repository::query(
				array(
					'form_id'  => $form_id,
					'per_page' => self::BATCH,
					'page'     => $page,
					'orderby'  => 'id',
					'order'    => 'ASC',
				)
			);

			foreach ( $entries as $entry ) {
				yield $entry;
			}

			++$page;
		} while ( count( $entries ) === self::BATCH && $page <= self::MAX_BATCH );
	}

	public static function action_name(): string {
		return self::ACTION;
	}
}
