<?php
/**
 * Forms list built on the core WP_List_Table so it inherits admin styling.
 *
 * @package FormLooq
 */

namespace FormLooq\Admin;

use FormLooq\Form_Repository;
use FormLooq\Plugin;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class Forms_List_Table extends \WP_List_Table {
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'looq_form',
				'plural'   => 'looq_forms',
				'ajax'     => false,
			)
		);
	}

	/**
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return array(
			'cb'            => '<input type="checkbox" />',
			'title'         => __( 'Form title', 'form-looq' ),
			'status'        => __( 'Status', 'form-looq' ),
			'id'            => __( 'ID', 'form-looq' ),
			'entries_count' => __( 'Entries', 'form-looq' ),
			'views'         => __( 'Views', 'form-looq' ),
			'conversion'    => __( 'Conversion', 'form-looq' ),
		);
	}

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	protected function get_sortable_columns(): array {
		return array(
			'title'         => array( 'title', false ),
			'status'        => array( 'status', false ),
			'id'            => array( 'id', true ),
			'entries_count' => array( 'entries_count', false ),
			'views'         => array( 'views', false ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	protected function get_bulk_actions(): array {
		return array(
			'delete' => __( 'Delete', 'form-looq' ),
		);
	}

	public function no_items(): void {
		esc_html_e( 'No forms yet. Create your first form to get started.', 'form-looq' );
	}

	public function prepare_items(): void {
		$per_page = 20;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$paged   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$status  = isset( $_GET['form_status'] ) ? sanitize_key( wp_unslash( $_GET['form_status'] ) ) : '';
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'id';
		$order   = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'desc';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$args = array(
			'search'   => $search,
			'status'   => $status,
			'orderby'  => $orderby,
			'order'    => $order,
			'per_page' => $per_page,
			'page'     => $paged,
		);

		$this->items = Form_Repository::query( $args );
		$total       = Form_Repository::count( $args );

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total / $per_page ),
			)
		);

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'title' );
	}

	/**
	 * @param array<string, mixed> $item Form row.
	 */
	protected function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="form_ids[]" value="%d" />', (int) $item['id'] );
	}

	/**
	 * @param array<string, mixed> $item Form row.
	 */
	protected function column_title( array $item ): string {
		$edit_url = Plugin::admin_url( Plugin::MENU_SLUG . '-builder', array( 'form' => (int) $item['id'] ) );

		$title = sprintf(
			'<strong><a class="row-title" href="%1$s">%2$s</a></strong>',
			esc_url( $edit_url ),
			esc_html( $item['title'] )
		);

		if ( '' !== (string) $item['description'] ) {
			$title .= '<div class="looq-row-description">' . esc_html( wp_trim_words( (string) $item['description'], 18 ) ) . '</div>';
		}

		$actions = array(
			'edit'      => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit', 'form-looq' ) ),
			'entries'   => sprintf(
				'<a href="%s">%s</a>',
				esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-inbox', array( 'form' => (int) $item['id'] ) ) ),
				esc_html__( 'Entries', 'form-looq' )
			),
			'settings'  => sprintf(
				'<a href="%s">%s</a>',
				esc_url( add_query_arg( 'tab', 'settings', $edit_url ) ),
				esc_html__( 'Settings', 'form-looq' )
			),
			'duplicate' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( self::action_url( 'duplicate', (int) $item['id'] ) ),
				esc_html__( 'Duplicate', 'form-looq' )
			),
			'delete'    => sprintf(
				'<a class="submitdelete looq-confirm" data-looq-confirm="%s" href="%s">%s</a>',
				esc_attr__( 'Delete this form and all of its entries? This cannot be undone.', 'form-looq' ),
				esc_url( self::action_url( 'delete', (int) $item['id'] ) ),
				esc_html__( 'Delete', 'form-looq' )
			),
		);

		return $title . $this->row_actions( $actions );
	}

	/**
	 * @param array<string, mixed> $item Form row.
	 */
	protected function column_status( array $item ): string {
		$statuses = Form_Repository::statuses();
		$label    = $statuses[ $item['status'] ] ?? $item['status'];

		return sprintf(
			'<span class="looq-status looq-status--%1$s">%2$s</span>',
			esc_attr( (string) $item['status'] ),
			esc_html( (string) $label )
		);
	}

	/**
	 * @param array<string, mixed> $item Form row.
	 */
	protected function column_id( array $item ): string {
		return '<code>' . (int) $item['id'] . '</code>';
	}

	/**
	 * @param array<string, mixed> $item Form row.
	 */
	protected function column_entries_count( array $item ): string {
		$count = (int) $item['entries_count'];

		if ( 0 === $count ) {
			return '<span aria-hidden="true">—</span><span class="screen-reader-text">' . esc_html__( 'No entries', 'form-looq' ) . '</span>';
		}

		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-inbox', array( 'form' => (int) $item['id'] ) ) ),
			esc_html( number_format_i18n( $count ) )
		);
	}

	/**
	 * @param array<string, mixed> $item Form row.
	 */
	protected function column_views( array $item ): string {
		return esc_html( number_format_i18n( (int) $item['views'] ) );
	}

	/**
	 * @param array<string, mixed> $item Form row.
	 */
	protected function column_conversion( array $item ): string {
		$rate = Form_Repository::conversion_rate( $item );

		return esc_html( number_format_i18n( $rate, 1 ) . '%' );
	}

	/**
	 * @param array<string, mixed> $item        Form row.
	 * @param string               $column_name Column key.
	 */
	protected function column_default( $item, $column_name ): string {
		return isset( $item[ $column_name ] ) && is_scalar( $item[ $column_name ] )
			? esc_html( (string) $item[ $column_name ] )
			: '';
	}

	protected function extra_tablenav( $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current = isset( $_GET['form_status'] ) ? sanitize_key( wp_unslash( $_GET['form_status'] ) ) : '';
		?>
		<div class="alignleft actions">
			<label class="screen-reader-text" for="looq-filter-status"><?php esc_html_e( 'Filter by status', 'form-looq' ); ?></label>
			<select name="form_status" id="looq-filter-status">
				<option value=""><?php esc_html_e( 'All statuses', 'form-looq' ); ?></option>
				<?php foreach ( Form_Repository::statuses() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php submit_button( __( 'Filter', 'form-looq' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}

	/**
	 * Build a nonce-protected single-row action URL.
	 */
	public static function action_url( string $action, int $form_id ): string {
		return wp_nonce_url(
			Plugin::admin_url(
				Plugin::MENU_SLUG,
				array(
					'looq_action' => $action,
					'form'        => $form_id,
				)
			),
			'looq_form_' . $action . '_' . $form_id
		);
	}
}
