<?php
/**
 * Drag-and-drop form builder screen.
 *
 * @package FormLooq
 */

namespace FormLooq\Admin;

use FormLooq\DB;
use FormLooq\Field_Validator;
use FormLooq\Form_Repository;
use FormLooq\Plugin;

defined( 'ABSPATH' ) || exit;

final class Page_Builder {
	private const ACTION = 'looq_save_form';

	public static function init(): void {
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	public static function enqueue( string $hook ): void {
		if ( ! str_contains( $hook, Plugin::MENU_SLUG . '-builder' ) ) {
			return;
		}

		wp_enqueue_script(
			'looq-builder',
			plugins_url( 'assets/js/builder.js', FORM_LOOQ_FILE ),
			array( 'wp-i18n' ),
			FORM_LOOQ_VERSION,
			true
		);

		wp_set_script_translations( 'looq-builder', 'form-looq', FORM_LOOQ_DIR . 'languages' );

		wp_localize_script(
			'looq-builder',
			'looqBuilder',
			array(
				'types'  => self::type_config(),
				'labels' => array(
					'newFieldLabel'   => __( 'Untitled field', 'form-looq' ),
					'removeField'     => __( 'Remove field', 'form-looq' ),
					'moveUp'          => __( 'Move up', 'form-looq' ),
					'moveDown'        => __( 'Move down', 'form-looq' ),
					'label'           => __( 'Label', 'form-looq' ),
					'name'            => __( 'Field name', 'form-looq' ),
					'nameHelp'        => __( 'Used as the stored key. Leave empty to generate it from the label.', 'form-looq' ),
					'placeholder'     => __( 'Placeholder', 'form-looq' ),
					'help'            => __( 'Help text', 'form-looq' ),
					'options'         => __( 'Options (one per line)', 'form-looq' ),
					'required'        => __( 'Required', 'form-looq' ),
					'width'           => __( 'Width', 'form-looq' ),
					'widthFull'       => __( 'Full width', 'form-looq' ),
					'widthHalf'       => __( 'Half width', 'form-looq' ),
					'empty'           => __( 'Drag a field from the left, or click one to add it here.', 'form-looq' ),
					'confirmRemove'   => __( 'Remove this field?', 'form-looq' ),
					'maxFields'       => __( 'A form can hold at most 50 fields.', 'form-looq' ),
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
			wp_safe_redirect( Plugin::admin_url( Plugin::MENU_SLUG, array( 'looq_notice' => 'error' ) ) );
			exit;
		}

		// The builder posts its state as JSON; every value is re-sanitized server side.
		$raw_fields = isset( $_POST['looq_fields'] ) ? wp_unslash( $_POST['looq_fields'] ) : '[]'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$fields     = DB::decode( is_string( $raw_fields ) ? $raw_fields : '' );

		$update = array( 'fields' => $fields );

		/*
		 * Only the settings tab renders the settings inputs, and an unchecked
		 * checkbox is not posted at all. Without this marker, saving from the
		 * fields or embed tab would read every setting as absent and reset the
		 * form's submit label, success message, layout, redirect, and privacy
		 * options back to their defaults.
		 */
		if ( ! empty( $_POST['looq_has_settings'] ) ) {
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
					'looq_notice' => 'updated',
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
			echo '<div class="wrap looq-wrap"><div class="notice notice-error"><p>'
				. esc_html__( 'That form no longer exists.', 'form-looq' ) . '</p></div></div>';
			return;
		}

		$tab = in_array( $tab, array( 'fields', 'settings', 'embed' ), true ) ? $tab : 'fields';
		?>
		<div class="wrap looq-wrap looq-builder-wrap">
			<?php
			Admin::header( $form['title'], __( 'Form builder', 'form-looq' ) );
			Admin::render_notice();
			?>

			<h2 class="nav-tab-wrapper">
				<?php
				$tabs = array(
					'fields'   => __( 'Fields', 'form-looq' ),
					'settings' => __( 'Settings', 'form-looq' ),
					'embed'    => __( 'Embed', 'form-looq' ),
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

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="looq-builder-form">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
				<input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $form_id ); ?>">
				<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
				<input type="hidden" name="looq_fields" id="looq-fields-input" value="<?php echo esc_attr( DB::encode( (array) $form['fields'] ) ); ?>">
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
					<p class="looq-builder__save">
						<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save form', 'form-looq' ); ?></button>
						<a class="button" href="<?php echo esc_url( Plugin::admin_url() ); ?>"><?php esc_html_e( 'Back to forms', 'form-looq' ); ?></a>
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
		<div class="looq-builder" id="looq-builder">
			<div class="looq-builder__palette">
				<h2><?php esc_html_e( 'Fields', 'form-looq' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Drag a field into the form, or click to append it.', 'form-looq' ); ?></p>
				<ul class="looq-palette" id="looq-palette">
					<?php foreach ( Field_Validator::types() as $type => $meta ) : ?>
						<li>
							<button
								type="button"
								class="looq-palette__item"
								draggable="true"
								data-looq-type="<?php echo esc_attr( $type ); ?>"
							>
								<span class="dashicons dashicons-<?php echo esc_attr( $meta['icon'] ); ?>" aria-hidden="true"></span>
								<span><?php echo esc_html( $meta['label'] ); ?></span>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="looq-builder__canvas">
				<h2><?php esc_html_e( 'Form layout', 'form-looq' ); ?></h2>
				<div class="looq-canvas" id="looq-canvas" aria-live="polite"></div>
			</div>
		</div>
		<noscript>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'The form builder needs JavaScript. Enable it to edit fields.', 'form-looq' ); ?></p>
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
		<input type="hidden" name="looq_has_settings" value="1">
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="looq-title"><?php esc_html_e( 'Form title', 'form-looq' ); ?></label></th>
					<td><input id="looq-title" class="regular-text" type="text" name="title" value="<?php echo esc_attr( (string) $form['title'] ); ?>" maxlength="191"></td>
				</tr>
				<tr>
					<th scope="row"><label for="looq-description"><?php esc_html_e( 'Description', 'form-looq' ); ?></label></th>
					<td><textarea id="looq-description" class="large-text" name="description" rows="3"><?php echo esc_textarea( (string) $form['description'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="looq-status"><?php esc_html_e( 'Status', 'form-looq' ); ?></label></th>
					<td>
						<select id="looq-status" name="status">
							<?php foreach ( Form_Repository::statuses() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $form['status'], $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Only active forms render on the site.', 'form-looq' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="looq-layout"><?php esc_html_e( 'Layout', 'form-looq' ); ?></label></th>
					<td>
						<select id="looq-layout" name="layout">
							<option value="one-column" <?php selected( $settings['layout'], 'one-column' ); ?>><?php esc_html_e( 'Single column', 'form-looq' ); ?></option>
							<option value="two-column" <?php selected( $settings['layout'], 'two-column' ); ?>><?php esc_html_e( 'Two columns', 'form-looq' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="looq-submit-label"><?php esc_html_e( 'Submit button label', 'form-looq' ); ?></label></th>
					<td><input id="looq-submit-label" class="regular-text" type="text" name="submit_label" value="<?php echo esc_attr( (string) $settings['submit_label'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="looq-success"><?php esc_html_e( 'Success message', 'form-looq' ); ?></label></th>
					<td><textarea id="looq-success" class="large-text" name="success_message" rows="2"><?php echo esc_textarea( (string) $settings['success_message'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="looq-redirect"><?php esc_html_e( 'Redirect after submit', 'form-looq' ); ?></label></th>
					<td>
						<input id="looq-redirect" class="regular-text code" type="url" name="redirect_url" value="<?php echo esc_attr( (string) $settings['redirect_url'] ); ?>" placeholder="https://">
						<p class="description"><?php esc_html_e( 'Leave empty to show the success message in place.', 'form-looq' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Privacy and spam', 'form-looq' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox" name="store_ip" value="1" <?php checked( ! empty( $settings['store_ip'] ) ); ?>>
								<?php esc_html_e( 'Store a salted hash of the visitor IP with each entry', 'form-looq' ); ?>
							</label><br>
							<label>
								<input type="checkbox" name="honeypot" value="1" <?php checked( ! empty( $settings['honeypot'] ) ); ?>>
								<?php esc_html_e( 'Enable the hidden honeypot field', 'form-looq' ); ?>
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
				'title' => __( 'Shortcode', 'form-looq' ),
				'help'  => __( 'Paste into any post, page, widget, or page builder text area.', 'form-looq' ),
				'code'  => Plugin::embed_code( $form_id ),
			),
			array(
				'title' => __( 'Block editor', 'form-looq' ),
				'help'  => __( 'Paste into the code editor view of a post.', 'form-looq' ),
				'code'  => Plugin::block_embed_code( $form_id ),
			),
			array(
				'title' => __( 'Theme template', 'form-looq' ),
				'help'  => __( 'Use inside a PHP template file.', 'form-looq' ),
				'code'  => Plugin::php_embed_code( $form_id ),
			),
		);
		?>
		<div class="looq-embed">
			<?php foreach ( $snippets as $index => $snippet ) : ?>
				<div class="looq-embed__item">
					<h3><?php echo esc_html( $snippet['title'] ); ?></h3>
					<p class="description"><?php echo esc_html( $snippet['help'] ); ?></p>
					<div class="looq-copy">
						<input
							type="text"
							class="large-text code"
							id="<?php echo esc_attr( 'looq-embed-' . $index ); ?>"
							value="<?php echo esc_attr( $snippet['code'] ); ?>"
							readonly
						>
						<button type="button" class="button looq-copy__button" data-looq-copy="<?php echo esc_attr( 'looq-embed-' . $index ); ?>">
							<?php esc_html_e( 'Copy', 'form-looq' ); ?>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
