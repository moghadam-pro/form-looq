<?php
/**
 * Single entry screen with the admin note field.
 *
 * @package FormLooq
 */

namespace FormLooq\Admin;

use FormLooq\Entry_Repository;
use FormLooq\Form_Repository;
use FormLooq\Plugin;

defined( 'ABSPATH' ) || exit;

final class Page_Entry {
	private const ACTION = 'looq_save_entry_note';

	public static function init(): void {
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'handle_note' ) );
	}

	public static function handle_note(): void {
		Admin::guard();
		check_admin_referer( self::ACTION );

		$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
		$note     = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

		Entry_Repository::set_note( $entry_id, $note );

		wp_safe_redirect(
			Plugin::admin_url(
				Plugin::MENU_SLUG . '-entry',
				array(
					'entry'       => $entry_id,
					'looq_notice' => 'entry-note',
				)
			)
		);
		exit;
	}

	public static function render(): void {
		Admin::guard();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$entry_id = isset( $_GET['entry'] ) ? absint( $_GET['entry'] ) : 0;
		$entry    = Entry_Repository::get( $entry_id );

		if ( ! $entry ) {
			echo '<div class="wrap looq-wrap"><div class="notice notice-error"><p>'
				. esc_html__( 'That entry no longer exists.', 'form-looq' ) . '</p></div></div>';
			return;
		}

		// Opening an entry marks it read, which is what "unread" is meant to track.
		if ( Entry_Repository::STATUS_UNREAD === $entry['status'] ) {
			Entry_Repository::set_status( $entry_id, Entry_Repository::STATUS_READ );
			$entry['status'] = Entry_Repository::STATUS_READ;
		}

		$form   = Form_Repository::get( (int) $entry['form_id'] );
		$labels = array();

		foreach ( (array) ( $form['fields'] ?? array() ) as $field ) {
			if ( ! empty( $field['name'] ) ) {
				$labels[ $field['name'] ] = (string) $field['label'];
			}
		}
		?>
		<div class="wrap looq-wrap">
			<?php
			Admin::header(
				/* translators: %d: entry ID. */
				sprintf( __( 'Entry #%d', 'form-looq' ), $entry_id ),
				(string) ( $form['title'] ?? __( 'Deleted form', 'form-looq' ) )
			);
			Admin::render_notice();
			?>

			<p class="looq-page-actions">
				<a class="button" href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-inbox', array( 'form' => (int) $entry['form_id'] ) ) ); ?>">
					<?php esc_html_e( 'Back to inbox', 'form-looq' ); ?>
				</a>
				<a class="button looq-confirm" data-looq-confirm="<?php esc_attr_e( 'Delete this entry permanently?', 'form-looq' ); ?>" href="<?php echo esc_url( Page_Inbox::action_url( 'delete', $entry_id, (int) $entry['form_id'] ) ); ?>">
					<?php esc_html_e( 'Delete entry', 'form-looq' ); ?>
				</a>
			</p>

			<div class="looq-entry">
				<div class="looq-entry__main">
					<h2><?php esc_html_e( 'Submitted values', 'form-looq' ); ?></h2>
					<table class="widefat striped looq-entry__table">
						<tbody>
							<?php if ( ! $entry['data'] ) : ?>
								<tr>
									<td><?php esc_html_e( 'This entry has no stored values.', 'form-looq' ); ?></td>
								</tr>
							<?php endif; ?>
							<?php foreach ( (array) $entry['data'] as $key => $value ) : ?>
								<tr>
									<th scope="row" style="width:240px">
										<?php echo esc_html( $labels[ $key ] ?? ucwords( str_replace( array( '-', '_' ), ' ', (string) $key ) ) ); ?>
									</th>
									<td style="white-space:pre-wrap">
										<?php echo esc_html( is_array( $value ) ? implode( ', ', $value ) : (string) $value ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<h2><?php esc_html_e( 'Admin note', 'form-looq' ); ?></h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="looq-entry__note">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
						<input type="hidden" name="entry_id" value="<?php echo esc_attr( (string) $entry_id ); ?>">
						<?php wp_nonce_field( self::ACTION ); ?>

						<label class="screen-reader-text" for="looq-entry-note"><?php esc_html_e( 'Admin note', 'form-looq' ); ?></label>
						<textarea id="looq-entry-note" name="note" rows="4" class="large-text" maxlength="2000" placeholder="<?php esc_attr_e( 'Private note about this entry — visible only to your team.', 'form-looq' ); ?>"><?php echo esc_textarea( (string) $entry['note'] ); ?></textarea>
						<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save note', 'form-looq' ); ?></button></p>
					</form>
				</div>

				<div class="looq-entry__side">
					<h2><?php esc_html_e( 'Details', 'form-looq' ); ?></h2>
					<table class="widefat striped">
						<tbody>
							<?php
							$timestamp = strtotime( (string) $entry['created_at'] );
							$meta      = array(
								__( 'Submitted on', 'form-looq' ) => $timestamp
									? wp_date( (string) get_option( 'date_format' ) . ' ' . (string) get_option( 'time_format' ), $timestamp )
									: (string) $entry['created_at'],
								__( 'Status', 'form-looq' )       => Entry_Repository::statuses()[ $entry['status'] ] ?? $entry['status'],
								__( 'Form', 'form-looq' )         => (string) ( $form['title'] ?? '—' ),
								__( 'User', 'form-looq' )         => $entry['user_id'] > 0
									? (string) get_the_author_meta( 'display_name', (int) $entry['user_id'] )
									: __( 'Not signed in', 'form-looq' ),
								__( 'IP hash', 'form-looq' )      => '' !== $entry['ip_hash']
									? substr( (string) $entry['ip_hash'], 0, 16 ) . '…'
									: __( 'Not stored', 'form-looq' ),
								__( 'Referer', 'form-looq' )      => (string) $entry['referer'],
								__( 'User agent', 'form-looq' )   => (string) $entry['user_agent'],
							);

							foreach ( $meta as $label => $value ) :
								?>
								<tr>
									<th scope="row"><?php echo esc_html( (string) $label ); ?></th>
									<td style="word-break:break-all"><?php echo esc_html( '' !== (string) $value ? (string) $value : '—' ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<p class="description">
						<?php esc_html_e( 'IP addresses are never stored in plain text. Only a salted hash is kept, and only when the form enables it.', 'form-looq' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}
}
