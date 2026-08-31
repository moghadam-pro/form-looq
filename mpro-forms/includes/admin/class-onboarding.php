<?php
/**
 * First-run welcome modal.
 *
 * Shows a condensed version of the public landing page the first time an admin
 * opens a plugin screen. Because it runs inside wp-admin, the download call to
 * action is swapped for a documentation link — the plugin is already installed.
 *
 * @package MPROForms
 */

namespace MPROForms\Admin;

use MPROForms\Plugin;
use MPROForms\Remote_Content;

defined( 'ABSPATH' ) || exit;

final class Onboarding {
	public const OPTION_PENDING = 'mpro_forms_show_welcome';

	private const ACTION = 'mpro_dismiss_welcome';

	public static function init(): void {
		add_action( 'admin_footer', array( self::class, 'maybe_render' ) );
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'handle_dismiss' ) );
	}

	public static function handle_dismiss(): void {
		Admin::guard();
		check_admin_referer( self::ACTION );

		delete_option( self::OPTION_PENDING );

		wp_safe_redirect( Plugin::admin_url() );
		exit;
	}

	public static function maybe_render(): void {
		if ( ! Admin::is_plugin_screen() || ! Plugin::current_user_can() ) {
			return;
		}

		if ( ! get_option( self::OPTION_PENDING ) ) {
			return;
		}

		$dismiss_url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION ), self::ACTION );
		?>
		<div class="mpro-welcome" role="dialog" aria-modal="true" aria-labelledby="mpro-welcome-title">
			<div class="mpro-welcome__backdrop"></div>
			<div class="mpro-welcome__dialog">
				<a class="mpro-welcome__close" href="<?php echo esc_url( $dismiss_url ); ?>" aria-label="<?php esc_attr_e( 'Close', 'mpro-forms' ); ?>">&times;</a>

				<div class="mpro-welcome__hero">
					<img src="<?php echo esc_url( Plugin::logo_url() ); ?>" alt="" width="56" height="56">
					<h1 id="mpro-welcome-title"><?php esc_html_e( 'Welcome to MPRO Forms', 'mpro-forms' ); ?></h1>
					<p><?php esc_html_e( 'Build forms, collect entries, and keep every submission on your own site. Free, with no license key and no external account.', 'mpro-forms' ); ?></p>
					<p class="mpro-welcome__cta">
						<a class="button button-primary button-hero" href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-new' ) ); ?>">
							<?php esc_html_e( 'Create your first form', 'mpro-forms' ); ?>
						</a>
						<a class="button button-hero" href="<?php echo esc_url( Remote_Content::DOCS_URL ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Read the documentation', 'mpro-forms' ); ?>
						</a>
					</p>
				</div>

				<div class="mpro-welcome__features">
					<?php
					$features = array(
						array(
							'icon'  => 'smartphone',
							'title' => __( 'SMS-ready with phone validation', 'mpro-forms' ),
							'text'  => __( 'Phone fields are validated server side, and gateway credentials are ready for SMS notifications and one-time codes.', 'mpro-forms' ),
						),
						array(
							'icon'  => 'welcome-widgets-menus',
							'title' => __( 'Build and manage forms simply', 'mpro-forms' ),
							'text'  => __( 'Templates, a drag-and-drop builder, an inbox with admin notes, and one-click embedding anywhere.', 'mpro-forms' ),
						),
						array(
							'icon'  => 'performance',
							'title' => __( 'Light and SEO friendly', 'mpro-forms' ),
							'text'  => __( 'No frameworks, no build step, and assets load only on pages that actually render a form.', 'mpro-forms' ),
						),
					);

					foreach ( $features as $feature ) :
						?>
						<div class="mpro-welcome__feature">
							<span class="dashicons dashicons-<?php echo esc_attr( $feature['icon'] ); ?>" aria-hidden="true"></span>
							<h2><?php echo esc_html( $feature['title'] ); ?></h2>
							<p><?php echo esc_html( $feature['text'] ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>

				<p class="mpro-welcome__footer">
					<a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Skip and go to my forms', 'mpro-forms' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}
}
