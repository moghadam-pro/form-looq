<?php
/**
 * Inbox screen: pick a form, then browse its entries.
 *
 * @package FormLooq
 */

namespace FormLooq\Admin;

use FormLooq\Entry_Repository;
use FormLooq\Form_Repository;
use FormLooq\Plugin;

defined( 'ABSPATH' ) || exit;

final class Page_Inbox {
	public static function init(): void {
		add_action( 'admin_init', array( self::class, 'handle_actions' ) );
	}

	public static function handle_actions(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : '';

		if ( Plugin::MENU_SLUG . '-inbox' !== $page || ! Plugin::current_user_can() ) {
			return;
		}

		self::handle_single_action();
		self::handle_bulk_action();
	}

	private static function handle_single_action(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action   = isset( $_GET['looq_action'] ) ? sanitize_key( wp_unslash( $_GET['looq_action'] ) ) : '';
		$entry_id = isset( $_GET['entry'] ) ? absint( $_GET['entry'] ) : 0;
		$form_id  = isset( $_GET['form'] ) ? absint( $_GET['form'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $action, array( 'delete', 'mark_read', 'mark_unread' ), true ) || $entry_id <= 0 ) {
			return;
		}

		check_admin_referer( 'looq_entry_' . $action . '_' . $entry_id );

		if ( 'delete' === $action ) {
			Entry_Repository::delete( $entry_id );
		} else {
			Entry_Repository::set_status(
				$entry_id,
				'mark_read' === $action ? Entry_Repository::STATUS_READ : Entry_Repository::STATUS_UNREAD
			);
		}

		self::redirect( $form_id, 'entries' );
	}

	private static function handle_bulk_action(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action  = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
		$action2 = isset( $_REQUEST['action2'] ) ? sanitize_key( wp_unslash( $_REQUEST['action2'] ) ) : '';
		$form_id = isset( $_REQUEST['form'] ) ? absint( $_REQUEST['form'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$action = '-1' !== $action && '' !== $action ? $action : $action2;

		if ( ! in_array( $action, array( 'delete', 'mark_read', 'mark_unread' ), true ) ) {
			return;
		}

		check_admin_referer( 'bulk-looq_entries' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ids = isset( $_REQUEST['entry_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_REQUEST['entry_ids'] ) ) : array();

		foreach ( array_filter( $ids ) as $entry_id ) {
			if ( 'delete' === $action ) {
				Entry_Repository::delete( $entry_id );
				continue;
			}

			Entry_Repository::set_status(
				$entry_id,
				'mark_read' === $action ? Entry_Repository::STATUS_READ : Entry_Repository::STATUS_UNREAD
			);
		}

		self::redirect( $form_id, $ids ? 'entries' : 'error' );
	}

	private static function redirect( int $form_id, string $notice ): void {
		wp_safe_redirect(
			Plugin::admin_url(
				Plugin::MENU_SLUG . '-inbox',
				array(
					'form'        => $form_id,
					'looq_notice' => $notice,
				)
			)
		);
		exit;
	}

	public static function action_url( string $action, int $entry_id, int $form_id ): string {
		return wp_nonce_url(
			Plugin::admin_url(
				Plugin::MENU_SLUG . '-inbox',
				array(
					'looq_action' => $action,
					'entry'       => $entry_id,
					'form'        => $form_id,
				)
			),
			'looq_entry_' . $action . '_' . $entry_id
		);
	}

	public static function render(): void {
		Admin::guard();

		$forms = Form_Repository::query( array( 'per_page' => 200 ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form_id = isset( $_GET['form'] ) ? absint( $_GET['form'] ) : 0;

		if ( 0 === $form_id && $forms ) {
			$form_id = (int) $forms[0]['id'];
		}
		?>
		<div class="wrap looq-wrap">
			<?php
			Admin::header(
				__( 'Inbox', 'form-looq' ),
				__( 'Entries submitted through your forms, stored only on this site.', 'form-looq' )
			);
			Admin::render_notice();
			?>

			<?php if ( ! $forms ) : ?>
				<div class="notice notice-info inline">
					<p>
						<?php esc_html_e( 'There are no forms yet, so there is nothing to show here.', 'form-looq' ); ?>
						<a href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-new' ) ); ?>"><?php esc_html_e( 'Create your first form.', 'form-looq' ); ?></a>
					</p>
				</div>
				<?php return; ?>
			<?php endif; ?>

			<form method="get" class="looq-inbox">
				<input type="hidden" name="page" value="<?php echo esc_attr( Plugin::MENU_SLUG . '-inbox' ); ?>">

				<div class="looq-inbox__bar">
					<label for="looq-inbox-form"><strong><?php esc_html_e( 'Form', 'form-looq' ); ?></strong></label>
					<select id="looq-inbox-form" name="form" onchange="this.form.submit()">
						<?php foreach ( $forms as $form ) : ?>
							<option value="<?php echo esc_attr( (string) $form['id'] ); ?>" <?php selected( $form_id, (int) $form['id'] ); ?>>
								<?php
								printf(
									/* translators: 1: form title, 2: entry count. */
									esc_html__( '%1$s (%2$s entries)', 'form-looq' ),
									esc_html( (string) $form['title'] ),
									esc_html( number_format_i18n( (int) $form['entries_count'] ) )
								);
								?>
							</option>
						<?php endforeach; ?>
					</select>
					<noscript><button type="submit" class="button"><?php esc_html_e( 'Show', 'form-looq' ); ?></button></noscript>

					<a class="button looq-inbox__export" href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-tools', array( 'form' => $form_id ) ) ); ?>">
						<?php esc_html_e( 'Export this form', 'form-looq' ); ?>
					</a>
				</div>

				<?php
				$table = new Entries_List_Table( $form_id );
				$table->prepare_items();
				$table->search_box( __( 'Search entries', 'form-looq' ), 'looq-entry-search' );
				$table->display();
				?>
			</form>
		</div>
		<?php
	}
}
