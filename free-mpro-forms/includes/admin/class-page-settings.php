<?php
/**
 * Tabbed settings screen.
 *
 * @package FreeMPROForms
 */

namespace FreeMPROForms\Admin;

use FreeMPROForms\Plugin;
use FreeMPROForms\Settings;

defined( 'ABSPATH' ) || exit;

final class Page_Settings {
	private const ACTION = 'fmpf_save_settings';

	public static function init(): void {
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'handle_save' ) );
	}

	public static function handle_save(): void {
		Admin::guard();
		check_admin_referer( self::ACTION );

		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'general';
		$tab = array_key_exists( $tab, Settings::tabs() ) ? $tab : 'general';

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Settings::sanitize handles every value.
		$input = isset( $_POST['fmpf_settings'] ) ? (array) wp_unslash( $_POST['fmpf_settings'] ) : array();

		Settings::save( $input );

		wp_safe_redirect(
			Plugin::admin_url(
				Plugin::MENU_SLUG . '-settings',
				array(
					'tab'         => $tab,
					'fmpf_notice' => 'settings',
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
		<div class="wrap fmpf-wrap">
			<?php
			Admin::header( __( 'Settings', 'free-mpro-forms' ), __( 'Plugin-wide defaults. Per-form options live in the form builder.', 'free-mpro-forms' ) );
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
					<?php submit_button( __( 'Save settings', 'free-mpro-forms' ) ); ?>
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
					<th scope="row"><label for="fmpf-default-layout"><?php esc_html_e( 'Default layout', 'free-mpro-forms' ); ?></label></th>
					<td>
						<select id="fmpf-default-layout" name="fmpf_settings[default_layout]">
							<option value="one-column" <?php selected( Settings::get( 'default_layout' ), 'one-column' ); ?>><?php esc_html_e( 'Single column', 'free-mpro-forms' ); ?></option>
							<option value="two-column" <?php selected( Settings::get( 'default_layout' ), 'two-column' ); ?>><?php esc_html_e( 'Two columns', 'free-mpro-forms' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Applied to newly created forms.', 'free-mpro-forms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fmpf-default-submit"><?php esc_html_e( 'Default submit label', 'free-mpro-forms' ); ?></label></th>
					<td><input id="fmpf-default-submit" class="regular-text" type="text" name="fmpf_settings[default_submit_label]" value="<?php echo esc_attr( (string) Settings::get( 'default_submit_label' ) ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Unsaved changes', 'free-mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="fmpf_settings[confirm_before_leaving]" value="0">
							<input type="checkbox" name="fmpf_settings[confirm_before_leaving]" value="1" <?php checked( (bool) Settings::get( 'confirm_before_leaving' ) ); ?>>
							<?php esc_html_e( 'Warn before leaving the builder with unsaved changes', 'free-mpro-forms' ); ?>
						</label>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	private static function render_addons_tab(): void {
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Add-on catalogue', 'free-mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="fmpf_settings[addons_remote_enabled]" value="0">
							<input type="checkbox" name="fmpf_settings[addons_remote_enabled]" value="1" <?php checked( (bool) Settings::get( 'addons_remote_enabled' ) ); ?>>
							<?php
							printf(
								/* translators: %s: catalogue URL. */
								esc_html__( 'Fetch the add-on list from %s', 'free-mpro-forms' ),
								'<code>' . esc_html( Plugin::HOME_URL . '/addons' ) . '</code>'
							);
							?>
						</label>
						<p class="description"><?php esc_html_e( 'When disabled, the bundled catalogue is shown instead and no external request is made.', 'free-mpro-forms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fmpf-addons-cache"><?php esc_html_e( 'Cache lifetime (hours)', 'free-mpro-forms' ); ?></label></th>
					<td><input id="fmpf-addons-cache" class="small-text" type="number" min="1" max="168" name="fmpf_settings[addons_cache_hours]" value="<?php echo esc_attr( (string) Settings::get( 'addons_cache_hours' ) ); ?>"></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	private static function render_license_tab(): void {
		?>
		<div class="notice notice-success inline fmpf-license-banner">
			<h2><?php esc_html_e( 'Every feature in this version is free.', 'free-mpro-forms' ); ?></h2>
			<p><?php esc_html_e( 'No license key is required and no feature is gated. Install it on as many sites as you like.', 'free-mpro-forms' ); ?></p>
			<p>
				<?php esc_html_e( 'Some future add-ons that carry ongoing hosting or maintenance costs — SMS delivery and similar services — may become paid. The free core will stay free, and this tab is where any license key would be entered.', 'free-mpro-forms' ); ?>
			</p>
		</div>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="fmpf-license-key"><?php esc_html_e( 'License key', 'free-mpro-forms' ); ?></label></th>
					<td>
						<input id="fmpf-license-key" class="regular-text code" type="text" value="" disabled placeholder="<?php esc_attr_e( 'Not required in this version', 'free-mpro-forms' ); ?>">
						<p class="description"><?php esc_html_e( 'Disabled because nothing in this release needs activation.', 'free-mpro-forms' ); ?></p>
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
					<th scope="row"><?php esc_html_e( 'Frontend CSS', 'free-mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="fmpf_settings[output_css]" value="0">
							<input type="checkbox" name="fmpf_settings[output_css]" value="1" <?php checked( (bool) Settings::get( 'output_css' ) ); ?>>
							<?php esc_html_e( 'Output the bundled form stylesheet', 'free-mpro-forms' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Turn this off if your theme styles the forms itself.', 'free-mpro-forms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'No-conflict mode', 'free-mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="fmpf_settings[no_conflict_mode]" value="0">
							<input type="checkbox" name="fmpf_settings[no_conflict_mode]" value="1" <?php checked( (bool) Settings::get( 'no_conflict_mode' ) ); ?>>
							<?php esc_html_e( 'Block third-party scripts and styles on plugin admin screens', 'free-mpro-forms' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Use this when another plugin breaks the form builder.', 'free-mpro-forms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fmpf-currency"><?php esc_html_e( 'Currency', 'free-mpro-forms' ); ?></label></th>
					<td>
						<input id="fmpf-currency" class="small-text code" type="text" maxlength="3" name="fmpf_settings[currency]" value="<?php echo esc_attr( (string) Settings::get( 'currency' ) ); ?>">
						<p class="description"><?php esc_html_e( 'Three-letter ISO code, used by payment-related add-ons.', 'free-mpro-forms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fmpf-retention"><?php esc_html_e( 'Entry retention (days)', 'free-mpro-forms' ); ?></label></th>
					<td>
						<input id="fmpf-retention" class="small-text" type="number" min="0" max="3650" name="fmpf_settings[retention_days]" value="<?php echo esc_attr( (string) Settings::get( 'retention_days' ) ); ?>">
						<p class="description"><?php esc_html_e( 'Entries older than this are deleted by a daily job. Zero keeps entries forever.', 'free-mpro-forms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Automatic updates', 'free-mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="fmpf_settings[auto_update]" value="0">
							<input type="checkbox" name="fmpf_settings[auto_update]" value="1" <?php checked( (bool) Settings::get( 'auto_update' ) ); ?>>
							<?php esc_html_e( 'Allow WordPress to update this plugin automatically', 'free-mpro-forms' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Uninstall behaviour', 'free-mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="fmpf_settings[delete_data_on_uninstall]" value="0">
							<input type="checkbox" name="fmpf_settings[delete_data_on_uninstall]" value="1" <?php checked( (bool) Settings::get( 'delete_data_on_uninstall' ) ); ?>>
							<?php esc_html_e( 'Delete all forms, entries, and plugin tables when the plugin is deleted', 'free-mpro-forms' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Off by default. Forms and entries live in dedicated database tables, so deleting and reinstalling the plugin keeps your data intact.', 'free-mpro-forms' ); ?>
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
					<th scope="row"><?php esc_html_e( 'Dashboard widget', 'free-mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="fmpf_settings[dashboard_widget]" value="0">
							<input type="checkbox" name="fmpf_settings[dashboard_widget]" value="1" <?php checked( (bool) Settings::get( 'dashboard_widget' ) ); ?>>
							<?php esc_html_e( 'Show form and entry totals on the WordPress dashboard', 'free-mpro-forms' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Elementor widget', 'free-mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="fmpf_settings[elementor_widget]" value="0">
							<input type="checkbox" name="fmpf_settings[elementor_widget]" value="1" <?php checked( (bool) Settings::get( 'elementor_widget' ) ); ?>>
							<?php esc_html_e( 'Register the Free MPRO Forms widget in the Elementor editor', 'free-mpro-forms' ); ?>
						</label>
						<p class="description">
							<?php
							echo defined( 'ELEMENTOR_VERSION' )
								? esc_html__( 'Elementor is active on this site.', 'free-mpro-forms' )
								: esc_html__( 'Elementor is not active, so the widget is not registered.', 'free-mpro-forms' );
							?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	private static function render_rest_tab(): void {
		?>
		<div class="notice notice-info inline">
			<p><?php esc_html_e( 'The REST API is read-only groundwork in this release. Enabling it registers no public routes yet.', 'free-mpro-forms' ); ?></p>
		</div>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'REST API', 'free-mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="fmpf_settings[rest_enabled]" value="0">
							<input type="checkbox" name="fmpf_settings[rest_enabled]" value="1" <?php checked( (bool) Settings::get( 'rest_enabled' ) ); ?>>
							<?php esc_html_e( 'Enable the Free MPRO Forms REST namespace', 'free-mpro-forms' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Namespace', 'free-mpro-forms' ); ?></th>
					<td><code>free-mpro-forms/v1</code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Base URL', 'free-mpro-forms' ); ?></th>
					<td><code><?php echo esc_html( rest_url( 'free-mpro-forms/v1' ) ); ?></code></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	private static function render_sms_tab(): void {
		?>
		<div class="notice notice-info inline">
			<p><?php esc_html_e( 'Credentials are stored now so that SMS notifications and phone verification can be switched on in a later release. No message is sent by this version.', 'free-mpro-forms' ); ?></p>
		</div>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'SMS integration', 'free-mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="fmpf_settings[sms_enabled]" value="0">
							<input type="checkbox" name="fmpf_settings[sms_enabled]" value="1" <?php checked( (bool) Settings::get( 'sms_enabled' ) ); ?>>
							<?php esc_html_e( 'Enable SMS features', 'free-mpro-forms' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fmpf-sms-provider"><?php esc_html_e( 'Gateway', 'free-mpro-forms' ); ?></label></th>
					<td>
						<select id="fmpf-sms-provider" name="fmpf_settings[sms_provider]">
							<?php foreach ( Settings::sms_providers() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( Settings::get( 'sms_provider' ), $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fmpf-sms-key"><?php esc_html_e( 'API key', 'free-mpro-forms' ); ?></label></th>
					<td><input id="fmpf-sms-key" class="regular-text code" type="password" autocomplete="off" name="fmpf_settings[sms_api_key]" value="<?php echo esc_attr( (string) Settings::get( 'sms_api_key' ) ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="fmpf-sms-sender"><?php esc_html_e( 'Sender number', 'free-mpro-forms' ); ?></label></th>
					<td><input id="fmpf-sms-sender" class="regular-text code" type="text" name="fmpf_settings[sms_sender]" value="<?php echo esc_attr( (string) Settings::get( 'sms_sender' ) ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Phone verification', 'free-mpro-forms' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="fmpf_settings[sms_verify_numbers]" value="0">
							<input type="checkbox" name="fmpf_settings[sms_verify_numbers]" value="1" <?php checked( (bool) Settings::get( 'sms_verify_numbers' ) ); ?>>
							<?php esc_html_e( 'Require a one-time code for phone fields', 'free-mpro-forms' ); ?>
						</label>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}
}
