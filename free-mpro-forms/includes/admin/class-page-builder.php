<?php
/**
 * Drag-and-drop form builder screen.
 *
 * @package FreeMPROForms
 */

namespace FreeMPROForms\Admin;

use FreeMPROForms\DB;
use FreeMPROForms\Field_Validator;
use FreeMPROForms\Form_Repository;
use FreeMPROForms\Plugin;

defined( 'ABSPATH' ) || exit;

final class Page_Builder {
	private const ACTION = 'fmpf_save_form';

	public static function init(): void {
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	public static function enqueue( string $hook ): void {
		if ( ! str_contains( $hook, Plugin::MENU_SLUG . '-builder' ) ) {
			return;
		}

		wp_enqueue_script(
			'fmpf-builder',
			plugins_url( 'assets/js/builder.js', FREE_MPRO_FORMS_FILE ),
			array( 'wp-i18n' ),
			FREE_MPRO_FORMS_VERSION,
			true
		);

		wp_set_script_translations( 'fmpf-builder', 'free-mpro-forms', FREE_MPRO_FORMS_DIR . 'languages' );

		wp_localize_script(
			'fmpf-builder',
			'fmpfBuilder',
			array(
				'types'  => self::type_config(),
				'labels' => array(
					'newFieldLabel'   => __( 'Untitled field', 'free-mpro-forms' ),
					'removeField'     => __( 'Remove field', 'free-mpro-forms' ),
					'moveUp'          => __( 'Move up', 'free-mpro-forms' ),
					'moveDown'        => __( 'Move down', 'free-mpro-forms' ),
					'label'           => __( 'Label', 'free-mpro-forms' ),
					'name'            => __( 'Field name', 'free-mpro-forms' ),
					'nameHelp'        => __( 'Used as the stored key. Leave empty to generate it from the label.', 'free-mpro-forms' ),
					'placeholder'     => __( 'Placeholder', 'free-mpro-forms' ),
					'help'            => __( 'Help text', 'free-mpro-forms' ),
					'options'         => __( 'Options (one per line)', 'free-mpro-forms' ),
					'required'        => __( 'Required', 'free-mpro-forms' ),
					'width'           => __( 'Width', 'free-mpro-forms' ),
					'widthFull'       => __( 'Full width', 'free-mpro-forms' ),
					'widthHalf'       => __( 'Half width', 'free-mpro-forms' ),
					'empty'           => __( 'Drag a field from the left, or click one to add it here.', 'free-mpro-forms' ),
					'confirmRemove'   => __( 'Remove this field?', 'free-mpro-forms' ),
					'maxFields'       => __( 'A form can hold at most 50 fields.', 'free-mpro-forms' ),
				),
			)
		);
	}

	/**
	 * Field palette configuration handed to the builder script.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function type_config(): array {
		$config = array();

		foreach ( Field_Validator::types() as $type => $meta ) {
			$config[] = array(
				'type'       => $type,
				'label'      => $meta['label'],
				'icon'       => $meta['icon'],
				'hasOptions' => $meta['has_options'],
			);
		}

		return $config;
	}

	public static function handle_save(): void {
		Admin::guard();
		check_admin_referer( self::ACTION );

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$form    = Form_Repository::get( $form_id );

		if ( ! $form ) {
			wp_safe_redirect( Plugin::admin_url( Plugin::MENU_SLUG, array( 'fmpf_notice' => 'error' ) ) );
			exit;
		}

		// The builder posts its state as JSON; every value is re-sanitized server side.
		$raw_fields = isset( $_POST['fmpf_fields'] ) ? wp_unslash( $_POST['fmpf_fields'] ) : '[]'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$fields     = DB::decode( is_string( $raw_fields ) ? $raw_fields : '' );

		$update = array( 'fields' => $fields );

		/*
		 * Only the settings tab renders the settings inputs, and an unchecked
		 * checkbox is not posted at all. Without this marker, saving from the
		 * fields or embed tab would read every setting as absent and reset the
		 * form's submit label, success message, layout, redirect, and privacy
		 * options back to their defaults.
		 */
		if ( ! empty( $_POST['fmpf_has_settings'] ) ) {
			$update['title']       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : $form['title'];
			$update['description'] = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : $form['description'];
			$update['status']      = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : $form['status'];
			$update['settings']    = array(
				'submit_label'    => isset( $_POST['submit_label'] ) ? sanitize_text_field( wp_unslash( $_POST['submit_label'] ) ) : '',
				'success_message' => isset( $_POST['success_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['success_message'] ) ) : '',
				'layout'          => isset( $_POST['layout'] ) ? sanitize_key( wp_unslash( $_POST['layout'] ) ) : 'one-column',
				'redirect_url'    => isset( $_POST['redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_url'] ) ) : '',
				'store_ip'        => ! empty( $_POST['store_ip'] ),
				'honeypot'        => ! empty( $_POST['honeypot'] ),
			);
		}

		Form_Repository::update( $form_id, $update );

		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'fields';

		wp_safe_redirect(
			Plugin::admin_url(
				Plugin::MENU_SLUG . '-builder',
				array(
					'form'        => $form_id,
					'tab'         => $tab,
					'fmpf_notice' => 'updated',
				)
			)
		);
		exit;
	}

	public static function render(): void {
		Admin::guard();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$form_id = isset( $_GET['form'] ) ? absint( $_GET['form'] ) : 0;
		$tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'fields';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$form = Form_Repository::get( $form_id );

		if ( ! $form ) {
			echo '<div class="wrap fmpf-wrap"><div class="notice notice-error"><p>'
				. esc_html__( 'That form no longer exists.', 'free-mpro-forms' ) . '</p></div></div>';
			return;
		}

		$tab = in_array( $tab, array( 'fields', 'settings', 'embed' ), true ) ? $tab : 'fields';
		?>
		<div class="wrap fmpf-wrap fmpf-builder-wrap">
			<?php
			Admin::header( $form['title'], __( 'Form builder', 'free-mpro-forms' ) );
			Admin::render_notice();
			?>

			<h2 class="nav-tab-wrapper">
				<?php
				$tabs = array(
					'fields'   => __( 'Fields', 'free-mpro-forms' ),
					'settings' => __( 'Settings', 'free-mpro-forms' ),
					'embed'    => __( 'Embed', 'free-mpro-forms' ),
				);

				foreach ( $tabs as $slug => $label ) {
					printf(
						'<a class="nav-tab %1$s" href="%2$s">%3$s</a>',
						esc_attr( $tab === $slug ? 'nav-tab-active' : '' ),
						esc_url(
							Plugin::admin_url(
								Plugin::MENU_SLUG . '-builder',
								array(
									'form' => $form_id,
									'tab'  => $slug,
								)
							)
						),
						esc_html( $label )
					);
				}
				?>
			</h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="fmpf-builder-form">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
				<input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $form_id ); ?>">
				<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
				<input type="hidden" name="fmpf_fields" id="fmpf-fields-input" value="<?php echo esc_attr( DB::encode( (array) $form['fields'] ) ); ?>">
				<?php wp_nonce_field( self::ACTION ); ?>

				<?php
				switch ( $tab ) {
					case 'settings':
						self::render_settings_tab( $form );
						break;
					case 'embed':
						self::render_embed_tab( $form );
						break;
					default:
						self::render_fields_tab( $form );
				}
				?>

				<?php if ( 'embed' !== $tab ) : ?>
					<p class="fmpf-builder__save">
						<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save form', 'free-mpro-forms' ); ?></button>
						<a class="button" href="<?php echo esc_url( Plugin::admin_url() ); ?>"><?php esc_html_e( 'Back to forms', 'free-mpro-forms' ); ?></a>
					</p>
				<?php endif; ?>
			</form>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed> $form Hydrated form row.
	 */
	private static function render_fields_tab( array $form ): void {
		?>
		<div class="fmpf-builder" id="fmpf-builder">
			<div class="fmpf-builder__palette">
				<h2><?php esc_html_e( 'Fields', 'free-mpro-forms' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Drag a field into the form, or click to append it.', 'free-mpro-forms' ); ?></p>
				<ul class="fmpf-palette" id="fmpf-palette">
					<?php foreach ( Field_Validator::types() as $type => $meta ) : ?>
						<li>
							<button
								type="button"
								class="fmpf-palette__item"
								draggable="true"
								data-fmpf-type="<?php echo esc_attr( $type ); ?>"
							>
								<span class="dashicons dashicons-<?php echo esc_attr( $meta['icon'] ); ?>" aria-hidden="true"></span>
								<span><?php echo esc_html( $meta['label'] ); ?></span>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="fmpf-builder__canvas">
				<h2><?php esc_html_e( 'Form layout', 'free-mpro-forms' ); ?></h2>
				<div class="fmpf-canvas" id="fmpf-canvas" aria-live="polite"></div>
			</div>
		</div>
		<noscript>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'The form builder needs JavaScript. Enable it to edit fields.', 'free-mpro-forms' ); ?></p>
			</div>
		</noscript>
		<?php
	}

	/**
	 * @param array<string, mixed> $form Hydrated form row.
	 */
	private static function render_settings_tab( array $form ): void {
		$settings = $form['settings'];
		?>
		<input type="hidden" name="fmpf_has_settings" value="1">
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="fmpf-title"><?php esc_html_e( 'Form title', 'free-mpro-forms' ); ?></label></th>
					<td><input id="fmpf-title" class="regular-text" type="text" name="title" value="<?php echo esc_attr( (string) $form['title'] ); ?>" maxlength="191"></td>
				</tr>
				<tr>
					<th scope="row"><label for="fmpf-description"><?php esc_html_e( 'Description', 'free-mpro-forms' ); ?></label></th>
					<td><textarea id="fmpf-description" class="large-text" name="description" rows="3"><?php echo esc_textarea( (string) $form['description'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="fmpf-status"><?php esc_html_e( 'Status', 'free-mpro-forms' ); ?></label></th>
					<td>
						<select id="fmpf-status" name="status">
							<?php foreach ( Form_Repository::statuses() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $form['status'], $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Only active forms render on the site.', 'free-mpro-forms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fmpf-layout"><?php esc_html_e( 'Layout', 'free-mpro-forms' ); ?></label></th>
					<td>
						<select id="fmpf-layout" name="layout">
							<option value="one-column" <?php selected( $settings['layout'], 'one-column' ); ?>><?php esc_html_e( 'Single column', 'free-mpro-forms' ); ?></option>
							<option value="two-column" <?php selected( $settings['layout'], 'two-column' ); ?>><?php esc_html_e( 'Two columns', 'free-mpro-forms' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fmpf-submit-label"><?php esc_html_e( 'Submit button label', 'free-mpro-forms' ); ?></label></th>
					<td><input id="fmpf-submit-label" class="regular-text" type="text" name="submit_label" value="<?php echo esc_attr( (string) $settings['submit_label'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="fmpf-success"><?php esc_html_e( 'Success message', 'free-mpro-forms' ); ?></label></th>
					<td><textarea id="fmpf-success" class="large-text" name="success_message" rows="2"><?php echo esc_textarea( (string) $settings['success_message'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="fmpf-redirect"><?php esc_html_e( 'Redirect after submit', 'free-mpro-forms' ); ?></label></th>
					<td>
						<input id="fmpf-redirect" class="regular-text code" type="url" name="redirect_url" value="<?php echo esc_attr( (string) $settings['redirect_url'] ); ?>" placeholder="https://">
						<p class="description"><?php esc_html_e( 'Leave empty to show the success message in place.', 'free-mpro-forms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Privacy and spam', 'free-mpro-forms' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox" name="store_ip" value="1" <?php checked( ! empty( $settings['store_ip'] ) ); ?>>
								<?php esc_html_e( 'Store a salted hash of the visitor IP with each entry', 'free-mpro-forms' ); ?>
							</label><br>
							<label>
								<input type="checkbox" name="honeypot" value="1" <?php checked( ! empty( $settings['honeypot'] ) ); ?>>
								<?php esc_html_e( 'Enable the hidden honeypot field', 'free-mpro-forms' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * @param array<string, mixed> $form Hydrated form row.
	 */
	private static function render_embed_tab( array $form ): void {
		$form_id = (int) $form['id'];

		$snippets = array(
			array(
				'title' => __( 'Shortcode', 'free-mpro-forms' ),
				'help'  => __( 'Paste into any post, page, widget, or page builder text area.', 'free-mpro-forms' ),
				'code'  => Plugin::embed_code( $form_id ),
			),
			array(
				'title' => __( 'Block editor', 'free-mpro-forms' ),
				'help'  => __( 'Paste into the code editor view of a post.', 'free-mpro-forms' ),
				'code'  => Plugin::block_embed_code( $form_id ),
			),
			array(
				'title' => __( 'Theme template', 'free-mpro-forms' ),
				'help'  => __( 'Use inside a PHP template file.', 'free-mpro-forms' ),
				'code'  => Plugin::php_embed_code( $form_id ),
			),
		);
		?>
		<div class="fmpf-embed">
			<?php foreach ( $snippets as $index => $snippet ) : ?>
				<div class="fmpf-embed__item">
					<h3><?php echo esc_html( $snippet['title'] ); ?></h3>
					<p class="description"><?php echo esc_html( $snippet['help'] ); ?></p>
					<div class="fmpf-copy">
						<input
							type="text"
							class="large-text code"
							id="<?php echo esc_attr( 'fmpf-embed-' . $index ); ?>"
							value="<?php echo esc_attr( $snippet['code'] ); ?>"
							readonly
						>
						<button type="button" class="button fmpf-copy__button" data-fmpf-copy="<?php echo esc_attr( 'fmpf-embed-' . $index ); ?>">
							<?php esc_html_e( 'Copy', 'free-mpro-forms' ); ?>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
