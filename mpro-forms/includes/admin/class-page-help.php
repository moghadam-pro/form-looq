<?php
/**
 * Help screen, populated from the project documentation site.
 *
 * @package MPROForms
 */

namespace MPROForms\Admin;

use MPROForms\Plugin;
use MPROForms\Remote_Content;

defined( 'ABSPATH' ) || exit;

final class Page_Help {
	public static function render(): void {
		Admin::guard();

		$docs = Remote_Content::docs();
		?>
		<div class="wrap mpro-wrap">
			<?php
			Admin::header(
				__( 'Help', 'mpro-forms' ),
				__( 'Guides and reference, loaded from the project documentation.', 'mpro-forms' )
			);
			?>

			<?php if ( 'bundled' === $docs['source'] ) : ?>
				<div class="notice notice-warning inline">
					<p>
						<?php
						printf(
							/* translators: %s: documentation URL. */
							esc_html__( 'Showing the bundled quick reference. The live documentation at %s could not be reached.', 'mpro-forms' ),
							'<code>' . esc_html( Remote_Content::DOCS_URL ) . '</code>'
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<div class="mpro-docs">
				<?php foreach ( $docs['sections'] as $section ) : ?>
					<div class="mpro-docs__section">
						<h2><?php echo esc_html( $section['title'] ); ?></h2>

						<?php if ( '' !== $section['description'] ) : ?>
							<p class="description"><?php echo esc_html( $section['description'] ); ?></p>
						<?php endif; ?>

						<ul class="mpro-docs__list">
							<?php foreach ( $section['articles'] as $article ) : ?>
								<li>
									<?php if ( '' !== $article['url'] ) : ?>
										<a href="<?php echo esc_url( $article['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $article['title'] ); ?></a>
									<?php else : ?>
										<strong><?php echo esc_html( $article['title'] ); ?></strong>
									<?php endif; ?>

									<?php if ( '' !== $article['excerpt'] ) : ?>
										<span class="mpro-docs__excerpt"><?php echo esc_html( $article['excerpt'] ); ?></span>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="mpro-docs__footer">
				<h2><?php esc_html_e( 'More help', 'mpro-forms' ); ?></h2>
				<p>
					<a class="button button-primary" href="<?php echo esc_url( Remote_Content::DOCS_URL ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Open full documentation', 'mpro-forms' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-status' ) ); ?>">
						<?php esc_html_e( 'System status report', 'mpro-forms' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}
}
