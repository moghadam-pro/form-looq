<?php
/**
 * Add-on catalogue screen.
 *
 * @package FormLooq
 */

namespace FormLooq\Admin;

use FormLooq\Plugin;
use FormLooq\Remote_Content;
use FormLooq\Settings;

defined( 'ABSPATH' ) || exit;

final class Page_Addons {
	public static function render(): void {
		Admin::guard();

		$catalogue = Remote_Content::addons();
		?>
		<div class="wrap looq-wrap">
			<?php
			Admin::header(
				__( 'Add-ons', 'form-looq' ),
				__( 'Ready-made form packs that add fields, screens, and settings of their own.', 'form-looq' )
			);
			?>

			<div class="notice notice-info inline looq-addons-banner">
				<p><strong><?php esc_html_e( 'Add-ons are a preview in this release.', 'form-looq' ); ?></strong></p>
				<p><?php esc_html_e( 'The catalogue below shows what is being built. Activating an add-on will become available in a following release — nothing here is installable yet.', 'form-looq' ); ?></p>
			</div>

			<?php if ( ! Settings::get( 'addons_remote_enabled', false ) ) : ?>
				<div class="notice notice-warning inline">
					<p>
						<?php
						printf(
							/* translators: %s: Add-ons settings tab link. */
							esc_html__( 'Showing the bundled catalogue. Fetching the live list from the project site is off — turn it on in %s if you want it.', 'form-looq' ),
							'<a href="' . esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-settings', array( 'tab' => 'addons' ) ) ) . '">' . esc_html__( 'Settings → Add-ons', 'form-looq' ) . '</a>'
						);
						?>
					</p>
				</div>
			<?php elseif ( 'bundled' === $catalogue['source'] ) : ?>
				<div class="notice notice-warning inline">
					<p>
						<?php
						printf(
							/* translators: %s: catalogue URL. */
							esc_html__( 'Showing the bundled catalogue. The live list at %s could not be reached.', 'form-looq' ),
							'<code>' . esc_html( Remote_Content::ADDONS_URL ) . '</code>'
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<div class="looq-addon-grid">
				<?php foreach ( $catalogue['addons'] as $addon ) : ?>
					<div class="looq-addon-card">
						<span class="looq-addon-card__icon dashicons dashicons-<?php echo esc_attr( $addon['icon'] ); ?>" aria-hidden="true"></span>
						<h3 class="looq-addon-card__title"><?php echo esc_html( $addon['name'] ); ?></h3>
						<p class="looq-addon-card__description"><?php echo esc_html( $addon['description'] ); ?></p>

						<?php if ( '' !== $addon['badge'] ) : ?>
							<span class="looq-addon-card__badge"><?php echo esc_html( $addon['badge'] ); ?></span>
						<?php endif; ?>

						<div class="looq-addon-card__footer">
							<label class="looq-toggle is-disabled">
								<input type="checkbox" disabled>
								<span><?php esc_html_e( 'Inactive', 'form-looq' ); ?></span>
							</label>

							<?php if ( '' !== $addon['url'] ) : ?>
								<a class="looq-addon-card__link" href="<?php echo esc_url( $addon['url'] ); ?>" target="_blank" rel="noopener noreferrer">
									<?php esc_html_e( 'Details', 'form-looq' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<p class="description">
				<a href="<?php echo esc_url( Plugin::HOME_URL ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Visit the project site', 'form-looq' ); ?>
				</a>
				<?php echo ' — '; ?>
				<a href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-settings', array( 'tab' => 'addons' ) ) ); ?>">
					<?php esc_html_e( 'Add-on settings', 'form-looq' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
