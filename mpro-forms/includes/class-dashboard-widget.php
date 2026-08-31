<?php
/**
 * WordPress dashboard summary widget.
 *
 * @package MPROForms
 */

namespace MPROForms;

defined( 'ABSPATH' ) || exit;

final class Dashboard_Widget {
	public static function init(): void {
		add_action( 'wp_dashboard_setup', array( self::class, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	/**
	 * Admin::enqueue() only loads the plugin stylesheet on the plugin's own
	 * screens. The dashboard widget renders on index.php instead, so without
	 * this it falls back to unstyled browser defaults for every list, table,
	 * and heading - structurally correct markup that simply looks like a mess.
	 */
	public static function enqueue( string $hook ): void {
		if ( 'index.php' !== $hook || ! Settings::get( 'dashboard_widget', true ) ) {
			return;
		}

		wp_enqueue_style(
			'mpro-admin',
			plugins_url( 'assets/css/admin.css', MPRO_FORMS_FILE ),
			array(),
			MPRO_FORMS_VERSION
		);
	}

	public static function register(): void {
		if ( ! Settings::get( 'dashboard_widget', true ) || ! Plugin::current_user_can() ) {
			return;
		}

		wp_add_dashboard_widget(
			'mpro_dashboard',
			Plugin::name(),
			array( self::class, 'render' )
		);
	}

	public static function render(): void {
		$total_forms   = Form_Repository::count();
		$active_forms  = Form_Repository::count( array( 'status' => Form_Repository::STATUS_ACTIVE ) );
		$total_entries = Entry_Repository::total();
		$unread        = Entry_Repository::count( array( 'status' => Entry_Repository::STATUS_UNREAD ) );
		$recent        = Form_Repository::query(
			array(
				'per_page' => 5,
				'orderby'  => 'entries_count',
				'order'    => 'DESC',
			)
		);
		$stats = array(
			array(
				'value'    => $total_forms,
				'label'    => __( 'Forms', 'mpro-forms' ),
				'modifier' => '',
			),
			array(
				'value'    => $active_forms,
				'label'    => __( 'Active', 'mpro-forms' ),
				'modifier' => '',
			),
			array(
				'value'    => $total_entries,
				'label'    => __( 'Entries', 'mpro-forms' ),
				'modifier' => '',
			),
			array(
				'value'    => $unread,
				'label'    => __( 'Unread', 'mpro-forms' ),
				// Only the actionable stat gets the brand accent, so it reads
				// as a call to action rather than one number among four.
				'modifier' => $unread > 0 ? ' mpro-dashboard__stat--unread' : '',
			),
		);
		?>
		<div class="mpro-dashboard">
			<div class="mpro-dashboard__stats">
				<?php foreach ( $stats as $stat ) : ?>
					<div class="mpro-dashboard__stat<?php echo esc_attr( $stat['modifier'] ); ?>">
						<span class="mpro-dashboard__stat-value"><?php echo esc_html( number_format_i18n( (int) $stat['value'] ) ); ?></span>
						<span class="mpro-dashboard__stat-label"><?php echo esc_html( $stat['label'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="mpro-dashboard__section">
				<h3 class="mpro-dashboard__heading"><?php esc_html_e( 'Top forms by entries', 'mpro-forms' ); ?></h3>

				<?php if ( $recent ) : ?>
					<table class="mpro-dashboard__table">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Form', 'mpro-forms' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Entries', 'mpro-forms' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Conversion', 'mpro-forms' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent as $form ) : ?>
								<?php $rate = Form_Repository::conversion_rate( $form ); ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-inbox', array( 'form' => (int) $form['id'] ) ) ); ?>">
											<?php echo esc_html( (string) $form['title'] ); ?>
										</a>
									</td>
									<td class="mpro-dashboard__num"><?php echo esc_html( number_format_i18n( (int) $form['entries_count'] ) ); ?></td>
									<td>
										<span class="mpro-dashboard__rate">
											<span class="mpro-dashboard__rate-bar"><span style="width:<?php echo esc_attr( (string) min( 100, $rate ) ); ?>%"></span></span>
											<span class="mpro-dashboard__num"><?php echo esc_html( number_format_i18n( $rate, 1 ) . '%' ); ?></span>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p class="mpro-dashboard__empty"><?php esc_html_e( 'No forms yet.', 'mpro-forms' ); ?></p>
				<?php endif; ?>
			</div>

			<p class="mpro-dashboard__actions">
				<a class="button button-primary" href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-new' ) ); ?>">
					<?php esc_html_e( 'New form', 'mpro-forms' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-inbox' ) ); ?>">
					<?php esc_html_e( 'Open inbox', 'mpro-forms' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
