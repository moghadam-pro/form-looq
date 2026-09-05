<?php
/**
 * Entry export screen.
 *
 * @package FormLooq
 */

namespace FormLooq\Admin;

use FormLooq\Form_Repository;
use FormLooq\Plugin;

defined( 'ABSPATH' ) || exit;

final class Page_Tools {
	public static function render(): void {
		Admin::guard();

		$forms = Form_Repository::query( array( 'per_page' => 200 ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected = isset( $_GET['form'] ) ? absint( $_GET['form'] ) : 0;
		?>
		<div class="wrap looq-wrap">
			<?php
			Admin::header(
				__( 'Export', 'form-looq' ),
				__( "Take a complete copy of a form's entries.", 'form-looq' )
			);
			Admin::render_notice();
			?>

			<div class="looq-cards">
				<div class="looq-card-panel">
					<h2><?php esc_html_e( 'Export entries', 'form-looq' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'Exports every entry of the selected form. This release intentionally offers no filters or column options — the file is a complete, unfiltered copy.', 'form-looq' ); ?>
					</p>

					<?php if ( ! $forms ) : ?>
						<p><?php esc_html_e( 'There are no forms to export yet.', 'form-looq' ); ?></p>
					<?php else : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="<?php echo esc_attr( Exporter::action_name() ); ?>">
							<?php wp_nonce_field( Exporter::action_name() ); ?>

							<p>
								<label for="looq-export-form"><strong><?php esc_html_e( 'Form', 'form-looq' ); ?></strong></label><br>
								<select id="looq-export-form" name="form_id">
									<?php foreach ( $forms as $form ) : ?>
										<option value="<?php echo esc_attr( (string) $form['id'] ); ?>" <?php selected( $selected, (int) $form['id'] ); ?>>
											<?php
											printf(
												/* translators: 1: form title, 2: entry count. */
												esc_html__( '%1$s (%2$s entries)', 'form-looq' ),
												esc_html( (string) $form['title'] ),
												esc_html( number_format_i18n( (int) $form['entries_count'] ) )
											);
											?>
										</option>
									<?php endforeach; ?>
								</select>
							</p>

							<p>
								<strong><?php esc_html_e( 'Format', 'form-looq' ); ?></strong><br>
								<label><input type="radio" name="format" value="csv" checked> <?php esc_html_e( 'CSV — spreadsheet friendly, UTF-8 with BOM', 'form-looq' ); ?></label><br>
								<label><input type="radio" name="format" value="xml"> <?php esc_html_e( 'XML — structured, keeps field names and labels', 'form-looq' ); ?></label>
							</p>

							<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Download export', 'form-looq' ); ?></button></p>
						</form>
					<?php endif; ?>
				</div>
			</div>

			<p class="description">
				<?php
				printf(
					/* translators: %s: privacy tools URL. */
					esc_html__( 'For per-person data requests, use the built-in WordPress privacy tools at %s.', 'form-looq' ),
					'<a href="' . esc_url( admin_url( 'export-personal-data.php' ) ) . '">' . esc_html__( 'Tools → Export Personal Data', 'form-looq' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}
}
