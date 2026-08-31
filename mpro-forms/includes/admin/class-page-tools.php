<?php
/**
 * Import / Export screen.
 *
 * @package MPROForms
 */

namespace MPROForms\Admin;

use MPROForms\Form_Repository;
use MPROForms\Plugin;

defined( 'ABSPATH' ) || exit;

final class Page_Tools {
	public static function render(): void {
		Admin::guard();

		$forms = Form_Repository::query( array( 'per_page' => 200 ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected = isset( $_GET['form'] ) ? absint( $_GET['form'] ) : 0;
		?>
		<div class="wrap mpro-wrap">
			<?php
			Admin::header(
				__( 'Import / Export', 'mpro-forms' ),
				__( 'Take a complete copy of a form\'s entries, or bring data in.', 'mpro-forms' )
			);
			Admin::render_notice();
			?>

			<div class="mpro-cards">
				<div class="mpro-card-panel">
					<h2><?php esc_html_e( 'Export entries', 'mpro-forms' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'Exports every entry of the selected form. This release intentionally offers no filters or column options — the file is a complete, unfiltered copy.', 'mpro-forms' ); ?>
					</p>

					<?php if ( ! $forms ) : ?>
						<p><?php esc_html_e( 'There are no forms to export yet.', 'mpro-forms' ); ?></p>
					<?php else : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="<?php echo esc_attr( Exporter::action_name() ); ?>">
							<?php wp_nonce_field( Exporter::action_name() ); ?>

							<p>
								<label for="mpro-export-form"><strong><?php esc_html_e( 'Form', 'mpro-forms' ); ?></strong></label><br>
								<select id="mpro-export-form" name="form_id">
									<?php foreach ( $forms as $form ) : ?>
										<option value="<?php echo esc_attr( (string) $form['id'] ); ?>" <?php selected( $selected, (int) $form['id'] ); ?>>
											<?php
											printf(
												/* translators: 1: form title, 2: entry count. */
												esc_html__( '%1$s (%2$s entries)', 'mpro-forms' ),
												esc_html( (string) $form['title'] ),
												esc_html( number_format_i18n( (int) $form['entries_count'] ) )
											);
											?>
										</option>
									<?php endforeach; ?>
								</select>
							</p>

							<p>
								<strong><?php esc_html_e( 'Format', 'mpro-forms' ); ?></strong><br>
								<label><input type="radio" name="format" value="csv" checked> <?php esc_html_e( 'CSV — spreadsheet friendly, UTF-8 with BOM', 'mpro-forms' ); ?></label><br>
								<label><input type="radio" name="format" value="xml"> <?php esc_html_e( 'XML — structured, keeps field names and labels', 'mpro-forms' ); ?></label>
							</p>

							<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Download export', 'mpro-forms' ); ?></button></p>
						</form>
					<?php endif; ?>
				</div>

				<div class="mpro-card-panel is-disabled">
					<h2><?php esc_html_e( 'Import entries', 'mpro-forms' ); ?></h2>

					<div class="notice notice-warning inline">
						<p><strong><?php esc_html_e( 'Import is disabled in this release.', 'mpro-forms' ); ?></strong></p>
						<p>
							<?php esc_html_e( 'Importing has to reconcile field names, option values, and duplicate detection against forms it did not create. Rather than ship a version that silently corrupts entries, import stays off until the export format has been tested against real sites and the safest merge strategy is clear.', 'mpro-forms' ); ?>
						</p>
						<p><?php esc_html_e( 'It will arrive in a following release. Exports produced now will remain importable.', 'mpro-forms' ); ?></p>
					</div>

					<p>
						<label for="mpro-import-file"><strong><?php esc_html_e( 'Import file', 'mpro-forms' ); ?></strong></label><br>
						<input id="mpro-import-file" type="file" disabled>
					</p>
					<p><button type="button" class="button" disabled><?php esc_html_e( 'Run import', 'mpro-forms' ); ?></button></p>
				</div>
			</div>

			<p class="description">
				<?php
				printf(
					/* translators: %s: privacy tools URL. */
					esc_html__( 'For per-person data requests, use the built-in WordPress privacy tools at %s.', 'mpro-forms' ),
					'<a href="' . esc_url( admin_url( 'export-personal-data.php' ) ) . '">' . esc_html__( 'Tools → Export Personal Data', 'mpro-forms' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}
}
