<?php
/**
 * System status report.
 *
 * @package FormLooq
 */

namespace FormLooq\Admin;

use FormLooq\DB;
use FormLooq\Entry_Repository;
use FormLooq\Form_Repository;
use FormLooq\Plugin;
use FormLooq\Settings;

defined( 'ABSPATH' ) || exit;

final class Page_Status {
	public static function render(): void {
		Admin::guard();
		?>
		<div class="wrap looq-wrap">
			<?php
			Admin::header(
				__( 'System status', 'form-looq' ),
				__( 'Everything support needs to diagnose an issue, in one place.', 'form-looq' )
			);
			?>

			<p class="looq-page-actions">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'update-core.php' ) ); ?>">
					<?php esc_html_e( 'Check for updates', 'form-looq' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( Plugin::HOME_URL ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Plugin website', 'form-looq' ); ?>
				</a>
				<button type="button" class="button" data-looq-copy="looq-status-report">
					<?php esc_html_e( 'Copy report', 'form-looq' ); ?>
				</button>
			</p>

			<?php foreach ( self::report() as $section => $rows ) : ?>
				<h2><?php echo esc_html( $section ); ?></h2>
				<table class="widefat striped looq-status-table">
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

			<h2><?php esc_html_e( 'Plain-text report', 'form-looq' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Paste this into a support request. It includes server and environment details — review it before sharing it with anyone outside your own support channel.', 'form-looq' ); ?>
			</p>
			<textarea id="looq-status-report" class="large-text code" rows="12" readonly><?php echo esc_textarea( self::plain_text_report() ); ?></textarea>
		</div>
		<?php
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	public static function report(): array {
		$uploads = wp_get_upload_dir();

		return array(
			__( 'Form LOOQ', 'form-looq' ) => array(
				__( 'Version', 'form-looq' )                => Plugin::version(),
				__( 'Upload folder permissions', 'form-looq' ) => self::directory_permissions( (string) ( $uploads['basedir'] ?? '' ) ),
				__( 'Output CSS', 'form-looq' )             => self::yes_no( (bool) Settings::get( 'output_css' ) ),
				__( 'Default theme', 'form-looq' )          => self::layout_label( (string) Settings::get( 'default_layout' ) ),
				__( 'No-conflict mode', 'form-looq' )       => self::yes_no( (bool) Settings::get( 'no_conflict_mode' ) ),
				__( 'Currency', 'form-looq' )               => (string) Settings::get( 'currency' ),
				__( 'Style filter', 'form-looq' )           => self::yes_no( (bool) has_filter( 'form_looq_track_views' ) ),
				__( 'Forms', 'form-looq' )                  => number_format_i18n( Form_Repository::count() ),
				__( 'Entries', 'form-looq' )                => number_format_i18n( Entry_Repository::total() ),
			),
			__( 'Database details', 'form-looq' ) => self::database_details(),
			__( 'WordPress environment', 'form-looq' ) => array(
				__( 'Site language', 'form-looq' )        => get_locale(),
				__( 'WordPress environment', 'form-looq' ) => wp_get_environment_type(),
				__( 'Site URL', 'form-looq' )             => home_url( '/' ),
				__( 'REST API base URL', 'form-looq' )    => rest_url(),
				__( 'WordPress version', 'form-looq' )    => get_bloginfo( 'version' ),
				__( 'Memory limit', 'form-looq' )         => defined( 'WP_MEMORY_LIMIT' ) ? (string) WP_MEMORY_LIMIT : __( 'Not set', 'form-looq' ),
				__( 'Debug mode', 'form-looq' )           => self::yes_no( defined( 'WP_DEBUG' ) && WP_DEBUG ),
				__( 'Debug log', 'form-looq' )            => self::yes_no( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ),
				__( 'Script debug mode', 'form-looq' )    => self::yes_no( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ),
				__( 'WordPress cron', 'form-looq' )       => self::yes_no( ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ),
				__( 'Alternate cron', 'form-looq' )       => self::yes_no( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ),
				__( 'Background tasks', 'form-looq' )     => self::next_cron_run(),
				__( 'Multisite', 'form-looq' )            => self::yes_no( is_multisite() ),
			),
			__( 'Server environment', 'form-looq' ) => array(
				__( 'Web server', 'form-looq' )    => self::server_value( 'SERVER_SOFTWARE' ),
				__( 'Port', 'form-looq' )          => self::server_value( 'SERVER_PORT' ),
				__( 'PHP version', 'form-looq' )   => PHP_VERSION,
				__( 'PHP memory limit', 'form-looq' ) => (string) ini_get( 'memory_limit' ),
				__( 'PHP max execution time', 'form-looq' ) => (string) ini_get( 'max_execution_time' ),
				__( 'PHP post max size', 'form-looq' ) => (string) ini_get( 'post_max_size' ),
				__( 'SSL', 'form-looq' )           => self::yes_no( is_ssl() ),
				__( 'cURL', 'form-looq' )          => self::yes_no( function_exists( 'curl_version' ) ),
				__( 'mbstring', 'form-looq' )      => self::yes_no( function_exists( 'mb_substr' ) ),
			),
			__( 'Date and time', 'form-looq' ) => array(
				__( 'Timezone', 'form-looq' )     => wp_timezone_string(),
				__( 'Site time', 'form-looq' )    => current_time( 'mysql' ),
				__( 'UTC time', 'form-looq' )     => gmdate( 'Y-m-d H:i:s' ),
				__( 'Date format', 'form-looq' )  => (string) get_option( 'date_format' ),
				__( 'Time format', 'form-looq' )  => (string) get_option( 'time_format' ),
			),
		);
	}

	/**
	 * @return array<string, string>
	 */
	private static function database_details(): array {
		global $wpdb;

		$details = array(
			__( 'Database management system', 'form-looq' ) => self::database_system(),
			__( 'Version', 'form-looq' )                    => (string) $wpdb->db_version(),
			__( 'Table prefix', 'form-looq' )               => $wpdb->prefix . DB::PREFIX,
			__( 'Character set', 'form-looq' )              => (string) $wpdb->charset,
			__( 'Collation', 'form-looq' )                  => (string) $wpdb->collate,
		);

		foreach ( DB::tables() as $key => $table ) {
			$details[ sprintf(
				/* translators: %s: table identifier. */
				__( 'Table: %s', 'form-looq' ),
				$key
			) ] = DB::table_exists( $table )
				? sprintf( '%s (%s)', $table, __( 'present', 'form-looq' ) )
				: sprintf( '%s (%s)', $table, __( 'missing', 'form-looq' ) );
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
			return __( 'Not found', 'form-looq' );
		}

		$mode = substr( sprintf( '%o', fileperms( $path ) ), -4 );

		return sprintf(
			'%s (%s)',
			$mode,
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Read-only diagnostics for an arbitrary runtime path; no filesystem mutation occurs.
			is_writable( $path ) ? __( 'writable', 'form-looq' ) : __( 'not writable', 'form-looq' )
		);
	}

	private static function next_cron_run(): string {
		$next = wp_next_scheduled( 'form_looq_daily_cleanup' );

		if ( ! $next ) {
			return __( 'Not scheduled', 'form-looq' );
		}

		return wp_date( 'Y-m-d H:i:s', $next );
	}

	private static function layout_label( string $layout ): string {
		return 'two-column' === $layout
			? __( 'Two columns', 'form-looq' )
			: __( 'Single column', 'form-looq' );
	}

	private static function server_value( string $key ): string {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		return isset( $_SERVER[ $key ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) : __( 'Unknown', 'form-looq' );
	}

	private static function yes_no( bool $value ): string {
		return $value ? __( 'Yes', 'form-looq' ) : __( 'No', 'form-looq' );
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
