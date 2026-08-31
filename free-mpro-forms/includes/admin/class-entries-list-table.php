<?php
/**
 * Entries list for the inbox screen.
 *
 * @package FreeMPROForms
 */

namespace FreeMPROForms\Admin;

use FreeMPROForms\Entry_Repository;
use FreeMPROForms\Form_Repository;
use FreeMPROForms\Plugin;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class Entries_List_Table extends \WP_List_Table {
	/**
	 * The first three fields of the selected form become dynamic columns.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $preview_fields = array();

	private int $form_id = 0;

	public function __construct( int $form_id ) {
		$this->form_id = $form_id;

		$form = Form_Repository::get( $form_id );

		if ( $form ) {
			foreach ( (array) $form['fields'] as $field ) {
				if ( 'section' === $field['type'] ) {
					continue;
				}

				$this->preview_fields[] = $field;

				if ( count( $this->preview_fields ) >= 3 ) {
					break;
				}
			}
		}

		parent::__construct(
			array(
				'singular' => 'fmpf_entry',
				'plural'   => 'fmpf_entries',
				'ajax'     => false,
			)
		);
	}

	/**
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'id' => __( 'ID', 'free-mpro-forms' ),
		);

		foreach ( $this->preview_fields as $field ) {
			$columns[ 'field_' . $field['name'] ] = $field['label'];
		}

		$columns['status']     = __( 'Status', 'free-mpro-forms' );
		$columns['created_at'] = __( 'Submitted', 'free-mpro-forms' );

		return $columns;
	}

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	protected function get_sortable_columns(): array {
		return array(
			'id'         => array( 'id', true ),
			'status'     => array( 'status', false ),
			'created_at' => array( 'created_at', true ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	protected function get_bulk_actions(): array {
		return array(
			'mark_read'   => __( 'Mark as read', 'free-mpro-forms' ),
			'mark_unread' => __( 'Mark as unread', 'free-mpro-forms' ),
			'delete'      => __( 'Delete', 'free-mpro-forms' ),
		);
	}

	public function no_items(): void {
		esc_html_e( 'No entries found.', 'free-mpro-forms' );
	}

	public function prepare_items(): void {
		$per_page = 20;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$paged   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$status  = isset( $_GET['entry_status'] ) ? sanitize_key( wp_unslash( $_GET['entry_status'] ) ) : '';
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'id';
		$order   = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'desc';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$args = array(
			'form_id'  => $this->form_id,
			'status'   => $status,
			'search'   => $search,
			'orderby'  => $orderby,
			'order'    => $order,
			'per_page' => $per_page,
			'page'     => $paged,
		);

		$this->items = Entry_Repository::query( $args );
		$total       = Entry_Repository::count( $args );

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total / $per_page ),
			)
		);

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'id' );
	}

	/**
	 * @param array<string, mixed> $item Entry row.
	 */
	protected function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="entry_ids[]" value="%d" />', (int) $item['id'] );
	}

	/**
	 * @param array<string, mixed> $item Entry row.
	 */
	protected function column_id( array $item ): string {
		$view_url = Plugin::admin_url( Plugin::MENU_SLUG . '-entry', array( 'entry' => (int) $item['id'] ) );

		$title = sprintf(
			'<strong><a class="row-title" href="%1$s">#%2$d</a></strong>',
			esc_url( $view_url ),
			(int) $item['id']
		);

		$actions = array(
			'view'   => sprintf( '<a href="%s">%s</a>', esc_url( $view_url ), esc_html__( 'View', 'free-mpro-forms' ) ),
			'delete' => sprintf(
				'<a class="submitdelete fmpf-confirm" data-fmpf-confirm="%s" href="%s">%s</a>',
				esc_attr__( 'Delete this entry permanently?', 'free-mpro-forms' ),
				esc_url( Page_Inbox::action_url( 'delete', (int) $item['id'], $this->form_id ) ),
				esc_html__( 'Delete', 'free-mpro-forms' )
			),
		);

		return $title . $this->row_actions( $actions );
	}

	/**
	 * @param array<string, mixed> $item Entry row.
	 */
	protected function column_status( array $item ): string {
		$statuses = Entry_Repository::statuses();

		return sprintf(
			'<span class="fmpf-status fmpf-status--%1$s">%2$s</span>',
			esc_attr( (string) $item['status'] ),
			esc_html( (string) ( $statuses[ $item['status'] ] ?? $item['status'] ) )
		);
	}

	/**
	 * @param array<string, mixed> $item Entry row.
	 */
	protected function column_created_at( array $item ): string {
		$timestamp = strtotime( (string) $item['created_at'] );

		if ( ! $timestamp ) {
			return '';
		}

		return esc_html(
			sprintf(
				/* translators: 1: date, 2: time. */
				__( '%1$s at %2$s', 'free-mpro-forms' ),
				wp_date( (string) get_option( 'date_format' ), $timestamp ),
				wp_date( (string) get_option( 'time_format' ), $timestamp )
			)
		);
	}

	/**
	 * @param array<string, mixed> $item        Entry row.
	 * @param string               $column_name Column key.
	 */
	protected function column_default( $item, $column_name ): string {
		if ( 0 !== strpos( $column_name, 'field_' ) ) {
			return '';
		}

		$key   = substr( $column_name, 6 );
		$value = $item['data'][ $key ] ?? '';

		if ( is_array( $value ) ) {
			$value = implode( ', ', $value );
		}

		$value = wp_trim_words( (string) $value, 12 );

		return '' !== $value ? esc_html( $value ) : '<span aria-hidden="true">—</span>';
	}

	protected function extra_tablenav( $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current = isset( $_GET['entry_status'] ) ? sanitize_key( wp_unslash( $_GET['entry_status'] ) ) : '';
		?>
		<div class="alignleft actions">
			<label class="screen-reader-text" for="fmpf-entry-status"><?php esc_html_e( 'Filter by status', 'free-mpro-forms' ); ?></label>
			<select name="entry_status" id="fmpf-entry-status">
				<option value=""><?php esc_html_e( 'All entries', 'free-mpro-forms' ); ?></option>
				<?php foreach ( Entry_Repository::statuses() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php submit_button( __( 'Filter', 'free-mpro-forms' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed> $item Entry row.
	 */
	public function single_row( $item ): void {
		$classes = Entry_Repository::STATUS_UNREAD === $item['status'] ? 'fmpf-entry-unread' : '';

		echo '<tr class="' . esc_attr( $classes ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
}
