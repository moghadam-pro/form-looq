<?php
/**
 * Two-step "new form" flow: pick a template, then name the form.
 *
 * @package FormLooq
 */

namespace FormLooq\Admin;

use FormLooq\Form_Repository;
use FormLooq\Plugin;
use FormLooq\Settings;
use FormLooq\Templates;

defined( 'ABSPATH' ) || exit;

final class Page_New_Form {
	private const ACTION = 'looq_create_form';

	public static function init(): void {
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'handle_create' ) );
	}

	public static function handle_create(): void {
		Admin::guard();
		check_admin_referer( self::ACTION );

		$template = isset( $_POST['template'] ) ? sanitize_key( wp_unslash( $_POST['template'] ) ) : 'blank';
		$template = Templates::exists( $template ) ? $template : 'blank';
		$title    = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';

		if ( '' === $title ) {
			wp_safe_redirect(
				Plugin::admin_url(
					Plugin::MENU_SLUG . '-new',
					array(
						'template'     => $template,
						'step'         => 'details',
						'looq_notice'  => 'error',
					)
				)
			);
			exit;
		}

		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';

		$form_id = Form_Repository::create(
			array(
				'title'       => $title,
				'description' => $description,
				'status'      => Form_Repository::STATUS_ACTIVE,
				'fields'      => Templates::fields( $template ),
				'settings'    => array(
					'layout'       => (string) Settings::get( 'default_layout', 'one-column' ),
					'submit_label' => Settings::default_submit_label(),
					'store_ip'     => true,
					'honeypot'     => true,
				),
			)
		);

		if ( $form_id <= 0 ) {
			wp_safe_redirect( Plugin::admin_url( Plugin::MENU_SLUG, array( 'looq_notice' => 'error' ) ) );
			exit;
		}

		wp_safe_redirect(
			Plugin::admin_url(
				Plugin::MENU_SLUG . '-builder',
				array(
					'form'        => $form_id,
					'looq_notice' => 'created',
				)
			)
		);
		exit;
	}

	public static function render(): void {
		Admin::guard();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$step     = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : 'template';
		$template = isset( $_GET['template'] ) ? sanitize_key( wp_unslash( $_GET['template'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$template = Templates::exists( $template ) ? $template : '';
		$step     = ( 'details' === $step && '' !== $template ) ? 'details' : 'template';
		?>
		<div class="wrap looq-wrap">
			<?php
			Admin::header(
				__( 'New form', 'form-looq' ),
				__( 'Choose a starting point, name your form, and open the builder.', 'form-looq' )
			);
			Admin::render_notice();
			?>

			<div class="looq-modal" role="dialog" aria-modal="false" aria-labelledby="looq-new-form-title">
				<div class="looq-modal__inner">
					<ol class="looq-steps">
						<li class="<?php echo 'template' === $step ? 'is-current' : 'is-done'; ?>"><?php esc_html_e( '1. Template', 'form-looq' ); ?></li>
						<li class="<?php echo 'details' === $step ? 'is-current' : ''; ?>"><?php esc_html_e( '2. Details', 'form-looq' ); ?></li>
					</ol>

					<?php if ( 'template' === $step ) : ?>
						<?php self::render_template_step(); ?>
					<?php else : ?>
						<?php self::render_details_step( $template ); ?>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	private static function render_template_step(): void {
		?>
		<h2 id="looq-new-form-title"><?php esc_html_e( 'Start from a template', 'form-looq' ); ?></h2>
		<p class="looq-modal__lead"><?php esc_html_e( 'Templates are just starting points — every field can be changed in the builder.', 'form-looq' ); ?></p>

		<div class="looq-template-grid">
			<?php foreach ( Templates::all() as $slug => $template ) : ?>
				<a
					class="looq-template-card<?php echo 'blank' === $slug ? ' is-blank' : ''; ?>"
					href="<?php
					echo esc_url(
						Plugin::admin_url(
							Plugin::MENU_SLUG . '-new',
							array(
								'step'     => 'details',
								'template' => $slug,
							)
						)
					);
					?>"
				>
					<span class="looq-template-card__icon dashicons dashicons-<?php echo esc_attr( (string) $template['icon'] ); ?>" aria-hidden="true"></span>
					<span class="looq-template-card__title"><?php echo esc_html( (string) $template['title'] ); ?></span>
					<span class="looq-template-card__description"><?php echo esc_html( (string) $template['description'] ); ?></span>
					<span class="looq-template-card__meta">
						<?php
						$count = count( (array) $template['fields'] );
						echo esc_html(
							0 === $count
								? __( 'Empty', 'form-looq' )
								/* translators: %s: number of fields. */
								: sprintf( _n( '%s field', '%s fields', $count, 'form-looq' ), number_format_i18n( $count ) )
						);
						?>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private static function render_details_step( string $template ): void {
		$templates = Templates::all();
		$chosen    = $templates[ $template ] ?? array();
		?>
		<h2 id="looq-new-form-title"><?php esc_html_e( 'Name your form', 'form-looq' ); ?></h2>
		<p class="looq-modal__lead">
			<?php
			printf(
				/* translators: %s: template name. */
				esc_html__( 'Starting from: %s', 'form-looq' ),
				'<strong>' . esc_html( (string) ( $chosen['title'] ?? '' ) ) . '</strong>'
			);
			?>
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="looq-modal__form">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
			<input type="hidden" name="template" value="<?php echo esc_attr( $template ); ?>">
			<?php wp_nonce_field( self::ACTION ); ?>

			<p>
				<label for="looq-new-title"><strong><?php esc_html_e( 'Form title', 'form-looq' ); ?></strong></label><br>
				<input id="looq-new-title" class="regular-text" type="text" name="title" maxlength="191" required autofocus>
			</p>

			<p>
				<label for="looq-new-description"><strong><?php esc_html_e( 'Description', 'form-looq' ); ?></strong> <span class="description"><?php esc_html_e( '(optional)', 'form-looq' ); ?></span></label><br>
				<textarea id="looq-new-description" class="large-text" name="description" rows="3"></textarea>
			</p>

			<p class="looq-modal__actions">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Create form', 'form-looq' ); ?></button>
				<a class="button button-secondary" href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-new' ) ); ?>"><?php esc_html_e( 'Back to templates', 'form-looq' ); ?></a>
			</p>
		</form>
		<?php
	}
}
