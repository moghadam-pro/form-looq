<?php

namespace FreeMPROForms;

defined( 'ABSPATH' ) || exit;

final class Form_Manager {
	private const META_FIELDS = '_fmpf_fields';
	private const META_TYPE   = '_fmpf_submission_type';

	public static function init(): void {
		add_action( 'init', array( self::class, 'register_post_type' ), 11 );
		add_action( 'add_meta_boxes_fmpf_form', array( self::class, 'add_meta_boxes' ) );
		add_action( 'save_post_fmpf_form', array( self::class, 'save' ), 10, 2 );
		add_shortcode( 'free_mpro_form', array( self::class, 'shortcode' ) );
		add_action( 'admin_post_fmpf_submit', array( self::class, 'handle_submission' ) );
		add_action( 'admin_post_nopriv_fmpf_submit', array( self::class, 'handle_submission' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ) );
		add_filter( 'manage_fmpf_form_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_fmpf_form_posts_custom_column', array( self::class, 'column_content' ), 10, 2 );
		add_action( 'admin_menu', array( self::class, 'register_menu' ), 20 );
	}

	public static function register_assets(): void {
		wp_register_style( 'free-mpro-forms', plugins_url( 'assets/css/forms.css', FREE_MPRO_FORMS_FILE ), array(), FREE_MPRO_FORMS_VERSION );
	}

	public static function register_post_type(): void {
		register_post_type(
			'fmpf_form',
			array(
				'labels' => array(
					'name'          => __( 'Forms', 'free-mpro-forms' ),
					'singular_name' => __( 'Form', 'free-mpro-forms' ),
					'add_new_item'  => __( 'Add New Form', 'free-mpro-forms' ),
					'edit_item'     => __( 'Edit Form', 'free-mpro-forms' ),
					'not_found'     => __( 'No forms found', 'free-mpro-forms' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => false,
				'show_in_rest' => false,
				'supports'     => array( 'title' ),
			)
		);
	}

	public static function register_menu(): void {
		add_menu_page(
			__( 'Free MPRO Forms', 'free-mpro-forms' ),
			__( 'Forms', 'free-mpro-forms' ),
			'edit_posts',
			'free-mpro-forms',
			array( self::class, 'overview' ),
			'dashicons-feedback',
			25
		);
		add_submenu_page( 'free-mpro-forms', __( 'Forms', 'free-mpro-forms' ), __( 'Forms', 'free-mpro-forms' ), 'edit_posts', 'edit.php?post_type=fmpf_form' );
		add_submenu_page( 'free-mpro-forms', __( 'Submissions', 'free-mpro-forms' ), __( 'Submissions', 'free-mpro-forms' ), 'manage_options', 'edit.php?post_type=fmpf_submission' );
		remove_submenu_page( 'free-mpro-forms', 'free-mpro-forms' );
	}

	public static function overview(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Free MPRO Forms', 'free-mpro-forms' ); ?></h1>
			<p><?php esc_html_e( 'Create lightweight forms and keep their submissions privately inside WordPress.', 'free-mpro-forms' ); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=fmpf_form' ) ); ?>"><?php esc_html_e( 'Manage Forms', 'free-mpro-forms' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=fmpf_submission' ) ); ?>"><?php esc_html_e( 'View Submissions', 'free-mpro-forms' ); ?></a>
			</p>
		</div>
		<?php
	}

	public static function add_meta_boxes(): void {
		add_meta_box( 'fmpf_builder', __( 'Form fields', 'free-mpro-forms' ), array( self::class, 'render_builder' ), 'fmpf_form', 'normal', 'high' );
		add_meta_box( 'fmpf_embed', __( 'Embed', 'free-mpro-forms' ), array( self::class, 'render_embed' ), 'fmpf_form', 'side', 'high' );
	}

	public static function render_builder( \WP_Post $post ): void {
		wp_nonce_field( 'fmpf_save_form', 'fmpf_nonce' );
		$fields = get_post_meta( $post->ID, self::META_FIELDS, true );
		$type   = get_post_meta( $post->ID, self::META_TYPE, true ) ?: 'general';
		?>
		<p><label for="fmpf-submission-type"><strong><?php esc_html_e( 'Submission type', 'free-mpro-forms' ); ?></strong></label></p>
		<input id="fmpf-submission-type" name="fmpf_submission_type" class="regular-text" value="<?php echo esc_attr( $type ); ?>" pattern="[a-z0-9_-]+" maxlength="64">
		<p><label for="fmpf-fields"><strong><?php esc_html_e( 'Fields — one per line', 'free-mpro-forms' ); ?></strong></label></p>
		<p class="description"><?php esc_html_e( 'Format: type|name|label|required|options|help. Types: section, text, email, tel, number, textarea, select, radio, scale, checkbox. Separate options with commas. Labels, options and help text support Persian and other Unicode languages.', 'free-mpro-forms' ); ?></p>
		<textarea id="fmpf-fields" name="fmpf_fields" class="large-text code" rows="16" spellcheck="false" maxlength="50000"><?php echo esc_textarea( (string) $fields ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Example: email|email|Email address|required||We will never publish your email.', 'free-mpro-forms' ); ?></p>
		<?php
	}

	public static function render_embed( \WP_Post $post ): void {
		echo '<p><code>[free_mpro_form id="' . esc_html( (string) $post->ID ) . '"]</code></p>';
		echo '<p>' . esc_html__( 'Use this shortcode in the block editor, Elementor or any shortcode-compatible page builder.', 'free-mpro-forms' ) . '</p>';
	}

	public static function save( int $post_id, \WP_Post $post ): void {
		if (
			! isset( $_POST['fmpf_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmpf_nonce'] ) ), 'fmpf_save_form' ) ||
			! current_user_can( 'edit_post', $post_id ) ||
			wp_is_post_revision( $post_id ) ||
			'fmpf_form' !== $post->post_type
		) {
			return;
		}

		$fields = isset( $_POST['fmpf_fields'] )
			? Field_Validator::sanitize_definition( wp_unslash( $_POST['fmpf_fields'] ) )
			: '';
		$type   = isset( $_POST['fmpf_submission_type'] )
			? sanitize_key( wp_unslash( $_POST['fmpf_submission_type'] ) )
			: 'general';

		update_post_meta( $post_id, self::META_FIELDS, $fields );
		update_post_meta( $post_id, self::META_TYPE, $type ?: 'general' );
	}

	public static function shortcode( array $atts ): string {
		wp_enqueue_style( 'free-mpro-forms' );

		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'button' => __( 'Submit', 'free-mpro-forms' ),
				'sent'   => __( 'Thank you. Your submission has been recorded.', 'free-mpro-forms' ),
				'error'  => __( 'Please review the form and try again.', 'free-mpro-forms' ),
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

		$direction = in_array( $atts['dir'], array( 'rtl', 'ltr' ), true ) ? $atts['dir'] : ( is_rtl() ? 'rtl' : 'ltr' );
		$is_rtl    = 'rtl' === $direction;
		$select    = $atts['select'] ?: ( $is_rtl ? 'انتخاب کنید' : __( 'Select', 'free-mpro-forms' ) );
		$yes       = $atts['yes'] ?: ( $is_rtl ? 'بله' : __( 'Yes', 'free-mpro-forms' ) );
		$status    = isset( $_GET['fmpf_status'] ) ? sanitize_key( wp_unslash( $_GET['fmpf_status'] ) ) : '';
		$notice_id = 'fmpf-notice-' . $form_id;
		$form_desc = in_array( $status, array( 'sent', 'error' ), true ) ? $notice_id : '';
		ob_start();
		?>
		<div class="fmpf-form-wrap" dir="<?php echo esc_attr( $direction ); ?>">
			<?php if ( 'sent' === $status ) : ?>
				<p id="<?php echo esc_attr( $notice_id ); ?>" class="fmpf-notice" role="status" tabindex="-1"><?php echo esc_html( (string) $atts['sent'] ); ?></p>
			<?php endif; ?>
			<?php if ( 'error' === $status ) : ?>
				<p id="<?php echo esc_attr( $notice_id ); ?>" class="fmpf-notice is-error" role="alert" tabindex="-1"><?php echo esc_html( (string) $atts['error'] ); ?></p>
			<?php endif; ?>
			<form class="fmpf-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"<?php if ( $form_desc ) : ?> aria-describedby="<?php echo esc_attr( $form_desc ); ?>"<?php endif; ?>>
				<input type="hidden" name="action" value="fmpf_submit">
				<input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $form_id ); ?>">
				<input type="hidden" name="fmpf_started_at" value="<?php echo esc_attr( (string) time() ); ?>">
				<?php wp_nonce_field( 'fmpf_submit_' . $form_id, 'fmpf_nonce' ); ?>
				<?php foreach ( $fields as $index => $field ) : ?>
					<?php self::render_field( $field, $select, $yes, $form_id, $index ); ?>
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
			self::redirect( $referer, 'error' );
		}

		$fields = Field_Validator::parse_definition( (string) get_post_meta( $form_id, self::META_FIELDS, true ) );
		if ( ! $fields ) {
			self::redirect( $referer, 'error' );
		}

		$validation = Field_Validator::validate_submission( $fields, $_POST );

		if ( ! $validation['valid'] ) {
			self::redirect( $referer, 'error' );
		}

		$type  = (string) get_post_meta( $form_id, self::META_TYPE, true );
		$title = get_the_title( $form_id ) . ' — ' . current_time( 'mysql' );

		if ( ! Submission_Manager::store( $type ?: 'general', $title, $validation['data'], $form_id ) ) {
			self::redirect( $referer, 'error' );
		}

		self::redirect( $referer, 'sent' );
	}

	public static function columns( array $columns ): array {
		$columns['fmpf_shortcode'] = __( 'Shortcode', 'free-mpro-forms' );
		return $columns;
	}

	public static function column_content( string $column, int $post_id ): void {
		if ( 'fmpf_shortcode' === $column ) {
			echo '<code>[free_mpro_form id="' . esc_html( (string) $post_id ) . '"]</code>';
		}
	}

	private static function render_field( array $field, string $select_label, string $yes_label, int $form_id, int $index ): void {
		if ( 'section' === $field['type'] ) {
			echo '<section class="fmpf-section"><h2>' . esc_html( $field['label'] ) . '</h2>';
			if ( $field['help'] ) {
				echo '<p>' . esc_html( $field['help'] ) . '</p>';
			}
			echo '</section>';
			return;
		}

		$field_id = 'fmpf-' . $form_id . '-' . $index . '-' . $field['name'];
		$help_id  = $field['help'] ? $field_id . '-help' : '';
		$required = $field['required'];
		$wide     = in_array( $field['type'], array( 'textarea', 'radio', 'scale', 'checkbox' ), true );
		$class    = $wide ? 'fmpf-field fmpf-field--wide' : 'fmpf-field';

		if ( in_array( $field['type'], array( 'radio', 'scale' ), true ) ) {
			echo '<fieldset class="' . esc_attr( $class . ' fmpf-fieldset' ) . '"' . ( $help_id ? ' aria-describedby="' . esc_attr( $help_id ) . '"' : '' ) . '>';
			echo '<legend class="fmpf-label">' . esc_html( self::label_text( $field ) ) . '</legend>';
			echo '<div class="fmpf-choice-group' . ( 'scale' === $field['type'] ? ' fmpf-scale' : '' ) . '">';

			foreach ( $field['options'] as $option_index => $option ) {
				$option_id = $field_id . '-' . $option_index;
				echo '<label for="' . esc_attr( $option_id ) . '"><input id="' . esc_attr( $option_id ) . '" type="radio" name="' . esc_attr( $field['name'] ) . '" value="' . esc_attr( $option ) . '"' . ( $required ? ' required aria-required="true"' : '' ) . '><span>' . esc_html( $option ) . '</span></label>';
			}

			echo '</div>';
			self::render_help( $field, $help_id );
			echo '</fieldset>';
			return;
		}

		echo '<div class="' . esc_attr( $class ) . '">';

		if ( 'checkbox' === $field['type'] ) {
			echo '<span class="fmpf-label">' . esc_html( self::label_text( $field ) ) . '</span>';
			echo '<label class="fmpf-checkbox" for="' . esc_attr( $field_id ) . '"><input id="' . esc_attr( $field_id ) . '" type="checkbox" name="' . esc_attr( $field['name'] ) . '" value="1"' . ( $required ? ' required aria-required="true"' : '' ) . ( $help_id ? ' aria-describedby="' . esc_attr( $help_id ) . '"' : '' ) . '><span>' . esc_html( $yes_label ) . '</span></label>';
			self::render_help( $field, $help_id );
			echo '</div>';
			return;
		}

		echo '<label class="fmpf-label" for="' . esc_attr( $field_id ) . '">' . esc_html( self::label_text( $field ) ) . '</label>';

		if ( 'textarea' === $field['type'] ) {
			echo '<textarea id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field['name'] ) . '" rows="5" maxlength="5000"' . ( $required ? ' required aria-required="true"' : '' ) . ( $help_id ? ' aria-describedby="' . esc_attr( $help_id ) . '"' : '' ) . '></textarea>';
		} elseif ( 'select' === $field['type'] ) {
			echo '<select id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field['name'] ) . '"' . ( $required ? ' required aria-required="true"' : '' ) . ( $help_id ? ' aria-describedby="' . esc_attr( $help_id ) . '"' : '' ) . '><option value="">' . esc_html( $select_label ) . '</option>';
			foreach ( $field['options'] as $option ) {
				echo '<option value="' . esc_attr( $option ) . '">' . esc_html( $option ) . '</option>';
			}
			echo '</select>';
		} else {
			$maxlength = 'tel' === $field['type'] ? 40 : ( 'number' === $field['type'] ? 64 : 500 );
			echo '<input id="' . esc_attr( $field_id ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $field['name'] ) . '" maxlength="' . esc_attr( (string) $maxlength ) . '"' . ( $required ? ' required aria-required="true"' : '' ) . ( $help_id ? ' aria-describedby="' . esc_attr( $help_id ) . '"' : '' ) . '>';
		}

		self::render_help( $field, $help_id );
		echo '</div>';
	}

	private static function render_help( array $field, string $help_id ): void {
		if ( $field['help'] && $help_id ) {
			echo '<small id="' . esc_attr( $help_id ) . '" class="fmpf-help">' . esc_html( $field['help'] ) . '</small>';
		}
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

	private static function redirect( string $url, string $status ): void {
		wp_safe_redirect( add_query_arg( 'fmpf_status', $status, remove_query_arg( 'fmpf_status', $url ) ) );
		exit;
	}
}
