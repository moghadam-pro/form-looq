<?php
/**
 * System status report.
 *
 * @package FreeMPROForms
 */

namespace FreeMPROForms\Admin;

use FreeMPROForms\DB;
use FreeMPROForms\Entry_Repository;
use FreeMPROForms\Form_Repository;
use FreeMPROForms\Plugin;
use FreeMPROForms\Settings;

defined( 'ABSPATH' ) || exit;

final class Page_Status {
	public static function render(): void {
		Admin::guard();
		?>
		<div class="wrap fmpf-wrap">
			<?php
			Admin::header(
				__( 'System status', 'free-mpro-forms' ),
				__( 'Everything support needs to diagnose an issue, in one place.', 'free-mpro-forms' )
			);
			?>

			<p class="fmpf-page-actions">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'update-core.php' ) ); ?>">
					<?php esc_html_e( 'Check for updates', 'free-mpro-forms' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( Plugin::HOME_URL ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Plugin website', 'free-mpro-forms' ); ?>
				</a>
				<button type="button" class="button" data-fmpf-copy="fmpf-status-report">
					<?php esc_html_e( 'Copy report', 'free-mpro-forms' ); ?>
				</button>
			</p>

			<?php foreach ( self::report() as $section => $rows ) : ?>
				<h2><?php echo esc_html( $section ); ?></h2>
				<table class="widefat striped fmpf-status-table">
					<tbody>
						<?php foreach ( $rows as $label => $value ) : ?>
							<tr>
								<th scope="row" style="width:280px"><?php echo esc_html( (string) $label ); ?></th>
								<td style="word-break:break-all"><?php echo esc_html( (string) $value ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>

			<h2><?php esc_html_e( 'Plain-text report', 'free-mpro-forms' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Paste this into a support request.', 'free-mpro-forms' ); ?></p>
			<textarea id="fmpf-status-report" class="large-text code" rows="12" readonly><?php echo esc_textarea( self::plain_text_report() ); ?></textarea>
		</div>
		<?php
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	public static function report(): array {
		global $wpdb;

		$uploads = wp_get_upload_dir();

		return array(
			__( 'Free MPRO Forms', 'free-mpro-forms' ) => array(
				__( 'Version', 'free-mpro-forms' )                => Plugin::version(),
				__( 'Upload folder', 'free-mpro-forms' )          => (string) ( $uploads['basedir'] ?? '' ),
				__( 'Upload folder permissions', 'free-mpro-forms' ) => self::directory_permissions( (string) ( $uploads['basedir'] ?? '' ) ),
				__( 'Output CSS', 'free-mpro-forms' )             => self::yes_no( (bool) Settings::get( 'output_css' ) ),
				__( 'Default theme', 'free-mpro-forms' )          => self::layout_label( (string) Settings::get( 'default_layout' ) ),
				__( 'No-conflict mode', 'free-mpro-forms' )       => self::yes_no( (bool) Settings::get( 'no_conflict_mode' ) ),
				__( 'Currency', 'free-mpro-forms' )               => (string) Settings::get( 'currency' ),
				__( 'Background notifications', 'free-mpro-forms' ) => self::yes_no( (bool) Settings::get( 'sms_enabled' ) ),
				__( 'Automatic updates', 'free-mpro-forms' )      => self::yes_no( (bool) Settings::get( 'auto_update' ) ),
				__( 'REST API v2', 'free-mpro-forms' )            => self::yes_no( (bool) Settings::get( 'rest_enabled' ) ),
				__( 'Style filter', 'free-mpro-forms' )           => self::yes_no( (bool) has_filter( 'free_mpro_forms_track_views' ) ),
				__( 'Forms', 'free-mpro-forms' )                  => number_format_i18n( Form_Repository::count() ),
				__( 'Entries', 'free-mpro-forms' )                => number_format_i18n( Entry_Repository::total() ),
			),
			__( 'Database details', 'free-mpro-forms' ) => self::database_details(),
			__( 'WordPress environment', 'free-mpro-forms' ) => array(
				__( 'Site language', 'free-mpro-forms' )        => get_locale(),
				__( 'WordPress environment', 'free-mpro-forms' ) => wp_get_environment_type(),
				__( 'Site URL', 'free-mpro-forms' )             => home_url( '/' ),
				__( 'REST API base URL', 'free-mpro-forms' )    => rest_url(),
				__( 'WordPress version', 'free-mpro-forms' )    => get_bloginfo( 'version' ),
				__( 'Memory limit', 'free-mpro-forms' )         => defined( 'WP_MEMORY_LIMIT' ) ? (string) WP_MEMORY_LIMIT : __( 'Not set', 'free-mpro-forms' ),
				__( 'Debug mode', 'free-mpro-forms' )           => self::yes_no( defined( 'WP_DEBUG' ) && WP_DEBUG ),
				__( 'Debug log', 'free-mpro-forms' )            => self::yes_no( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ),
				__( 'Script debug mode', 'free-mpro-forms' )    => self::yes_no( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ),
				__( 'WordPress cron', 'free-mpro-forms' )       => self::yes_no( ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ),
				__( 'Alternate cron', 'free-mpro-forms' )       => self::yes_no( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ),
				__( 'Background tasks', 'free-mpro-forms' )     => self::next_cron_run(),
				__( 'Multisite', 'free-mpro-forms' )            => self::yes_no( is_multisite() ),
			),
			__( 'Server environment', 'free-mpro-forms' ) => array(
				__( 'Web server', 'free-mpro-forms' )    => self::server_value( 'SERVER_SOFTWARE' ),
				__( 'Port', 'free-mpro-forms' )          => self::server_value( 'SERVER_PORT' ),
				__( 'Document root', 'free-mpro-forms' ) => self::server_value( 'DOCUMENT_ROOT' ),
				__( 'PHP version', 'free-mpro-forms' )   => PHP_VERSION,
				__( 'PHP memory limit', 'free-mpro-forms' ) => (string) ini_get( 'memory_limit' ),
				__( 'PHP max execution time', 'free-mpro-forms' ) => (string) ini_get( 'max_execution_time' ),
				__( 'PHP post max size', 'free-mpro-forms' ) => (string) ini_get( 'post_max_size' ),
				__( 'SSL', 'free-mpro-forms' )           => self::yes_no( is_ssl() ),
				__( 'cURL', 'free-mpro-forms' )          => self::yes_no( function_exists( 'curl_version' ) ),
				__( 'mbstring', 'free-mpro-forms' )      => self::yes_no( function_exists( 'mb_substr' ) ),
			),
			__( 'Date and time', 'free-mpro-forms' ) => array(
				__( 'Timezone', 'free-mpro-forms' )     => wp_timezone_string(),
				__( 'Site time', 'free-mpro-forms' )    => current_time( 'mysql' ),
				__( 'UTC time', 'free-mpro-forms' )     => gmdate( 'Y-m-d H:i:s' ),
				__( 'Date format', 'free-mpro-forms' )  => (string) get_option( 'date_format' ),
				__( 'Time format', 'free-mpro-forms' )  => (string) get_option( 'time_format' ),
			),
		);
	}

	/**
	 * @return array<string, string>
	 */
	private static function database_details(): array {
		global $wpdb;

		$details = array(
			__( 'Database management system', 'free-mpro-forms' ) => self::database_system(),
			__( 'Version', 'free-mpro-forms' )                    => (string) $wpdb->db_version(),
			__( 'Table prefix', 'free-mpro-forms' )               => $wpdb->prefix . DB::PREFIX,
			__( 'Character set', 'free-mpro-forms' )              => (string) $wpdb->charset,
			__( 'Collation', 'free-mpro-forms' )                  => (string) $wpdb->collate,
		);

		foreach ( DB::tables() as $key => $table ) {
			$details[ sprintf(
				/* translators: %s: table identifier. */
				__( 'Table: %s', 'free-mpro-forms' ),
				$key
			) ] = DB::table_exists( $table )
				? sprintf( '%s (%s)', $table, __( 'present', 'free-mpro-forms' ) )
				: sprintf( '%s (%s)', $table, __( 'missing', 'free-mpro-forms' ) );
		}

		return $details;
	}

	private static function database_system(): string {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$comment = (string) $wpdb->get_var( 'SELECT @@version_comment' );

		return '' !== $comment ? $comment : 'MySQL';
	}

	private static function directory_permissions( string $path ): string {
		if ( '' === $path || ! is_dir( $path ) ) {
			return __( 'Not found', 'free-mpro-forms' );
		}

		$mode = substr( sprintf( '%o', fileperms( $path ) ), -4 );

		return sprintf(
			'%s (%s)',
			$mode,
			is_writable( $path ) ? __( 'writable', 'free-mpro-forms' ) : __( 'not writable', 'free-mpro-forms' )
		);
	}

	private static function next_cron_run(): string {
		$next = wp_next_scheduled( 'fmpf_daily_retention_cleanup' );

		if ( ! $next ) {
			return __( 'Not scheduled', 'free-mpro-forms' );
		}

		return wp_date( 'Y-m-d H:i:s', $next );
	}

	private static function layout_label( string $layout ): string {
		return 'two-column' === $layout
			? __( 'Two columns', 'free-mpro-forms' )
			: __( 'Single column', 'free-mpro-forms' );
	}

	private static function server_value( string $key ): string {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		return isset( $_SERVER[ $key ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) : __( 'Unknown', 'free-mpro-forms' );
	}

	private static function yes_no( bool $value ): string {
		return $value ? __( 'Yes', 'free-mpro-forms' ) : __( 'No', 'free-mpro-forms' );
	}

	public static function plain_text_report(): string {
		$lines = array();

		foreach ( self::report() as $section => $rows ) {
			$lines[] = '### ' . $section;

			foreach ( $rows as $label => $value ) {
				$lines[] = $label . ': ' . $value;
			}

			$lines[] = '';
		}

		return implode( "\n", $lines );
	}
}
