<?php

namespace FreeMPROForms;

defined( 'ABSPATH' ) || exit;

final class Frontend_Form {
	private const META_FIELDS = '_fmpf_fields';
	private const META_TYPE   = '_fmpf_submission_type';

	public static function init(): void {
		remove_shortcode( 'free_mpro_form' );
		add_shortcode( 'free_mpro_form', array( self::class, 'shortcode' ) );

		remove_action( 'admin_post_fmpf_submit', array( Form_Manager::class, 'handle_submission' ) );
		remove_action( 'admin_post_nopriv_fmpf_submit', array( Form_Manager::class, 'handle_submission' ) );
		add_action( 'admin_post_fmpf_submit', array( self::class, 'handle_submission' ) );
		add_action( 'admin_post_nopriv_fmpf_submit', array( self::class, 'handle_submission' ) );
		add_action( 'template_redirect', array( self::class, 'protect_state_page' ), 0 );
	}

	public static function protect_state_page(): void {
		if ( ! isset( $_GET['fmpf_state'] ) ) {
			return;
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		nocache_headers();
	}

	public static function shortcode( array $atts ): string {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'button' => __( 'Submit', 'free-mpro-forms' ),
				'sent'   => __( 'Thank you. Your submission has been recorded.', 'free-mpro-forms' ),
				'error'  => __( 'Please review the highlighted fields and try again.', 'free-mpro-forms' ),
				'dir'    => 'auto',
				'select' => '',
				'yes'    => '',
			),
			$atts,
			'free_mpro_form'
		);

		$form_id = absint( $atts['id'] );
		if ( ! $form_id || 'fmpf_form' !== get_post_type( $form_id ) || 'publish' !== get_post_status( $form_id ) ) {
			return current_user_can( 'edit_posts' ) ? '<p>' . esc_html__( 'Select a published form.', 'free-mpro-forms' ) . '</p>' : '';
		}

		$fields = Field_Validator::parse_definition( (string) get_post_meta( $form_id, self::META_FIELDS, true ) );
		if ( ! $fields ) {
			return '';
		}

		wp_enqueue_style( 'free-mpro-forms' );
		wp_enqueue_script(
			'free-mpro-forms-frontend',
			plugins_url( 'assets/js/forms.js', FREE_MPRO_FORMS_FILE ),
			array(),
			FREE_MPRO_FORMS_VERSION,
			true
		);

		$direction   = in_array( $atts['dir'], array( 'rtl', 'ltr' ), true ) ? $atts['dir'] : ( is_rtl() ? 'rtl' : 'ltr' );
		$is_rtl      = 'rtl' === $direction;
		$select      = $atts['select'] ?: ( $is_rtl ? 'انتخاب کنید' : __( 'Select', 'free-mpro-forms' ) );
		$yes         = $atts['yes'] ?: ( $is_rtl ? 'بله' : __( 'Yes', 'free-mpro-forms' ) );
		$status_form = isset( $_GET['fmpf_form'] ) ? absint( $_GET['fmpf_form'] ) : 0;
		$status      = $status_form === $form_id && isset( $_GET['fmpf_status'] ) ? sanitize_key( wp_unslash( $_GET['fmpf_status'] ) ) : '';
		$token       = $status_form === $form_id && isset( $_GET['fmpf_state'] ) ? sanitize_text_field( wp_unslash( $_GET['fmpf_state'] ) ) : '';
		$state       = 'error' === $status && $token ? Submission_State::consume( $token, $form_id ) : array();
		$values      = is_array( $state['values'] ?? null ) ? $state['values'] : array();
		$errors      = is_array( $state['errors'] ?? null ) ? $state['errors'] : array();
		$has_error   = 'error' === $status;
		$notice_id   = 'fmpf-notice-' . $form_id;
		$form_id_attr = 'fmpf-form-' . $form_id;

		ob_start();
		?>
		<div id="<?php echo esc_attr( $form_id_attr ); ?>" class="fmpf-form-wrap" dir="<?php echo esc_attr( $direction ); ?>" data-fmpf-has-errors="<?php echo $has_error ? '1' : '0'; ?>">
			<?php if ( 'sent' === $status ) : ?>
				<p id="<?php echo esc_attr( $notice_id ); ?>" class="fmpf-notice" role="status" tabindex="-1"><?php echo esc_html( (string) $atts['sent'] ); ?></p>
			<?php elseif ( $has_error ) : ?>
				<?php self::render_error_summary( $fields, $errors, (string) $atts['error'], $notice_id, $form_id ); ?>
			<?php endif; ?>

			<form class="fmpf-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"<?php if ( in_array( $status, array( 'sent', 'error' ), true ) ) : ?> aria-describedby="<?php echo esc_attr( $notice_id ); ?>"<?php endif; ?>>
				<input type="hidden" name="action" value="fmpf_submit">
				<input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $form_id ); ?>">
				<input type="hidden" name="fmpf_started_at" value="<?php echo esc_attr( (string) time() ); ?>">
				<?php wp_nonce_field( 'fmpf_submit_' . $form_id, 'fmpf_nonce' ); ?>

				<?php foreach ( $fields as $index => $field ) : ?>
					<?php self::render_field( $field, $select, $yes, $form_id, $index, $values, $errors ); ?>
				<?php endforeach; ?>

				<label class="fmpf-honeypot" aria-hidden="true" for="<?php echo esc_attr( 'fmpf-website-' . $form_id ); ?>">
					<?php esc_html_e( 'Leave this field empty', 'free-mpro-forms' ); ?>
					<input id="<?php echo esc_attr( 'fmpf-website-' . $form_id ); ?>" name="website" tabindex="-1" autocomplete="off">
				</label>
				<button type="submit"><?php echo esc_html( (string) $atts['button'] ); ?></button>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public static function handle_submission(): void {
		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$referer = wp_validate_redirect( wp_get_referer() ?: '', home_url( '/' ) );
		$started = isset( $_POST['fmpf_started_at'] ) ? absint( $_POST['fmpf_started_at'] ) : 0;
		$elapsed = $started > 0 ? time() - $started : 0;

		if (
			! $form_id ||
			'fmpf_form' !== get_post_type( $form_id ) ||
			'publish' !== get_post_status( $form_id ) ||
			! isset( $_POST['fmpf_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmpf_nonce'] ) ), 'fmpf_submit_' . $form_id ) ||
			! empty( $_POST['website'] ) ||
			$elapsed < 2 ||
			$elapsed > DAY_IN_SECONDS
		) {
			self::redirect( $referer, 'error', $form_id );
		}

		$fields = Field_Validator::parse_definition( (string) get_post_meta( $form_id, self::META_FIELDS, true ) );
		if ( ! $fields ) {
			self::redirect( $referer, 'error', $form_id );
		}

		$validation = Submission_Validator::validate( $fields, $_POST );
		if ( ! $validation['valid'] ) {
			$token = Submission_State::create( $form_id, $validation['values'], $validation['errors'] );
			self::redirect( $referer, 'error', $form_id, $token );
		}

		$type  = (string) get_post_meta( $form_id, self::META_TYPE, true );
		$title = get_the_title( $form_id ) . ' — ' . current_time( 'mysql' );

		if ( ! Submission_Manager::store( $type ?: 'general', $title, $validation['data'], $form_id ) ) {
			$errors = array( '_form' => __( 'We could not save your submission. Please try again.', 'free-mpro-forms' ) );
			$token  = Submission_State::create( $form_id, $validation['values'], $errors );
			self::redirect( $referer, 'error', $form_id, $token );
		}

		self::redirect( $referer, 'sent', $form_id );
	}

	private static function render_error_summary( array $fields, array $errors, string $fallback, string $notice_id, int $form_id ): void {
		$field_map = array();
		foreach ( $fields as $index => $field ) {
			if ( 'section' !== $field['type'] ) {
				$field_map[ $field['name'] ] = array(
					'label' => $field['label'],
					'id'    => self::field_id( $form_id, $index, $field ),
				);
			}
		}
		?>
		<div id="<?php echo esc_attr( $notice_id ); ?>" class="fmpf-notice is-error fmpf-error-summary" role="alert" tabindex="-1">
			<p><strong><?php echo esc_html( $errors['_form'] ?? $fallback ); ?></strong></p>
			<?php if ( array_diff_key( $errors, array( '_form' => true ) ) ) : ?>
				<ul>
					<?php foreach ( $errors as $name => $message ) : ?>
						<?php if ( '_form' === $name || ! isset( $field_map[ $name ] ) ) { continue; } ?>
						<li><a href="#<?php echo esc_attr( $field_map[ $name ]['id'] ); ?>"><?php echo esc_html( $message ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_field( array $field, string $select_label, string $yes_label, int $form_id, int $index, array $values, array $errors ): void {
		if ( 'section' === $field['type'] ) {
			echo '<section class="fmpf-section"><h2>' . esc_html( $field['label'] ) . '</h2>';
			if ( $field['help'] ) {
				echo '<p>' . esc_html( $field['help'] ) . '</p>';
			}
			echo '</section>';
			return;
		}

		$field_id     = self::field_id( $form_id, $index, $field );
		$help_id      = $field['help'] ? $field_id . '-help' : '';
		$error        = isset( $errors[ $field['name'] ] ) ? (string) $errors[ $field['name'] ] : '';
		$error_id     = $error ? $field_id . '-error' : '';
		$described_by = trim( $help_id . ' ' . $error_id );
		$value        = isset( $values[ $field['name'] ] ) ? (string) $values[ $field['name'] ] : '';
		$required     = $field['required'];
		$wide         = in_array( $field['type'], array( 'textarea', 'radio', 'scale', 'checkbox' ), true );
		$class        = $wide ? 'fmpf-field fmpf-field--wide' : 'fmpf-field';

		if ( in_array( $field['type'], array( 'radio', 'scale' ), true ) ) {
			echo '<fieldset id="' . esc_attr( $field_id ) . '" class="' . esc_attr( $class . ' fmpf-fieldset' . ( $error ? ' is-invalid' : '' ) ) . '"' . ( $described_by ? ' aria-describedby="' . esc_attr( $described_by ) . '"' : '' ) . ( $error ? ' aria-invalid="true" tabindex="-1"' : '' ) . '>';
			echo '<legend class="fmpf-label">' . esc_html( self::label_text( $field ) ) . '</legend>';
			echo '<div class="fmpf-choice-group' . ( 'scale' === $field['type'] ? ' fmpf-scale' : '' ) . '">';

			foreach ( $field['options'] as $option_index => $option ) {
				$option_id = $field_id . '-' . $option_index;
				echo '<label for="' . esc_attr( $option_id ) . '"><input id="' . esc_attr( $option_id ) . '" type="radio" name="' . esc_attr( $field['name'] ) . '" value="' . esc_attr( $option ) . '"' . checked( $value, $option, false ) . ( $required ? ' required aria-required="true"' : '' ) . '><span>' . esc_html( $option ) . '</span></label>';
			}

			echo '</div>';
			self::render_help( $field, $help_id );
			self::render_error( $error, $error_id );
			echo '</fieldset>';
			return;
		}

		echo '<div class="' . esc_attr( $class . ( $error ? ' is-invalid' : '' ) ) . '">';

		if ( 'checkbox' === $field['type'] ) {
			echo '<span class="fmpf-label">' . esc_html( self::label_text( $field ) ) . '</span>';
			echo '<label class="fmpf-checkbox" for="' . esc_attr( $field_id ) . '"><input id="' . esc_attr( $field_id ) . '" type="checkbox" name="' . esc_attr( $field['name'] ) . '" value="1"' . checked( $value, '1', false ) . ( $required ? ' required aria-required="true"' : '' ) . ( $described_by ? ' aria-describedby="' . esc_attr( $described_by ) . '"' : '' ) . ( $error ? ' aria-invalid="true"' : '' ) . '><span>' . esc_html( $yes_label ) . '</span></label>';
			self::render_help( $field, $help_id );
			self::render_error( $error, $error_id );
			echo '</div>';
			return;
		}

		echo '<label class="fmpf-label" for="' . esc_attr( $field_id ) . '">' . esc_html( self::label_text( $field ) ) . '</label>';
		$common = ( $required ? ' required aria-required="true"' : '' ) . ( $described_by ? ' aria-describedby="' . esc_attr( $described_by ) . '"' : '' ) . ( $error ? ' aria-invalid="true"' : '' );

		if ( 'textarea' === $field['type'] ) {
			echo '<textarea id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field['name'] ) . '" rows="5" maxlength="5000"' . $common . '>' . esc_textarea( $value ) . '</textarea>';
		} elseif ( 'select' === $field['type'] ) {
			echo '<select id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field['name'] ) . '"' . $common . '><option value="">' . esc_html( $select_label ) . '</option>';
			foreach ( $field['options'] as $option ) {
				echo '<option value="' . esc_attr( $option ) . '"' . selected( $value, $option, false ) . '>' . esc_html( $option ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'number' === $field['type'] ) {
			echo '<input id="' . esc_attr( $field_id ) . '" type="number" step="any" name="' . esc_attr( $field['name'] ) . '" value="' . esc_attr( $value ) . '"' . $common . '>';
		} else {
			$maxlength    = 'tel' === $field['type'] ? 40 : 500;
			$autocomplete = 'email' === $field['type'] ? 'email' : ( 'tel' === $field['type'] ? 'tel' : 'off' );
			echo '<input id="' . esc_attr( $field_id ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $field['name'] ) . '" value="' . esc_attr( $value ) . '" maxlength="' . esc_attr( (string) $maxlength ) . '" autocomplete="' . esc_attr( $autocomplete ) . '"' . $common . '>';
		}

		self::render_help( $field, $help_id );
		self::render_error( $error, $error_id );
		echo '</div>';
	}

	private static function render_help( array $field, string $help_id ): void {
		if ( $field['help'] && $help_id ) {
			echo '<small id="' . esc_attr( $help_id ) . '" class="fmpf-help">' . esc_html( $field['help'] ) . '</small>';
		}
	}

	private static function render_error( string $error, string $error_id ): void {
		if ( $error && $error_id ) {
			echo '<p id="' . esc_attr( $error_id ) . '" class="fmpf-field-error">' . esc_html( $error ) . '</p>';
		}
	}

	private static function field_id( int $form_id, int $index, array $field ): string {
		return 'fmpf-' . $form_id . '-' . $index . '-' . $field['name'];
	}

	private static function label_text( array $field ): string {
		if ( ! $field['required'] ) {
			return $field['label'];
		}

		return sprintf(
			/* translators: %s: Field label. */
			__( '%s (required)', 'free-mpro-forms' ),
			$field['label']
		);
	}

	private static function redirect( string $url, string $status, int $form_id, string $token = '' ): void {
		$url  = preg_replace( '/#.*$/', '', $url ) ?: home_url( '/' );
		$url  = remove_query_arg( array( 'fmpf_status', 'fmpf_state', 'fmpf_form' ), $url );
		$args = array(
			'fmpf_status' => $status,
			'fmpf_form'   => $form_id,
		);

		if ( $token ) {
			$args['fmpf_state'] = $token;
		}

		wp_safe_redirect( add_query_arg( $args, $url ) . '#fmpf-form-' . $form_id );
		exit;
	}
}
