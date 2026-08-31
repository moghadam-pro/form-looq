<?php
/**
 * Shared plugin identity, capability, and asset helpers.
 *
 * @package FreeMPROForms
 */

namespace FreeMPROForms;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	/**
	 * Single capability that gates every plugin screen.
	 */
	public const CAPABILITY = 'fmpf_manage_forms';

	/**
	 * Top-level admin menu slug. Sub-pages append their own suffix.
	 */
	public const MENU_SLUG = 'free-mpro-forms';

	/**
	 * Public site that hosts the add-on catalogue, documentation, and landing page.
	 */
	public const HOME_URL = 'https://sayid.ir/free-forms-plugin';

	public static function version(): string {
		return FREE_MPRO_FORMS_VERSION;
	}

	/**
	 * Capability required to manage forms and entries.
	 */
	public static function capability(): string {
		/**
		 * Filter the capability required to use plugin screens.
		 *
		 * @param string $capability Capability name.
		 */
		return (string) apply_filters( 'free_mpro_forms_capability', self::CAPABILITY );
	}

	public static function current_user_can(): bool {
		return current_user_can( self::capability() ) || current_user_can( 'manage_options' );
	}

	/**
	 * Menu label for the top-level entry.
	 *
	 * Persian admins see the localized label even before the bundled translation
	 * files are loaded, because the menu is the plugin's most visible surface.
	 */
	public static function menu_label(): string {
		$label = __( 'Forms', 'free-mpro-forms' );

		if ( 'Forms' === $label && 0 === strpos( determine_locale(), 'fa' ) ) {
			return 'فرم‌ها';
		}

		return $label;
	}

	/**
	 * Absolute URL of the plugin logo used in the admin header bar.
	 */
	public static function logo_url(): string {
		return plugins_url( 'assets/images/logo.svg', FREE_MPRO_FORMS_FILE );
	}

	/**
	 * Data URI of the monochrome menu icon so WordPress can recolor it.
	 */
	public static function menu_icon(): string {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><g fill="black">'
			. '<rect x="3" y="5" width="57.2" height="7.16" rx="1.43"/>'
			. '<rect x="3" y="16.46" width="45.77" height="7.16" rx="1.43"/>'
			. '<rect x="53.06" y="16.46" width="7.15" height="7.16" rx="1.43"/>'
			. '<rect x="3" y="27.92" width="45.77" height="7.16" rx="1.43"/>'
			. '<rect x="53.06" y="27.92" width="7.15" height="7.16" rx="1.43"/>'
			. '<rect x="3" y="39.38" width="45.77" height="7.16" rx="1.43"/>'
			. '<rect x="53.06" y="39.38" width="7.15" height="7.16" rx="1.43"/>'
			. '<rect x="3" y="50.84" width="17.88" height="7.16" rx="1.43"/>'
			. '</g></svg>';

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Data URI, the documented way to supply a menu icon.
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	/**
	 * Build an admin URL for one of the plugin pages.
	 *
	 * @param array<string, string|int> $args Extra query arguments.
	 */
	public static function admin_url( string $page = self::MENU_SLUG, array $args = array() ): string {
		return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Shortcode snippet users copy to embed a form.
	 */
	public static function embed_code( int $form_id ): string {
		return '[free_mpro_form id="' . $form_id . '"]';
	}

	public static function block_embed_code( int $form_id ): string {
		return '<!-- wp:shortcode -->' . self::embed_code( $form_id ) . '<!-- /wp:shortcode -->';
	}

	public static function php_embed_code( int $form_id ): string {
		return "<?php echo do_shortcode( '" . self::embed_code( $form_id ) . "' ); ?>";
	}
}
