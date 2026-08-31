<?php
/**
 * Forms list screen and its row/bulk actions.
 *
 * @package FreeMPROForms
 */

namespace FreeMPROForms\Admin;

use FreeMPROForms\Form_Repository;
use FreeMPROForms\Plugin;

defined( 'ABSPATH' ) || exit;

final class Page_Forms {
	public static function init(): void {
		add_action( 'admin_init', array( self::class, 'handle_actions' ) );
	}

	/**
	 * Process row and bulk actions before the list table renders.
	 */
	public static function handle_actions(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$page = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( Plugin::MENU_SLUG !== $page || ! Plugin::current_user_can() ) {
			return;
		}

		self::handle_single_action();
		self::handle_bulk_action();
	}

	private static function handle_single_action(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['fmpf_action'] ) ? sanitize_key( wp_unslash( $_GET['fmpf_action'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form_id = isset( $_GET['form'] ) ? absint( $_GET['form'] ) : 0;

		if ( ! in_array( $action, array( 'duplicate', 'delete' ), true ) || $form_id <= 0 ) {
			return;
		}

		check_admin_referer( 'fmpf_form_' . $action . '_' . $form_id );

		if ( 'duplicate' === $action ) {
			$new_id = Form_Repository::duplicate( $form_id );
			self::redirect( $new_id > 0 ? 'duplicated' : 'error' );
		}

		self::redirect( Form_Repository::delete( $form_id ) ? 'deleted' : 'error' );
	}

	private static function handle_bulk_action(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action  = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
		$action2 = isset( $_REQUEST['action2'] ) ? sanitize_key( wp_unslash( $_REQUEST['action2'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$action = '-1' !== $action && '' !== $action ? $action : $action2;

		if ( 'delete' !== $action ) {
			return;
		}

		check_admin_referer( 'bulk-fmpf_forms' );

		$ids = isset( $_REQUEST['form_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_REQUEST['form_ids'] ) ) : array();

		foreach ( array_filter( $ids ) as $form_id ) {
			Form_Repository::delete( $form_id );
		}

		self::redirect( $ids ? 'deleted' : 'error' );
	}

	private static function redirect( string $notice ): void {
		wp_safe_redirect( Plugin::admin_url( Plugin::MENU_SLUG, array( 'fmpf_notice' => $notice ) ) );
		exit;
	}

	public static function render(): void {
		Admin::guard();

		$table = new Forms_List_Table();
		$table->prepare_items();

		$new_url = Plugin::admin_url( Plugin::MENU_SLUG . '-new' );
		?>
		<div class="wrap fmpf-wrap">
			<?php Admin::header( __( 'Forms list', 'free-mpro-forms' ), __( 'Every form on this site, with its entries, views, and conversion rate.', 'free-mpro-forms' ) ); ?>

			<h2 class="screen-reader-text"><?php esc_html_e( 'Forms list', 'free-mpro-forms' ); ?></h2>

			<p class="fmpf-page-actions">
				<a class="button button-primary" href="<?php echo esc_url( $new_url ); ?>">
					<?php esc_html_e( 'Add new form', 'free-mpro-forms' ); ?>
				</a>
			</p>

			<?php Admin::render_notice(); ?>

			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( Plugin::MENU_SLUG ); ?>">
				<?php
				$table->search_box( __( 'Search forms', 'free-mpro-forms' ), 'fmpf-form-search' );
				$table->display();
				?>
			</form>
		</div>
		<?php
	}
}
