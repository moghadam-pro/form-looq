<?php
/**
 * Add-on catalogue screen.
 *
 * @package MPROForms
 */

namespace MPROForms\Admin;

use MPROForms\Plugin;
use MPROForms\Remote_Content;

defined( 'ABSPATH' ) || exit;

final class Page_Addons {
	public static function render(): void {
		Admin::guard();

		$catalogue = Remote_Content::addons();
		?>
		<div class="wrap mpro-wrap">
			<?php
			Admin::header(
				__( 'Add-ons', 'mpro-forms' ),
				__( 'Ready-made form packs that add fields, screens, and settings of their own.', 'mpro-forms' )
			);
			?>

			<div class="notice notice-info inline mpro-addons-banner">
				<p><strong><?php esc_html_e( 'Add-ons are a preview in this release.', 'mpro-forms' ); ?></strong></p>
				<p><?php esc_html_e( 'The catalogue below shows what is being built. Activating an add-on will become available in a following release — nothing here is installable yet.', 'mpro-forms' ); ?></p>
			</div>

			<?php if ( 'bundled' === $catalogue['source'] ) : ?>
				<div class="notice notice-warning inline">
					<p>
						<?php
						printf(
							/* translators: %s: catalogue URL. */
							esc_html__( 'Showing the bundled catalogue. The live list at %s could not be reached, or remote fetching is disabled in Settings → Add-ons.', 'mpro-forms' ),
							'<code>' . esc_html( Remote_Content::ADDONS_URL ) . '</code>'
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<div class="mpro-addon-grid">
				<?php foreach ( $catalogue['addons'] as $addon ) : ?>
					<div class="mpro-addon-card">
						<span class="mpro-addon-card__icon dashicons dashicons-<?php echo esc_attr( $addon['icon'] ); ?>" aria-hidden="true"></span>
						<h3 class="mpro-addon-card__title"><?php echo esc_html( $addon['name'] ); ?></h3>
						<p class="mpro-addon-card__description"><?php echo esc_html( $addon['description'] ); ?></p>

						<?php if ( '' !== $addon['badge'] ) : ?>
							<span class="mpro-addon-card__badge"><?php echo esc_html( $addon['badge'] ); ?></span>
						<?php endif; ?>

						<div class="mpro-addon-card__footer">
							<label class="mpro-toggle is-disabled">
								<input type="checkbox" disabled>
								<span><?php esc_html_e( 'Inactive', 'mpro-forms' ); ?></span>
							</label>

							<?php if ( '' !== $addon['url'] ) : ?>
								<a class="mpro-addon-card__link" href="<?php echo esc_url( $addon['url'] ); ?>" target="_blank" rel="noopener noreferrer">
									<?php esc_html_e( 'Details', 'mpro-forms' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<p class="description">
				<a href="<?php echo esc_url( Remote_Content::ADDONS_URL ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'See the full catalogue on the project site', 'mpro-forms' ); ?>
				</a>
				<?php echo ' — '; ?>
				<a href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-settings', array( 'tab' => 'addons' ) ) ); ?>">
					<?php esc_html_e( 'Add-on settings', 'mpro-forms' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
