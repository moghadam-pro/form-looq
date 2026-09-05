<?php
/**
 * System status report.
 *
 * @package MPROForms
 */

namespace MPROForms\Admin;

use MPROForms\DB;
use MPROForms\Entry_Repository;
use MPROForms\Form_Repository;
use MPROForms\Plugin;
use MPROForms\Settings;

defined( 'ABSPATH' ) || exit;

final class Page_Status {
	public static function render(): void {
		Admin::guard();
		?>
		<div class="wrap mpro-wrap">
			<?php
			Admin::header(
				__( 'System status', 'mpro-forms' ),
				__( 'Everything support needs to diagnose an issue, in one place.', 'mpro-forms' )
			);
			?>

			<p class="mpro-page-actions">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'update-core.php' ) ); ?>">
					<?php esc_html_e( 'Check for updates', 'mpro-forms' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( Plugin::HOME_URL ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Plugin website', 'mpro-forms' ); ?>
				</a>
				<button type="button" class="button" data-mpro-copy="mpro-status-report">
					<?php esc_html_e( 'Copy report', 'mpro-forms' ); ?>
				</button>
			</p>

			<?php foreach ( self::report() as $section => $rows ) : ?>
				<h2><?php echo esc_html( $section ); ?></h2>
				<table class="widefat striped mpro-status-table">
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

			<h2><?php esc_html_e( 'Plain-text report', 'mpro-forms' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Paste this into a support request. It includes server and environment details — review it before sharing it with anyone outside your own support channel.', 'mpro-forms' ); ?>
			</p>
			<textarea id="mpro-status-report" class="large-text code" rows="12" readonly><?php echo esc_textarea( self::plain_text_report() ); ?></textarea>
		</div>
		<?php
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	public static function report(): array {
		$uploads = wp_get_upload_dir();

		return array(
			__( 'MPRO Forms', 'mpro-forms' ) => array(
				__( 'Version', 'mpro-forms' )                => Plugin::version(),
				__( 'Upload folder permissions', 'mpro-forms' ) => self::directory_permissions( (string) ( $uploads['basedir'] ?? '' ) ),
				__( 'Output CSS', 'mpro-forms' )             => self::yes_no( (bool) Settings::get( 'output_css' ) ),
				__( 'Default theme', 'mpro-forms' )          => self::layout_label( (string) Settings::get( 'default_layout' ) ),
				__( 'No-conflict mode', 'mpro-forms' )       => self::yes_no( (bool) Settings::get( 'no_conflict_mode' ) ),
				__( 'Currency', 'mpro-forms' )               => (string) Settings::get( 'currency' ),
				__( 'Style filter', 'mpro-forms' )           => self::yes_no( (bool) has_filter( 'mpro_forms_track_views' ) ),
				__( 'Forms', 'mpro-forms' )                  => number_format_i18n( Form_Repository::count() ),
				__( 'Entries', 'mpro-forms' )                => number_format_i18n( Entry_Repository::total() ),
			),
			__( 'Database details', 'mpro-forms' ) => self::database_details(),
			__( 'WordPress environment', 'mpro-forms' ) => array(
				__( 'Site language', 'mpro-forms' )        => get_locale(),
				__( 'WordPress environment', 'mpro-forms' ) => wp_get_environment_type(),
				__( 'Site URL', 'mpro-forms' )             => home_url( '/' ),
				__( 'REST API base URL', 'mpro-forms' )    => rest_url(),
				__( 'WordPress version', 'mpro-forms' )    => get_bloginfo( 'version' ),
				__( 'Memory limit', 'mpro-forms' )         => defined( 'WP_MEMORY_LIMIT' ) ? (string) WP_MEMORY_LIMIT : __( 'Not set', 'mpro-forms' ),
				__( 'Debug mode', 'mpro-forms' )           => self::yes_no( defined( 'WP_DEBUG' ) && WP_DEBUG ),
				__( 'Debug log', 'mpro-forms' )            => self::yes_no( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ),
				__( 'Script debug mode', 'mpro-forms' )    => self::yes_no( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ),
				__( 'WordPress cron', 'mpro-forms' )       => self::yes_no( ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ),
				__( 'Alternate cron', 'mpro-forms' )       => self::yes_no( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ),
				__( 'Background tasks', 'mpro-forms' )     => self::next_cron_run(),
				__( 'Multisite', 'mpro-forms' )            => self::yes_no( is_multisite() ),
			),
			__( 'Server environment', 'mpro-forms' ) => array(
				__( 'Web server', 'mpro-forms' )    => self::server_value( 'SERVER_SOFTWARE' ),
				__( 'Port', 'mpro-forms' )          => self::server_value( 'SERVER_PORT' ),
				__( 'PHP version', 'mpro-forms' )   => PHP_VERSION,
				__( 'PHP memory limit', 'mpro-forms' ) => (string) ini_get( 'memory_limit' ),
				__( 'PHP max execution time', 'mpro-forms' ) => (string) ini_get( 'max_execution_time' ),
				__( 'PHP post max size', 'mpro-forms' ) => (string) ini_get( 'post_max_size' ),
				__( 'SSL', 'mpro-forms' )           => self::yes_no( is_ssl() ),
				__( 'cURL', 'mpro-forms' )          => self::yes_no( function_exists( 'curl_version' ) ),
				__( 'mbstring', 'mpro-forms' )      => self::yes_no( function_exists( 'mb_substr' ) ),
			),
			__( 'Date and time', 'mpro-forms' ) => array(
				__( 'Timezone', 'mpro-forms' )     => wp_timezone_string(),
				__( 'Site time', 'mpro-forms' )    => current_time( 'mysql' ),
				__( 'UTC time', 'mpro-forms' )     => gmdate( 'Y-m-d H:i:s' ),
				__( 'Date format', 'mpro-forms' )  => (string) get_option( 'date_format' ),
				__( 'Time format', 'mpro-forms' )  => (string) get_option( 'time_format' ),
			),
		);
	}

	/**
	 * @return array<string, string>
	 */
	private static function database_details(): array {
		global $wpdb;

		$details = array(
			__( 'Database management system', 'mpro-forms' ) => self::database_system(),
			__( 'Version', 'mpro-forms' )                    => (string) $wpdb->db_version(),
			__( 'Table prefix', 'mpro-forms' )               => $wpdb->prefix . DB::PREFIX,
			__( 'Character set', 'mpro-forms' )              => (string) $wpdb->charset,
			__( 'Collation', 'mpro-forms' )                  => (string) $wpdb->collate,
		);

		foreach ( DB::tables() as $key => $table ) {
			$details[ sprintf(
				/* translators: %s: table identifier. */
				__( 'Table: %s', 'mpro-forms' ),
				$key
			) ] = DB::table_exists( $table )
				? sprintf( '%s (%s)', $table, __( 'present', 'mpro-forms' ) )
				: sprintf( '%s (%s)', $table, __( 'missing', 'mpro-forms' ) );
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
			return __( 'Not found', 'mpro-forms' );
		}

		$mode = substr( sprintf( '%o', fileperms( $path ) ), -4 );

		return sprintf(
			'%s (%s)',
			$mode,
			is_writable( $path ) ? __( 'writable', 'mpro-forms' ) : __( 'not writable', 'mpro-forms' )
		);
	}

	private static function next_cron_run(): string {
		$next = wp_next_scheduled( 'mpro_forms_daily_cleanup' );

		if ( ! $next ) {
			return __( 'Not scheduled', 'mpro-forms' );
		}

		return wp_date( 'Y-m-d H:i:s', $next );
	}

	private static function layout_label( string $layout ): string {
		return 'two-column' === $layout
			? __( 'Two columns', 'mpro-forms' )
			: __( 'Single column', 'mpro-forms' );
	}

	private static function server_value( string $key ): string {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		return isset( $_SERVER[ $key ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) : __( 'Unknown', 'mpro-forms' );
	}

	private static function yes_no( bool $value ): string {
		return $value ? __( 'Yes', 'mpro-forms' ) : __( 'No', 'mpro-forms' );
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
