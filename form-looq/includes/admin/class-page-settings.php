<?php
/**
 * Tabbed settings screen.
 *
 * @package FormLooq
 */

namespace FormLooq\Admin;

use FormLooq\Plugin;
use FormLooq\Settings;

defined( 'ABSPATH' ) || exit;

final class Page_Settings {
	private const ACTION = 'looq_save_settings';

	public static function init(): void {
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'handle_save' ) );
	}

	public static function handle_save(): void {
		Admin::guard();
		check_admin_referer( self::ACTION );

		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'general';
		$tab = array_key_exists( $tab, Settings::tabs() ) ? $tab : 'general';

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Settings::sanitize handles every value.
		$input = isset( $_POST['form_looq_settings'] ) ? (array) wp_unslash( $_POST['form_looq_settings'] ) : array();

		Settings::save( $input );

		wp_safe_redirect(
			Plugin::admin_url(
				Plugin::MENU_SLUG . '-settings',
				array(
					'tab'         => $tab,
					'looq_notice' => 'settings',
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
		<div class="wrap looq-wrap">
			<?php
			Admin::header( __( 'Settings', 'form-looq' ), __( 'Plugin-wide defaults. Per-form options live in the form builder.', 'form-looq' ) );
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

				<?php submit_button( __( 'Save settings', 'form-looq' ) ); ?>
			</form>
		</div>
		<?php
	}

	private static function render_editor_tab(): void {
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="looq-default-layout"><?php esc_html_e( 'Default layout', 'form-looq' ); ?></label></th>
					<td>
						<select id="looq-default-layout" name="form_looq_settings[default_layout]">
							<option value="one-column" <?php selected( Settings::get( 'default_layout' ), 'one-column' ); ?>><?php esc_html_e( 'Single column', 'form-looq' ); ?></option>
							<option value="two-column" <?php selected( Settings::get( 'default_layout' ), 'two-column' ); ?>><?php esc_html_e( 'Two columns', 'form-looq' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Applied to newly created forms.', 'form-looq' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="looq-default-submit"><?php esc_html_e( 'Default submit label', 'form-looq' ); ?></label></th>
					<td><input id="looq-default-submit" class="regular-text" type="text" name="form_looq_settings[default_submit_label]" value="<?php echo esc_attr( (string) Settings::get( 'default_submit_label' ) ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Unsaved changes', 'form-looq' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="form_looq_settings[confirm_before_leaving]" value="0">
							<input type="checkbox" name="form_looq_settings[confirm_before_leaving]" value="1" <?php checked( (bool) Settings::get( 'confirm_before_leaving' ) ); ?>>
							<?php esc_html_e( 'Warn before leaving the builder with unsaved changes', 'form-looq' ); ?>
						</label>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	private static function render_addons_tab(): void {
		?>
		<div class="notice notice-warning inline">
			<p>
				<strong><?php esc_html_e( 'Off by default.', 'form-looq' ); ?></strong>
				<?php
				printf(
					/* translators: %s: catalogue endpoint URL. */
					esc_html__( "Turning this on makes the Add-ons and Help screens request %s. Each request sends only standard HTTP headers plus a user agent identifying the plugin version and this site's URL — no form content, entry data, or personal data. Nothing is sent unless you enable it here.", 'form-looq' ),
					'<code>' . esc_html( Plugin::HOME_URL ) . '</code>'
				);
				?>
			</p>
		</div>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Add-on catalogue', 'form-looq' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="form_looq_settings[addons_remote_enabled]" value="0">
							<input type="checkbox" name="form_looq_settings[addons_remote_enabled]" value="1" <?php checked( (bool) Settings::get( 'addons_remote_enabled' ) ); ?>>
							<?php
							printf(
								/* translators: %s: catalogue URL. */
								esc_html__( 'Fetch the add-on and help catalogue from %s', 'form-looq' ),
								'<code>' . esc_html( Plugin::HOME_URL . '/addons' ) . '</code>'
							);
							?>
						</label>
						<p class="description"><?php esc_html_e( 'When disabled, the bundled catalogue is shown instead and no external request is made.', 'form-looq' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="looq-addons-cache"><?php esc_html_e( 'Cache lifetime (hours)', 'form-looq' ); ?></label></th>
					<td><input id="looq-addons-cache" class="small-text" type="number" min="1" max="168" name="form_looq_settings[addons_cache_hours]" value="<?php echo esc_attr( (string) Settings::get( 'addons_cache_hours' ) ); ?>"></td>
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
					<th scope="row"><?php esc_html_e( 'Frontend CSS', 'form-looq' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="form_looq_settings[output_css]" value="0">
							<input type="checkbox" name="form_looq_settings[output_css]" value="1" <?php checked( (bool) Settings::get( 'output_css' ) ); ?>>
							<?php esc_html_e( 'Output the bundled form stylesheet', 'form-looq' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Turn this off if your theme styles the forms itself.', 'form-looq' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'No-conflict mode', 'form-looq' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="form_looq_settings[no_conflict_mode]" value="0">
							<input type="checkbox" name="form_looq_settings[no_conflict_mode]" value="1" <?php checked( (bool) Settings::get( 'no_conflict_mode' ) ); ?>>
							<?php esc_html_e( 'Block third-party scripts and styles on plugin admin screens', 'form-looq' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Use this when another plugin breaks the form builder.', 'form-looq' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="looq-currency"><?php esc_html_e( 'Currency', 'form-looq' ); ?></label></th>
					<td>
						<input id="looq-currency" class="small-text code" type="text" maxlength="3" name="form_looq_settings[currency]" value="<?php echo esc_attr( (string) Settings::get( 'currency' ) ); ?>">
						<p class="description"><?php esc_html_e( 'Three-letter ISO code, used by payment-related add-ons.', 'form-looq' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="looq-retention"><?php esc_html_e( 'Entry retention (days)', 'form-looq' ); ?></label></th>
					<td>
						<input id="looq-retention" class="small-text" type="number" min="0" max="3650" name="form_looq_settings[retention_days]" value="<?php echo esc_attr( (string) Settings::get( 'retention_days' ) ); ?>">
						<p class="description"><?php esc_html_e( 'Entries older than this are deleted by a daily job. Zero keeps entries forever.', 'form-looq' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Uninstall behaviour', 'form-looq' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="form_looq_settings[delete_data_on_uninstall]" value="0">
							<input type="checkbox" name="form_looq_settings[delete_data_on_uninstall]" value="1" <?php checked( (bool) Settings::get( 'delete_data_on_uninstall' ) ); ?>>
							<?php esc_html_e( 'Delete all forms, entries, and plugin tables when the plugin is deleted', 'form-looq' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Off by default. Forms and entries live in dedicated database tables, so deleting and reinstalling the plugin keeps your data intact.', 'form-looq' ); ?>
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
					<th scope="row"><?php esc_html_e( 'Dashboard widget', 'form-looq' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="form_looq_settings[dashboard_widget]" value="0">
							<input type="checkbox" name="form_looq_settings[dashboard_widget]" value="1" <?php checked( (bool) Settings::get( 'dashboard_widget' ) ); ?>>
							<?php esc_html_e( 'Show form and entry totals on the WordPress dashboard', 'form-looq' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Elementor widget', 'form-looq' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="form_looq_settings[elementor_widget]" value="0">
							<input type="checkbox" name="form_looq_settings[elementor_widget]" value="1" <?php checked( (bool) Settings::get( 'elementor_widget' ) ); ?>>
							<?php esc_html_e( 'Register the Form LOOQ widget in the Elementor editor', 'form-looq' ); ?>
						</label>
						<p class="description">
							<?php
							echo defined( 'ELEMENTOR_VERSION' )
								? esc_html__( 'Elementor is active on this site.', 'form-looq' )
								: esc_html__( 'Elementor is not active, so the widget is not registered.', 'form-looq' );
							?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

}
