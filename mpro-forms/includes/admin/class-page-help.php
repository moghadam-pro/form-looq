<?php
/**
 * Help screen with a bundled quick reference.
 *
 * Earlier releases fetched this content from the project website. That made
 * every visit to this screen an outbound request carrying the site's URL, for
 * a catalogue that was not guaranteed to exist yet. The content now ships
 * with the plugin instead, and the only outbound link is a plain click the
 * admin chooses to make.
 *
 * @package MPROForms
 */

namespace MPROForms\Admin;

use MPROForms\Plugin;

defined( 'ABSPATH' ) || exit;

final class Page_Help {
	public static function render(): void {
		Admin::guard();
		?>
		<div class="wrap mpro-wrap">
			<?php
			Admin::header(
				__( 'Help', 'mpro-forms' ),
				__( 'A quick reference for building forms and handling entries.', 'mpro-forms' )
			);
			?>

			<div class="mpro-docs">
				<?php foreach ( self::sections() as $section ) : ?>
					<div class="mpro-docs__section">
						<h2><?php echo esc_html( $section['title'] ); ?></h2>
						<p class="description"><?php echo esc_html( $section['description'] ); ?></p>

						<ul class="mpro-docs__list">
							<?php foreach ( $section['articles'] as $article ) : ?>
								<li>
									<strong><?php echo esc_html( $article['title'] ); ?></strong>
									<span class="mpro-docs__excerpt"><?php echo esc_html( $article['excerpt'] ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="mpro-docs__footer">
				<h2><?php esc_html_e( 'More help', 'mpro-forms' ); ?></h2>
				<p>
					<a class="button button-primary" href="<?php echo esc_url( Plugin::HOME_URL ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Visit the plugin website', 'mpro-forms' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-status' ) ); ?>">
						<?php esc_html_e( 'System status report', 'mpro-forms' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function sections(): array {
		return array(
			array(
				'title'       => __( 'Getting started', 'mpro-forms' ),
				'description' => __( 'Create your first form and place it on a page.', 'mpro-forms' ),
				'articles'    => array(
					array(
						'title'   => __( 'Creating a form', 'mpro-forms' ),
						'excerpt' => __( 'Pick a template, name the form, and arrange fields in the builder.', 'mpro-forms' ),
					),
					array(
						'title'   => __( 'Embedding a form', 'mpro-forms' ),
						'excerpt' => __( 'Copy the shortcode from the Embed tab into any post, page, or builder.', 'mpro-forms' ),
					),
				),
			),
			array(
				'title'       => __( 'Entries and privacy', 'mpro-forms' ),
				'description' => __( 'How submissions are stored, exported, and erased.', 'mpro-forms' ),
				'articles'    => array(
					array(
						'title'   => __( 'Where entries are stored', 'mpro-forms' ),
						'excerpt' => __( 'Entries live in dedicated database tables on your own site and are never sent anywhere else.', 'mpro-forms' ),
					),
					array(
						'title'   => __( 'Retention and erasure', 'mpro-forms' ),
						'excerpt' => __( 'Set a retention window, and use the WordPress privacy tools for personal data requests.', 'mpro-forms' ),
					),
				),
			),
		);
	}
}
