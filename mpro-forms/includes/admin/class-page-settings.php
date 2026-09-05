<?php
/**
 * Tabbed settings screen.
 *
 * @package MPROForms
 */

namespace MPROForms\Admin;

use MPROForms\Plugin;
use MPROForms\Settings;

defined( 'ABSPATH' ) || exit;

final class Page_Settings {
	private const ACTION = 'mpro_save_settings';

	public static function init(): void {
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'handle_save' ) );
	}

	public static function handle_save(): void {
		Admin::guard();
		check_admin_referer( self::ACTION );

		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'general';
		$tab = array_key_exists( $tab, Settings::tabs() ) ? $tab : 'general';

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Settings::sanitize handles every value.
		$input = isset( $_POST['mpro_forms_settings'] ) ? (array) wp_unslash( $_POST['mpro_forms_settings'] ) : array();

		Settings::save( $input );

		wp_safe_redirect(
			Plugin::admin_url(
				Plugin::MENU_SLUG . '-settings',
				array(
					'tab'         => $tab,
					'mpro_notice' => 'settings',
				)
			)
		);
		exit;
	}

	public static function render(): void {
		Admin::guard();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'editor';
		$tabs = Settings::tabs();
		$tab  = array_key_exists( $tab, $tabs ) ? $tab : 'editor';
		?>
		<div class="wrap mpro-wrap">
			<?php
			Admin::header( __( 'Settings', 'mpro-forms' ), __( 'Plugin-wide defaults. Per-form options live in the form builder.', 'mpro-forms' ) );
			Admin::render_notice();
			?>

			<h2 class="nav-tab-wrapper">
				<?php
				foreach ( $tabs as $slug => $label ) {
					printf(
						'<a class="nav-tab %1$s" href="%2$s">%3$s</a>',
						esc_attr( $tab === $slug ? 'nav-tab-active' : '' ),
						esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-settings', array( 'tab' => $slug ) ) ),
						esc_html( $label )
					);
				}
				?>
			</h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
				<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
				<?php wp_nonce_field( self::ACTION ); ?>

				<?php
				$method = 'render_' . $tab . '_tab';

				if ( method_exists( self::class, $method ) ) {
					self::{$method}();
				}
				?>

				<?php if ( 'license' !== $tab ) : ?>
					<?php submit_button( __( 'Save settings', 'mpro-forms' ) ); ?>
				<?php endif; ?>
			</form>
		</div>
		<?php
	}

	private static function render_editor_tab(): void {
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="mpro-default-layout"><?php esc_html_e( 'Default layout', 'mpro-forms' ); ?></label></th>
					<td>
						<select id="mpro-default-layout" name="mpro_forms_settings[default_layout]">
							<option value="one-column" <?php selected( Settings::get( 'default_layout' ), 'one-column' ); ?>><?php esc_html_e( 'Single column', 'mpro-forms' ); ?></option>
							<option value="two-column" <?php selected( Settings::get( 'default_layout' ), 'two-column' ); ?>><?php esc_html_e( 'Two columns', 'mpro-forms' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Applied to newly created forms.', 'mpro-forms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mpro-default-submit"><?php esc_html_e( 'Default submit label', 'mpro-forms' ); ?></label></th>
					<td><input id="mpro-default-submit" class="regular-text" type="text" name="mpro_forms_settings[default_submit_label]" value="<?php echo esc_attr( (string) Settings::get( 'default_submit_label' ) ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Unsaved changes', 'mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="mpro_forms_settings[confirm_before_leaving]" value="0">
							<input type="checkbox" name="mpro_forms_settings[confirm_before_leaving]" value="1" <?php checked( (bool) Settings::get( 'confirm_before_leaving' ) ); ?>>
							<?php esc_html_e( 'Warn before leaving the builder with unsaved changes', 'mpro-forms' ); ?>
						</label>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	private static function render_general_tab(): void {
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Frontend CSS', 'mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="mpro_forms_settings[output_css]" value="0">
							<input type="checkbox" name="mpro_forms_settings[output_css]" value="1" <?php checked( (bool) Settings::get( 'output_css' ) ); ?>>
							<?php esc_html_e( 'Output the bundled form stylesheet', 'mpro-forms' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Turn this off if your theme styles the forms itself.', 'mpro-forms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'No-conflict mode', 'mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="mpro_forms_settings[no_conflict_mode]" value="0">
							<input type="checkbox" name="mpro_forms_settings[no_conflict_mode]" value="1" <?php checked( (bool) Settings::get( 'no_conflict_mode' ) ); ?>>
							<?php esc_html_e( 'Block third-party scripts and styles on plugin admin screens', 'mpro-forms' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Use this when another plugin breaks the form builder.', 'mpro-forms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mpro-currency"><?php esc_html_e( 'Currency', 'mpro-forms' ); ?></label></th>
					<td>
						<input id="mpro-currency" class="small-text code" type="text" maxlength="3" name="mpro_forms_settings[currency]" value="<?php echo esc_attr( (string) Settings::get( 'currency' ) ); ?>">
						<p class="description"><?php esc_html_e( 'Three-letter ISO code, used by payment-related add-ons.', 'mpro-forms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="mpro-retention"><?php esc_html_e( 'Entry retention (days)', 'mpro-forms' ); ?></label></th>
					<td>
						<input id="mpro-retention" class="small-text" type="number" min="0" max="3650" name="mpro_forms_settings[retention_days]" value="<?php echo esc_attr( (string) Settings::get( 'retention_days' ) ); ?>">
						<p class="description"><?php esc_html_e( 'Entries older than this are deleted by a daily job. Zero keeps entries forever.', 'mpro-forms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Uninstall behaviour', 'mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="mpro_forms_settings[delete_data_on_uninstall]" value="0">
							<input type="checkbox" name="mpro_forms_settings[delete_data_on_uninstall]" value="1" <?php checked( (bool) Settings::get( 'delete_data_on_uninstall' ) ); ?>>
							<?php esc_html_e( 'Delete all forms, entries, and plugin tables when the plugin is deleted', 'mpro-forms' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Off by default. Forms and entries live in dedicated database tables, so deleting and reinstalling the plugin keeps your data intact.', 'mpro-forms' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	private static function render_widgets_tab(): void {
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Dashboard widget', 'mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="mpro_forms_settings[dashboard_widget]" value="0">
							<input type="checkbox" name="mpro_forms_settings[dashboard_widget]" value="1" <?php checked( (bool) Settings::get( 'dashboard_widget' ) ); ?>>
							<?php esc_html_e( 'Show form and entry totals on the WordPress dashboard', 'mpro-forms' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Elementor widget', 'mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="mpro_forms_settings[elementor_widget]" value="0">
							<input type="checkbox" name="mpro_forms_settings[elementor_widget]" value="1" <?php checked( (bool) Settings::get( 'elementor_widget' ) ); ?>>
							<?php esc_html_e( 'Register the MPRO Forms widget in the Elementor editor', 'mpro-forms' ); ?>
						</label>
						<p class="description">
							<?php
							echo defined( 'ELEMENTOR_VERSION' )
								? esc_html__( 'Elementor is active on this site.', 'mpro-forms' )
								: esc_html__( 'Elementor is not active, so the widget is not registered.', 'mpro-forms' );
							?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

}
