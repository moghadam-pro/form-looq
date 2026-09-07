<?php

namespace FormLooq;

defined( 'ABSPATH' ) || exit;

final class Frontend_Form {
	public static function init(): void {
		add_shortcode( 'looq_form', array( self::class, 'shortcode' ) );

		// Pre-rename aliases, so content saved under either earlier product name
		// (Free MPRO Forms, then MPRO Forms) keeps rendering unchanged. These
		// tag names are deliberately not prefixed with this plugin's current
		// prefix: they must match exactly what old content already contains.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedElementFound
		add_shortcode( 'mpro_form', array( self::class, 'shortcode' ) );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedElementFound
		add_shortcode( 'free_mpro_form', array( self::class, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ) );
		add_action( 'admin_post_looq_submit', array( self::class, 'handle_submission' ) );
		add_action( 'admin_post_nopriv_looq_submit', array( self::class, 'handle_submission' ) );
		add_action( 'template_redirect', array( self::class, 'protect_state_page' ), 0 );
	}

	/**
	 * Register frontend assets so they are only enqueued when a form renders.
	 */
	public static function register_assets(): void {
		if ( ! Settings::get( 'output_css', true ) ) {
			return;
		}

		wp_register_style(
			'form-looq',
			plugins_url( 'assets/css/forms.css', FORM_LOOQ_FILE ),
			array(),
			FORM_LOOQ_VERSION
		);
	}

	/**
	 * Load a renderable form, or null when it is missing, inactive, or empty.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function renderable_form( int $form_id ): ?array {
		$form = Form_Repository::get( $form_id );

		if ( ! $form || Form_Repository::STATUS_ACTIVE !== $form['status'] || ! $form['fields'] ) {
			return null;
		}

		return $form;
	}

	public static function protect_state_page(): void {
		if ( ! isset( $_GET['looq_state'] ) ) {
			return;
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			// This exact, unprefixed name is a cross-plugin convention that page
			// caching plugins (WP Super Cache, W3 Total Cache, and others) look
			// for directly - prefixing it would silently stop it from working.
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
			define( 'DONOTCACHEPAGE', true );
		}

		nocache_headers();
	}

	public static function shortcode( array $atts ): string {
		$atts = shortcode_atts(
			array(
				// Empty defaults let the form's own settings supply the wording.
				'id'     => 0,
				'button' => '',
				'sent'   => '',
				'error'  => __( 'Please review the highlighted fields and try again.', 'form-looq' ),
				'dir'    => 'auto',
				'select' => '',
				'yes'    => '',
			),
			$atts,
			'looq_form'
		);

		$form_id = absint( $atts['id'] );
		$form    = self::renderable_form( $form_id );

		if ( ! $form ) {
			return Plugin::current_user_can() ? '<p>' . esc_html__( 'Select an active form.', 'form-looq' ) . '</p>' : '';
		}

		$fields   = $form['fields'];
		$settings = $form['settings'];

		if ( '' === (string) $atts['button'] ) {
			$atts['button'] = $settings['submit_label'];
		}

		if ( '' === (string) $atts['sent'] ) {
			$atts['sent'] = $settings['success_message'];
		}

		Form_Repository::record_view( $form_id );

		wp_enqueue_style( 'form-looq' );
		wp_enqueue_script(
			'form-looq-frontend',
			plugins_url( 'assets/js/forms.js', FORM_LOOQ_FILE ),
			array(),
			FORM_LOOQ_VERSION,
			true
		);

		$direction   = in_array( $atts['dir'], array( 'rtl', 'ltr' ), true ) ? $atts['dir'] : ( is_rtl() ? 'rtl' : 'ltr' );
		// Text direction is not a language: an Arabic, Hebrew, or Urdu site is
		// also RTL, so the fallback word has to come from the current locale's
		// translation, not from a hardcoded Persian string.
		$select      = $atts['select'] ?: __( 'Select', 'form-looq' );
		$yes         = $atts['yes'] ?: __( 'Yes', 'form-looq' );
		$status_form = isset( $_GET['looq_form'] ) ? absint( $_GET['looq_form'] ) : 0;
		$status      = $status_form === $form_id && isset( $_GET['looq_status'] ) ? sanitize_key( wp_unslash( $_GET['looq_status'] ) ) : '';
		$token       = $status_form === $form_id && isset( $_GET['looq_state'] ) ? sanitize_text_field( wp_unslash( $_GET['looq_state'] ) ) : '';
		$state       = 'error' === $status && $token ? Submission_State::consume( $token, $form_id ) : array();
		$values      = is_array( $state['values'] ?? null ) ? $state['values'] : array();
		$errors      = is_array( $state['errors'] ?? null ) ? $state['errors'] : array();
		$has_error   = 'error' === $status;
		$notice_id   = 'looq-notice-' . $form_id;
		$form_id_attr = 'looq-form-' . $form_id;

		$layout = 'two-column' === ( $settings['layout'] ?? '' ) ? ' looq-layout-two-column' : '';

		ob_start();
		?>
		<div id="<?php echo esc_attr( $form_id_attr ); ?>" class="looq-form-wrap<?php echo esc_attr( $layout ); ?>" dir="<?php echo esc_attr( $direction ); ?>" data-looq-has-errors="<?php echo $has_error ? '1' : '0'; ?>">
			<?php if ( 'sent' === $status ) : ?>
				<p id="<?php echo esc_attr( $notice_id ); ?>" class="looq-notice" role="status" tabindex="-1"><?php echo esc_html( (string) $atts['sent'] ); ?></p>
			<?php elseif ( $has_error ) : ?>
				<?php self::render_error_summary( $fields, $errors, (string) $atts['error'], $notice_id, $form_id ); ?>
			<?php endif; ?>

			<form class="looq-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"<?php if ( in_array( $status, array( 'sent', 'error' ), true ) ) : ?> aria-describedby="<?php echo esc_attr( $notice_id ); ?>"<?php endif; ?>>
				<input type="hidden" name="action" value="looq_submit">
				<input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $form_id ); ?>">
				<input type="hidden" name="looq_started_at" value="<?php echo esc_attr( (string) time() ); ?>">
				<?php wp_nonce_field( 'looq_submit_' . $form_id, 'looq_nonce' ); ?>

				<?php foreach ( $fields as $index => $field ) : ?>
					<?php self::render_field( $field, $select, $yes, $form_id, $index, $values, $errors ); ?>
				<?php endforeach; ?>

				<?php if ( ! empty( $settings['honeypot'] ) ) : ?>
					<label class="looq-honeypot" aria-hidden="true" for="<?php echo esc_attr( 'looq-website-' . $form_id ); ?>">
						<?php esc_html_e( 'Leave this field empty', 'form-looq' ); ?>
						<input id="<?php echo esc_attr( 'looq-website-' . $form_id ); ?>" name="website" tabindex="-1" autocomplete="off">
					</label>
				<?php endif; ?>
				<button type="submit"><?php echo esc_html( (string) $atts['button'] ); ?></button>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public static function handle_submission(): void {
		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$referer = wp_validate_redirect( wp_get_referer() ?: '', home_url( '/' ) );
		$started = isset( $_POST['looq_started_at'] ) ? absint( $_POST['looq_started_at'] ) : 0;
		$elapsed = $started > 0 ? time() - $started : 0;

		$form = self::renderable_form( $form_id );

		if (
			! $form ||
			! isset( $_POST['looq_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['looq_nonce'] ) ), 'looq_submit_' . $form_id ) ||
			! empty( $_POST['website'] ) ||
			$elapsed < 2 ||
			$elapsed > DAY_IN_SECONDS
		) {
			self::redirect( $referer, 'error', $form_id );
		}

		$fields = $form['fields'];

		$validation = Submission_Validator::validate( $fields, $_POST );
		if ( ! $validation['valid'] ) {
			$token = Submission_State::create( $form_id, $validation['values'], $validation['errors'] );
			self::redirect( $referer, 'error', $form_id, $token );
		}

		$context = array(
			'ip'         => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'referer'    => $referer,
		);

		if ( ! Entry_Repository::create( $form_id, $validation['data'], $context ) ) {
			$errors = array( '_form' => __( 'We could not save your submission. Please try again.', 'form-looq' ) );
			$token  = Submission_State::create( $form_id, $validation['values'], $errors );
			self::redirect( $referer, 'error', $form_id, $token );
		}

		$redirect_url = (string) ( $form['settings']['redirect_url'] ?? '' );

		if ( '' !== $redirect_url && wp_http_validate_url( $redirect_url ) ) {
			// wp_safe_redirect() only allows WP_ALLOWED_REDIRECT_HOSTS, which would
			// silently fall back to the home page for the external destination a
			// capability-holding form administrator deliberately configured here
			// (this is never visitor-supplied). Rather than bypass the safe-redirect
			// helper, the destination's own host is added to the allow-list for the
			// duration of this one redirect, so wp_safe_redirect() itself honours it.
			$redirect_host = wp_parse_url( $redirect_url, PHP_URL_HOST );

			if ( is_string( $redirect_host ) && '' !== $redirect_host ) {
				add_filter(
					'allowed_redirect_hosts',
					static function ( array $hosts ) use ( $redirect_host ): array {
						$hosts[] = $redirect_host;
						return $hosts;
					}
				);
			}

			wp_safe_redirect( $redirect_url );
			exit;
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
		<div id="<?php echo esc_attr( $notice_id ); ?>" class="looq-notice is-error looq-error-summary" role="alert" tabindex="-1">
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
			echo '<section class="looq-section"><h2>' . esc_html( $field['label'] ) . '</h2>';
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
		$wide         = 'half' !== ( $field['width'] ?? 'full' )
			|| in_array( $field['type'], array( 'textarea', 'radio', 'scale', 'checkbox' ), true );
		$class        = $wide ? 'looq-field looq-field--wide' : 'looq-field';
		$placeholder  = '' !== (string) ( $field['placeholder'] ?? '' )
			? ' placeholder="' . esc_attr( (string) $field['placeholder'] ) . '"'
			: '';

		if ( in_array( $field['type'], array( 'radio', 'scale' ), true ) ) {
			echo '<fieldset id="' . esc_attr( $field_id ) . '" class="' . esc_attr( $class . ' looq-fieldset' . ( $error ? ' is-invalid' : '' ) ) . '"' . ( $described_by ? ' aria-describedby="' . esc_attr( $described_by ) . '"' : '' ) . ( $error ? ' aria-invalid="true" tabindex="-1"' : '' ) . '>';
			echo '<legend class="looq-label">' . esc_html( self::label_text( $field ) ) . '</legend>';
			echo '<div class="looq-choice-group' . ( 'scale' === $field['type'] ? ' looq-scale' : '' ) . '">';

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
			echo '<span class="looq-label">' . esc_html( self::label_text( $field ) ) . '</span>';
			echo '<label class="looq-checkbox" for="' . esc_attr( $field_id ) . '"><input id="' . esc_attr( $field_id ) . '" type="checkbox" name="' . esc_attr( $field['name'] ) . '" value="1"' . checked( $value, '1', false ) . ( $required ? ' required aria-required="true"' : '' ) . ( $described_by ? ' aria-describedby="' . esc_attr( $described_by ) . '"' : '' ) . ( $error ? ' aria-invalid="true"' : '' ) . '><span>' . esc_html( $yes_label ) . '</span></label>';
			self::render_help( $field, $help_id );
			self::render_error( $error, $error_id );
			echo '</div>';
			return;
		}

		echo '<label class="looq-label" for="' . esc_attr( $field_id ) . '">' . esc_html( self::label_text( $field ) ) . '</label>';
		$common = ( $required ? ' required aria-required="true"' : '' ) . ( $described_by ? ' aria-describedby="' . esc_attr( $described_by ) . '"' : '' ) . ( $error ? ' aria-invalid="true"' : '' );

		if ( 'textarea' === $field['type'] ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attribute fragments are assembled from fixed strings and individually escaped values above.
			echo '<textarea id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field['name'] ) . '" rows="5" maxlength="5000"' . $placeholder . $common . '>' . esc_textarea( $value ) . '</textarea>';
		} elseif ( 'select' === $field['type'] ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The common attribute fragment contains fixed strings and an esc_attr()-escaped ID.
			echo '<select id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field['name'] ) . '"' . $common . '><option value="">' . esc_html( $select_label ) . '</option>';
			foreach ( $field['options'] as $option ) {
				echo '<option value="' . esc_attr( $option ) . '"' . selected( $value, $option, false ) . '>' . esc_html( $option ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'number' === $field['type'] ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The common attribute fragment contains fixed strings and an esc_attr()-escaped ID.
			echo '<input id="' . esc_attr( $field_id ) . '" type="number" step="any" name="' . esc_attr( $field['name'] ) . '" value="' . esc_attr( $value ) . '"' . $common . '>';
		} else {
			$maxlength    = 'tel' === $field['type'] ? 40 : 500;
			$autocomplete = 'email' === $field['type'] ? 'email' : ( 'tel' === $field['type'] ? 'tel' : 'off' );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attribute fragments are assembled from fixed strings and individually escaped values above.
			echo '<input id="' . esc_attr( $field_id ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $field['name'] ) . '" value="' . esc_attr( $value ) . '" maxlength="' . esc_attr( (string) $maxlength ) . '" autocomplete="' . esc_attr( $autocomplete ) . '"' . $placeholder . $common . '>';
		}

		self::render_help( $field, $help_id );
		self::render_error( $error, $error_id );
		echo '</div>';
	}

	private static function render_help( array $field, string $help_id ): void {
		if ( $field['help'] && $help_id ) {
			echo '<small id="' . esc_attr( $help_id ) . '" class="looq-help">' . esc_html( $field['help'] ) . '</small>';
		}
	}

	private static function render_error( string $error, string $error_id ): void {
		if ( $error && $error_id ) {
			echo '<p id="' . esc_attr( $error_id ) . '" class="looq-field-error">' . esc_html( $error ) . '</p>';
		}
	}

	private static function field_id( int $form_id, int $index, array $field ): string {
		return 'looq-' . $form_id . '-' . $index . '-' . $field['name'];
	}

	private static function label_text( array $field ): string {
		if ( ! $field['required'] ) {
			return $field['label'];
		}

		return sprintf(
			/* translators: %s: Field label. */
			__( '%s (required)', 'form-looq' ),
			$field['label']
		);
	}

	private static function redirect( string $url, string $status, int $form_id, string $token = '' ): void {
		$url  = preg_replace( '/#.*$/', '', $url ) ?: home_url( '/' );
		$url  = remove_query_arg( array( 'looq_status', 'looq_state', 'looq_form' ), $url );
		$args = array(
			'looq_status' => $status,
			'looq_form'   => $form_id,
		);

		if ( $token ) {
			$args['looq_state'] = $token;
		}

		wp_safe_redirect( add_query_arg( $args, $url ) . '#looq-form-' . $form_id );
		exit;
	}
}
