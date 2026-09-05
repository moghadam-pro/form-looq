<?php
/**
 * Admin menu registration, shared chrome, and asset loading.
 *
 * @package MPROForms
 */

namespace MPROForms\Admin;

use MPROForms\Plugin;
use MPROForms\Settings;

defined( 'ABSPATH' ) || exit;

final class Admin {
	/**
	 * Menu position 11 places the plugin immediately below Media (10).
	 */
	private const MENU_POSITION = 11;

	/**
	 * @var array<int, string>
	 */
	private static array $hooks = array();

	/**
	 * Slugs that stay routable but are kept out of the sidebar.
	 *
	 * @var array<int, string>
	 */
	private static array $hidden = array();

	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'register_menu' ), 9 );
		add_action( 'admin_head', array( self::class, 'hide_pages' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_action( 'admin_print_scripts', array( self::class, 'no_conflict' ), 100 );
		add_action( 'admin_print_styles', array( self::class, 'no_conflict' ), 100 );
		add_filter( 'plugin_action_links_' . plugin_basename( MPRO_FORMS_FILE ), array( self::class, 'action_links' ) );
		add_filter( 'admin_body_class', array( self::class, 'body_class' ) );
	}

	/**
	 * Pages in submenu order.
	 *
	 * @return array<string, array{title: string, callback: callable, hidden?: bool}>
	 */
	public static function pages(): array {
		return array(
			Plugin::MENU_SLUG              => array(
				'title'    => __( 'Forms list', 'mpro-forms' ),
				'callback' => array( Page_Forms::class, 'render' ),
			),
			Plugin::MENU_SLUG . '-new'     => array(
				'title'    => __( 'New form', 'mpro-forms' ),
				'callback' => array( Page_New_Form::class, 'render' ),
			),
			Plugin::MENU_SLUG . '-inbox'   => array(
				'title'    => __( 'Inbox', 'mpro-forms' ),
				'callback' => array( Page_Inbox::class, 'render' ),
			),
			Plugin::MENU_SLUG . '-settings' => array(
				'title'    => __( 'Settings', 'mpro-forms' ),
				'callback' => array( Page_Settings::class, 'render' ),
			),
			Plugin::MENU_SLUG . '-tools'   => array(
				'title'    => __( 'Export', 'mpro-forms' ),
				'callback' => array( Page_Tools::class, 'render' ),
			),
			Plugin::MENU_SLUG . '-status'  => array(
				'title'    => __( 'System status', 'mpro-forms' ),
				'callback' => array( Page_Status::class, 'render' ),
			),
			Plugin::MENU_SLUG . '-help'    => array(
				'title'    => __( 'Help', 'mpro-forms' ),
				'callback' => array( Page_Help::class, 'render' ),
			),
			Plugin::MENU_SLUG . '-builder' => array(
				'title'    => __( 'Form builder', 'mpro-forms' ),
				'callback' => array( Page_Builder::class, 'render' ),
				'hidden'   => true,
			),
			Plugin::MENU_SLUG . '-entry'   => array(
				'title'    => __( 'Entry', 'mpro-forms' ),
				'callback' => array( Page_Entry::class, 'render' ),
				'hidden'   => true,
			),
		);
	}

	public static function register_menu(): void {
		$capability = Plugin::capability();

		add_menu_page(
			Plugin::menu_label(),
			Plugin::menu_label(),
			$capability,
			Plugin::MENU_SLUG,
			array( Page_Forms::class, 'render' ),
			Plugin::menu_icon(),
			self::MENU_POSITION
		);

		self::$hidden = array();

		foreach ( self::pages() as $slug => $page ) {
			$hook = add_submenu_page(
				Plugin::MENU_SLUG,
				$page['title'],
				$page['title'],
				$capability,
				$slug,
				$page['callback']
			);

			if ( $hook ) {
				self::$hooks[] = $hook;
			}

			if ( ! empty( $page['hidden'] ) ) {
				self::$hidden[] = $slug;
			}
		}
	}

	/**
	 * Drop hidden pages from the sidebar.
	 *
	 * This deliberately runs on admin_head rather than admin_menu.
	 * user_can_access_admin_page() decides access by looking the slug up in
	 * $submenu, so removing an entry during admin_menu makes WordPress refuse
	 * the page outright. admin_head fires after that check and before
	 * menu-header.php renders the sidebar, which is the one window where the
	 * entry can be removed without costing access to the page.
	 */
	public static function hide_pages(): void {
		foreach ( self::$hidden as $slug ) {
			remove_submenu_page( Plugin::MENU_SLUG, $slug );
		}
	}

	public static function is_plugin_screen(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return $screen instanceof \WP_Screen && in_array( $screen->id, self::$hooks, true );
	}

	/**
	 * @param string $classes Space separated body classes.
	 */
	public static function body_class( string $classes ): string {
		return self::is_plugin_screen() ? $classes . ' mpro-admin ' : $classes;
	}

	public static function enqueue( string $hook ): void {
		if ( ! in_array( $hook, self::$hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'mpro-admin',
			plugins_url( 'assets/css/admin.css', MPRO_FORMS_FILE ),
			array(),
			MPRO_FORMS_VERSION
		);

		wp_enqueue_script(
			'mpro-admin',
			plugins_url( 'assets/js/admin.js', MPRO_FORMS_FILE ),
			array(),
			MPRO_FORMS_VERSION,
			true
		);

		wp_localize_script(
			'mpro-admin',
			'mproAdmin',
			array(
				'copied'       => __( 'Copied to clipboard.', 'mpro-forms' ),
				'copyFailed'   => __( 'Copy failed. Select the text and copy it manually.', 'mpro-forms' ),
				'confirmLeave' => (bool) Settings::get( 'confirm_before_leaving', true ),
				'leaveWarning' => __( 'You have unsaved changes.', 'mpro-forms' ),
			)
		);
	}

	/**
	 * Strip third-party assets from plugin screens when no-conflict mode is on.
	 *
	 * Only handles registered outside WordPress core and this plugin are removed,
	 * which is what makes another plugin's script stop breaking the builder.
	 */
	public static function no_conflict(): void {
		if ( ! self::is_plugin_screen() || ! Settings::get( 'no_conflict_mode', false ) ) {
			return;
		}

		$core_url   = includes_url();
		$admin_url  = admin_url();
		$plugin_url = plugins_url( '', MPRO_FORMS_FILE );

		foreach ( array( wp_scripts(), wp_styles() ) as $registry ) {
			foreach ( (array) $registry->queue as $handle ) {
				$src = (string) ( $registry->registered[ $handle ]->src ?? '' );

				if ( '' === $src || ! str_starts_with( $src, 'http' ) ) {
					continue;
				}

				if (
					str_starts_with( $src, $core_url )
					|| str_starts_with( $src, $admin_url )
					|| str_starts_with( $src, $plugin_url )
				) {
					continue;
				}

				$registry->dequeue( $handle );
			}
		}
	}

	/**
	 * Render the header bar shown at the top of every plugin screen.
	 */
	public static function header( string $title, string $subtitle = '' ): void {
		?>
		<div class="mpro-header">
			<img class="mpro-header__logo" src="<?php echo esc_url( Plugin::logo_url() ); ?>" alt="" width="32" height="32">
			<div class="mpro-header__identity">
				<span class="mpro-header__name"><?php echo esc_html( Plugin::name() ); ?></span>
				<span class="mpro-header__version">v<?php echo esc_html( Plugin::version() ); ?></span>
			</div>
			<div class="mpro-header__page">
				<h1 class="mpro-header__title"><?php echo esc_html( $title ); ?></h1>
				<?php if ( '' !== $subtitle ) : ?>
					<p class="mpro-header__subtitle"><?php echo esc_html( $subtitle ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
		/*
		 * WordPress moves admin notices to just after the first <h1> unless a
		 * .wp-header-end marker says otherwise. Our <h1> lives inside the header
		 * bar, so without this the notice would be injected into that flex row.
		 */
		echo '<hr class="wp-header-end">';
	}

	/**
	 * @param array<int, string> $links Existing plugin row links.
	 * @return array<int, string>
	 */
	public static function action_links( array $links ): array {
		array_unshift(
			$links,
			'<a href="' . esc_url( Plugin::admin_url() ) . '">' . esc_html__( 'Forms', 'mpro-forms' ) . '</a>',
			'<a href="' . esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-settings' ) ) . '">' . esc_html__( 'Settings', 'mpro-forms' ) . '</a>'
		);

		return $links;
	}

	/**
	 * Guard used at the top of every page callback.
	 */
	public static function guard(): void {
		if ( ! Plugin::current_user_can() ) {
			wp_die( esc_html__( 'You do not have permission to manage forms.', 'mpro-forms' ), 403 );
		}
	}

	/**
	 * Print a dismissible notice from a query argument.
	 */
	public static function render_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$message = isset( $_GET['mpro_notice'] ) ? sanitize_key( wp_unslash( $_GET['mpro_notice'] ) ) : '';

		if ( '' === $message ) {
			return;
		}

		$notices = array(
			'created'     => array( 'success', __( 'Form created.', 'mpro-forms' ) ),
			'updated'     => array( 'success', __( 'Form saved.', 'mpro-forms' ) ),
			'duplicated'  => array( 'success', __( 'Form duplicated.', 'mpro-forms' ) ),
			'deleted'     => array( 'success', __( 'Form deleted.', 'mpro-forms' ) ),
			'settings'    => array( 'success', __( 'Settings saved.', 'mpro-forms' ) ),
			'entry-note'  => array( 'success', __( 'Note saved.', 'mpro-forms' ) ),
			'entries'     => array( 'success', __( 'Entries updated.', 'mpro-forms' ) ),
			'error'       => array( 'error', __( 'The request could not be completed.', 'mpro-forms' ) ),
		);

		if ( ! isset( $notices[ $message ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $notices[ $message ][0] ),
			esc_html( $notices[ $message ][1] )
		);
	}
}
