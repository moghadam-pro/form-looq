<?php
/**
 * Help screen, with a bundled quick reference shown when the optional
 * remote-fetch setting is off or the project site can't be reached.
 *
 * @package FormLooq
 */

namespace FormLooq\Admin;

use FormLooq\Plugin;
use FormLooq\Remote_Content;
use FormLooq\Settings;

defined( 'ABSPATH' ) || exit;

final class Page_Help {
	public static function render(): void {
		Admin::guard();

		$remote_enabled = (bool) Settings::get( 'addons_remote_enabled', false );
		$docs           = $remote_enabled ? Remote_Content::docs() : array( 'source' => 'bundled', 'sections' => array() );
		$sections       = $docs['sections'] ?: self::bundled_sections();
		?>
		<div class="wrap looq-wrap">
			<?php
			Admin::header(
				__( 'Help', 'form-looq' ),
				__( 'A quick reference for building forms and handling entries.', 'form-looq' )
			);
			?>

			<?php if ( $remote_enabled && 'bundled' === $docs['source'] ) : ?>
				<div class="notice notice-warning inline">
					<p>
						<?php
						printf(
							/* translators: %s: documentation URL. */
							esc_html__( 'Showing the bundled quick reference. The live documentation at %s could not be reached.', 'form-looq' ),
							'<code>' . esc_html( Remote_Content::DOCS_URL ) . '</code>'
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<div class="looq-docs">
				<?php foreach ( $sections as $section ) : ?>
					<div class="looq-docs__section">
						<h2><?php echo esc_html( $section['title'] ); ?></h2>

						<?php if ( '' !== ( $section['description'] ?? '' ) ) : ?>
							<p class="description"><?php echo esc_html( $section['description'] ); ?></p>
						<?php endif; ?>

						<ul class="looq-docs__list">
							<?php foreach ( $section['articles'] as $article ) : ?>
								<li>
									<?php if ( '' !== ( $article['url'] ?? '' ) ) : ?>
										<a href="<?php echo esc_url( $article['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $article['title'] ); ?></a>
									<?php else : ?>
										<strong><?php echo esc_html( $article['title'] ); ?></strong>
									<?php endif; ?>

									<?php if ( '' !== ( $article['excerpt'] ?? '' ) ) : ?>
										<span class="looq-docs__excerpt"><?php echo esc_html( $article['excerpt'] ); ?></span>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="looq-docs__footer">
				<h2><?php esc_html_e( 'More help', 'form-looq' ); ?></h2>
				<p>
					<a class="button button-primary" href="<?php echo esc_url( $remote_enabled ? Remote_Content::DOCS_URL : Plugin::HOME_URL ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Visit the plugin website', 'form-looq' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-status' ) ); ?>">
						<?php esc_html_e( 'System status report', 'form-looq' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function bundled_sections(): array {
		return array(
			array(
				'title'       => __( 'Getting started', 'form-looq' ),
				'description' => __( 'Create your first form and place it on a page.', 'form-looq' ),
				'articles'    => array(
					array(
						'title'   => __( 'Creating a form', 'form-looq' ),
						'excerpt' => __( 'Pick a template, name the form, and arrange fields in the builder.', 'form-looq' ),
					),
					array(
						'title'   => __( 'Embedding a form', 'form-looq' ),
						'excerpt' => __( 'Copy the shortcode from the Embed tab into any post, page, or builder.', 'form-looq' ),
					),
				),
			),
			array(
				'title'       => __( 'Entries and privacy', 'form-looq' ),
				'description' => __( 'How submissions are stored, exported, and erased.', 'form-looq' ),
				'articles'    => array(
					array(
						'title'   => __( 'Where entries are stored', 'form-looq' ),
						'excerpt' => __( 'Entries live in dedicated database tables on your own site and are never sent anywhere else.', 'form-looq' ),
					),
					array(
						'title'   => __( 'Retention and erasure', 'form-looq' ),
						'excerpt' => __( 'Set a retention window, and use the WordPress privacy tools for personal data requests.', 'form-looq' ),
					),
				),
			),
		);
	}
}
