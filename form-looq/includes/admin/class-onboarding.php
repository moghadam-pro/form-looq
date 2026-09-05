<?php
/**
 * First-run welcome modal.
 *
 * Shows a condensed version of the public landing page the first time an admin
 * opens a plugin screen. Because it runs inside wp-admin, the download call to
 * action is swapped for a documentation link — the plugin is already installed.
 *
 * @package FormLooq
 */

namespace FormLooq\Admin;

use FormLooq\Plugin;

defined( 'ABSPATH' ) || exit;

final class Onboarding {
	public const OPTION_PENDING = 'form_looq_show_welcome';

	private const ACTION = 'looq_dismiss_welcome';

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
		<div class="looq-welcome" role="dialog" aria-modal="true" aria-labelledby="looq-welcome-title">
			<div class="looq-welcome__backdrop"></div>
			<div class="looq-welcome__dialog">
				<a class="looq-welcome__close" href="<?php echo esc_url( $dismiss_url ); ?>" aria-label="<?php esc_attr_e( 'Close', 'form-looq' ); ?>">&times;</a>

				<div class="looq-welcome__hero">
					<img class="looq-welcome__logo" src="<?php echo esc_url( Plugin::logo_url() ); ?>" alt="" width="56" height="56">
					<h1 id="looq-welcome-title"><?php esc_html_e( 'Welcome to Form LOOQ', 'form-looq' ); ?></h1>
					<p><?php esc_html_e( 'Build forms, collect entries, and keep every submission on your own site. Free, with no license key and no external account.', 'form-looq' ); ?></p>
					<p class="looq-welcome__cta">
						<a class="button button-primary button-hero" href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-new' ) ); ?>">
							<?php esc_html_e( 'Create your first form', 'form-looq' ); ?>
						</a>
						<a class="button button-hero" href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-help' ) ); ?>">
							<?php esc_html_e( 'Read the documentation', 'form-looq' ); ?>
						</a>
					</p>
				</div>

				<div class="looq-welcome__features">
					<?php
					$features = array(
						array(
							'icon'  => 'privacy',
							'title' => __( 'Entries stay on your own site', 'form-looq' ),
							'text'  => __( 'Submissions are stored in this site\'s own database tables and are never sent to an external service.', 'form-looq' ),
						),
						array(
							'icon'  => 'welcome-widgets-menus',
							'title' => __( 'Build and manage forms simply', 'form-looq' ),
							'text'  => __( 'Templates, a drag-and-drop builder, an inbox with admin notes, and one-click embedding anywhere.', 'form-looq' ),
						),
						array(
							'icon'  => 'performance',
							'title' => __( 'Light and SEO friendly', 'form-looq' ),
							'text'  => __( 'No frameworks, no build step, and assets load only on pages that actually render a form.', 'form-looq' ),
						),
					);

					foreach ( $features as $feature ) :
						?>
						<div class="looq-welcome__feature">
							<span class="dashicons dashicons-<?php echo esc_attr( $feature['icon'] ); ?>" aria-hidden="true"></span>
							<h2><?php echo esc_html( $feature['title'] ); ?></h2>
							<p><?php echo esc_html( $feature['text'] ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>

				<p class="looq-welcome__footer">
					<a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Skip and go to my forms', 'form-looq' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}
}
