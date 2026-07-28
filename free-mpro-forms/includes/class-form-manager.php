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
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ) );
		add_filter( 'manage_fmpf_form_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_fmpf_form_posts_custom_column', array( self::class, 'column_content' ), 10, 2 );
		add_action( 'admin_menu', array( self::class, 'register_menu' ), 20 );
	}

	public static function register_assets(): void {
		wp_register_style(
			'free-mpro-forms',
			plugins_url( 'assets/css/forms.css', FREE_MPRO_FORMS_FILE ),
			array(),
			FREE_MPRO_FORMS_VERSION
		);
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

		add_submenu_page(
			'free-mpro-forms',
			__( 'Forms', 'free-mpro-forms' ),
			__( 'Forms', 'free-mpro-forms' ),
			'edit_posts',
			'edit.php?post_type=fmpf_form'
		);

		add_submenu_page(
			'free-mpro-forms',
			__( 'Submissions', 'free-mpro-forms' ),
			__( 'Submissions', 'free-mpro-forms' ),
			'manage_options',
			'edit.php?post_type=fmpf_submission'
		);

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
			? Field_Validator::sanitize_definition( sanitize_textarea_field( wp_unslash( $_POST['fmpf_fields'] ) ) )
			: '';
		$type   = isset( $_POST['fmpf_submission_type'] )
			? sanitize_key( wp_unslash( $_POST['fmpf_submission_type'] ) )
			: 'general';

		update_post_meta( $post_id, self::META_FIELDS, $fields );
		update_post_meta( $post_id, self::META_TYPE, $type ?: 'general' );
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
}
