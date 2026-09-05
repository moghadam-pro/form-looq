<?php
/**
 * Full CSV and XML export of a form's entries.
 *
 * @package FormLooq
 */

namespace FormLooq\Admin;

use FormLooq\Entry_Repository;
use FormLooq\Form_Repository;
use FormLooq\Plugin;

defined( 'ABSPATH' ) || exit;

final class Exporter {
	private const ACTION = 'looq_export';
	private const BATCH  = 200;

	/**
	 * Leading characters that spreadsheet applications treat as a formula
	 * prefix. A visitor-supplied value starting with one of these would run
	 * as a formula the moment an admin opens the export in Excel or
	 * LibreOffice (CSV injection / "formula injection").
	 */
	private const FORMULA_PREFIXES = array( '=', '+', '-', '@', "\t", "\r" );

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
			wp_safe_redirect( Plugin::admin_url( Plugin::MENU_SLUG . '-tools', array( 'looq_notice' => 'error' ) ) );
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
			array( __( 'Entry ID', 'form-looq' ), __( 'Submitted on', 'form-looq' ), __( 'Status', 'form-looq' ) ),
			array_values( $columns ),
			array( __( 'Admin note', 'form-looq' ) )
		);

		fputcsv( $handle, $header, ',', '"', '\\' );

		foreach ( self::batches( $form_id ) as $entry ) {
			$row = array( (string) $entry['id'], self::local_date( (string) $entry['created_at'] ), (string) $entry['status'] );

			foreach ( array_keys( $columns ) as $key ) {
				$value = $entry['data'][ $key ] ?? '';
				$row[] = is_array( $value ) ? implode( ' | ', $value ) : (string) $value;
			}

			$row[] = (string) $entry['note'];

			fputcsv( $handle, array_map( array( self::class, 'escape_csv_cell' ), $row ), ',', '"', '\\' );
		}

		fclose( $handle );
	}

	/**
	 * created_at is stored in UTC; convert it to the site's own timezone so
	 * an export shows the same submission time an admin sees on screen.
	 */
	private static function local_date( string $created_at_gmt ): string {
		$timestamp = strtotime( $created_at_gmt );

		return $timestamp ? wp_date( 'Y-m-d H:i:s', $timestamp ) : $created_at_gmt;
	}

	/**
	 * Neutralise a value that a spreadsheet application would otherwise read
	 * as a formula, by prefixing it with an apostrophe. Excel and
	 * LibreOffice both render the apostrophe-prefixed value as plain text.
	 */
	private static function escape_csv_cell( string $value ): string {
		return in_array( substr( $value, 0, 1 ), self::FORMULA_PREFIXES, true ) ? "'" . $value : $value;
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
			echo "\t\t" . '<submitted-on>' . esc_html( self::local_date( (string) $entry['created_at'] ) ) . '</submitted-on>' . "\n";

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
		} while ( count( $entries ) === self::BATCH );
	}

	public static function action_name(): string {
		return self::ACTION;
	}
}
