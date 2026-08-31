<?php
/**
 * First-run welcome modal.
 *
 * Shows a condensed version of the public landing page the first time an admin
 * opens a plugin screen. Because it runs inside wp-admin, the download call to
 * action is swapped for a documentation link — the plugin is already installed.
 *
 * @package FreeMPROForms
 */

namespace FreeMPROForms\Admin;

use FreeMPROForms\Plugin;
use FreeMPROForms\Remote_Content;

defined( 'ABSPATH' ) || exit;

final class Onboarding {
	public const OPTION_PENDING = 'fmpf_show_welcome';

	private const ACTION = 'fmpf_dismiss_welcome';

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
		<div class="fmpf-welcome" role="dialog" aria-modal="true" aria-labelledby="fmpf-welcome-title">
			<div class="fmpf-welcome__backdrop"></div>
			<div class="fmpf-welcome__dialog">
				<a class="fmpf-welcome__close" href="<?php echo esc_url( $dismiss_url ); ?>" aria-label="<?php esc_attr_e( 'Close', 'free-mpro-forms' ); ?>">&times;</a>

				<div class="fmpf-welcome__hero">
					<img src="<?php echo esc_url( Plugin::logo_url() ); ?>" alt="" width="56" height="56">
					<h1 id="fmpf-welcome-title"><?php esc_html_e( 'Welcome to Free MPRO Forms', 'free-mpro-forms' ); ?></h1>
					<p><?php esc_html_e( 'Build forms, collect entries, and keep every submission on your own site. Free, with no license key and no external account.', 'free-mpro-forms' ); ?></p>
					<p class="fmpf-welcome__cta">
						<a class="button button-primary button-hero" href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-new' ) ); ?>">
							<?php esc_html_e( 'Create your first form', 'free-mpro-forms' ); ?>
						</a>
						<a class="button button-hero" href="<?php echo esc_url( Remote_Content::DOCS_URL ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Read the documentation', 'free-mpro-forms' ); ?>
						</a>
					</p>
				</div>

				<div class="fmpf-welcome__features">
					<?php
					$features = array(
						array(
							'icon'  => 'smartphone',
							'title' => __( 'SMS-ready with phone validation', 'free-mpro-forms' ),
							'text'  => __( 'Phone fields are validated server side, and gateway credentials are ready for SMS notifications and one-time codes.', 'free-mpro-forms' ),
						),
						array(
							'icon'  => 'welcome-widgets-menus',
							'title' => __( 'Build and manage forms simply', 'free-mpro-forms' ),
							'text'  => __( 'Templates, a drag-and-drop builder, an inbox with admin notes, and one-click embedding anywhere.', 'free-mpro-forms' ),
						),
						array(
							'icon'  => 'performance',
							'title' => __( 'Light and SEO friendly', 'free-mpro-forms' ),
							'text'  => __( 'No frameworks, no build step, and assets load only on pages that actually render a form.', 'free-mpro-forms' ),
						),
					);

					foreach ( $features as $feature ) :
						?>
						<div class="fmpf-welcome__feature">
							<span class="dashicons dashicons-<?php echo esc_attr( $feature['icon'] ); ?>" aria-hidden="true"></span>
							<h2><?php echo esc_html( $feature['title'] ); ?></h2>
							<p><?php echo esc_html( $feature['text'] ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>

				<p class="fmpf-welcome__footer">
					<a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Skip and go to my forms', 'free-mpro-forms' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}
}
