<?php

namespace FreeMPROForms;

defined( 'ABSPATH' ) || exit;

final class Settings {
	public const OPTION_RETENTION_DAYS      = 'fmpf_retention_days';
	public const OPTION_DELETE_ON_UNINSTALL = 'fmpf_delete_data_on_uninstall';
	public const CRON_HOOK                  = 'fmpf_daily_retention_cleanup';

	private const SETTINGS_GROUP = 'fmpf_settings';
	private const SETTINGS_PAGE  = 'free-mpro-forms-settings';

	public static function init(): void {
		add_action( 'init', array( self::class, 'ensure_schedule' ), 20 );
		add_action( 'admin_init', array( self::class, 'register' ) );
		add_action( 'admin_menu', array( self::class, 'register_page' ), 30 );
		add_action( self::CRON_HOOK, array( self::class, 'cleanup_expired_submissions' ) );
	}

	public static function activate(): void {
		self::ensure_schedule();
	}

	public static function ensure_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	public static function register(): void {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_RETENTION_DAYS,
			array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => array( self::class, 'sanitize_retention_days' ),
			)
		);

		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_DELETE_ON_UNINSTALL,
			array(
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => static fn( mixed $value ): int => empty( $value ) ? 0 : 1,
			)
		);

		add_settings_section(
			'fmpf_data_section',
			__( 'Submission data', 'free-mpro-forms' ),
			array( self::class, 'render_section_intro' ),
			self::SETTINGS_PAGE
		);

		add_settings_field(
			self::OPTION_RETENTION_DAYS,
			__( 'Automatic retention', 'free-mpro-forms' ),
			array( self::class, 'render_retention_field' ),
			self::SETTINGS_PAGE,
			'fmpf_data_section'
		);

		add_settings_field(
			self::OPTION_DELETE_ON_UNINSTALL,
			__( 'Plugin uninstall', 'free-mpro-forms' ),
			array( self::class, 'render_uninstall_field' ),
			self::SETTINGS_PAGE,
			'fmpf_data_section'
		);
	}

	public static function register_page(): void {
		add_submenu_page(
			'free-mpro-forms',
			__( 'Forms Settings', 'free-mpro-forms' ),
			__( 'Settings', 'free-mpro-forms' ),
			'manage_options',
			self::SETTINGS_PAGE,
			array( self::class, 'render_page' )
		);
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Free MPRO Forms Settings', 'free-mpro-forms' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::SETTINGS_PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public static function render_section_intro(): void {
		echo '<p>' . esc_html__( 'Choose how long permanent submissions remain on this WordPress installation. Temporary validation state expires separately after five minutes.', 'free-mpro-forms' ) . '</p>';
	}

	public static function render_retention_field(): void {
		$value   = absint( get_option( self::OPTION_RETENTION_DAYS, 0 ) );
		$options = array(
			0    => __( 'Keep submissions until manually deleted', 'free-mpro-forms' ),
			30   => __( 'Delete after 30 days', 'free-mpro-forms' ),
			60   => __( 'Delete after 60 days', 'free-mpro-forms' ),
			90   => __( 'Delete after 90 days', 'free-mpro-forms' ),
			180  => __( 'Delete after 180 days', 'free-mpro-forms' ),
			365  => __( 'Delete after one year', 'free-mpro-forms' ),
			730  => __( 'Delete after two years', 'free-mpro-forms' ),
			1825 => __( 'Delete after five years', 'free-mpro-forms' ),
		);
		?>
		<select name="<?php echo esc_attr( self::OPTION_RETENTION_DAYS ); ?>">
			<?php foreach ( $options as $days => $label ) : ?>
				<option value="<?php echo esc_attr( (string) $days ); ?>"<?php selected( $value, $days ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Cleanup runs daily. The default is to keep submissions until an administrator deletes them.', 'free-mpro-forms' ); ?></p>
		<?php
	}

	public static function render_uninstall_field(): void {
		$value = (bool) get_option( self::OPTION_DELETE_ON_UNINSTALL, false );
		?>
		<input type="hidden" name="<?php echo esc_attr( self::OPTION_DELETE_ON_UNINSTALL ); ?>" value="0">
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_DELETE_ON_UNINSTALL ); ?>" value="1"<?php checked( $value ); ?>>
			<?php esc_html_e( 'Permanently delete all Free MPRO Forms forms, submissions, settings, and temporary state when the plugin is uninstalled.', 'free-mpro-forms' ); ?>
		</label>
		<p class="description"><strong><?php esc_html_e( 'Warning:', 'free-mpro-forms' ); ?></strong> <?php esc_html_e( 'This cannot be undone. Deactivation never deletes form or submission data.', 'free-mpro-forms' ); ?></p>
		<?php
	}

	public static function sanitize_retention_days( mixed $value ): int {
		$value   = absint( $value );
		$allowed = array( 0, 30, 60, 90, 180, 365, 730, 1825 );

		return in_array( $value, $allowed, true ) ? $value : 0;
	}

	public static function cleanup_expired_submissions(): int {
		$days = absint( get_option( self::OPTION_RETENTION_DAYS, 0 ) );
		if ( 0 === $days ) {
			return 0;
		}

		$deleted = 0;
		$before  = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		do {
			$query = new \WP_Query(
				array(
					'post_type'              => 'fmpf_submission',
					'post_status'            => 'any',
					'fields'                 => 'ids',
					'posts_per_page'         => 100,
					'orderby'                => 'ID',
					'order'                  => 'ASC',
					'no_found_rows'          => true,
					'cache_results'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'date_query'             => array(
						array(
							'column'    => 'post_date_gmt',
							'before'    => $before,
							'inclusive' => true,
						),
					),
				)
			);

			$ids                = array_map( 'absint', $query->posts );
			$deleted_this_batch = 0;

			foreach ( $ids as $submission_id ) {
				if ( wp_delete_post( $submission_id, true ) ) {
					++$deleted;
					++$deleted_this_batch;
				}
			}

			if ( $ids && 0 === $deleted_this_batch ) {
				break;
			}
		} while ( count( $ids ) === 100 );

		return $deleted;
	}
}
