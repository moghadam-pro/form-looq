<?php
/**
 * WordPress dashboard summary widget.
 *
 * @package FormLooq
 */

namespace FormLooq;

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
			'looq-admin',
			plugins_url( 'assets/css/admin.css', FORM_LOOQ_FILE ),
			array(),
			FORM_LOOQ_VERSION
		);
	}

	public static function register(): void {
		if ( ! Settings::get( 'dashboard_widget', true ) || ! Plugin::current_user_can() ) {
			return;
		}

		wp_add_dashboard_widget(
			'looq_dashboard',
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
				'label'    => __( 'Forms', 'form-looq' ),
				'modifier' => '',
			),
			array(
				'value'    => $active_forms,
				'label'    => __( 'Active', 'form-looq' ),
				'modifier' => '',
			),
			array(
				'value'    => $total_entries,
				'label'    => __( 'Entries', 'form-looq' ),
				'modifier' => '',
			),
			array(
				'value'    => $unread,
				'label'    => __( 'Unread', 'form-looq' ),
				// Only the actionable stat gets the brand accent, so it reads
				// as a call to action rather than one number among four.
				'modifier' => $unread > 0 ? ' looq-dashboard__stat--unread' : '',
			),
		);
		?>
		<div class="looq-dashboard">
			<div class="looq-dashboard__stats">
				<?php foreach ( $stats as $stat ) : ?>
					<div class="looq-dashboard__stat<?php echo esc_attr( $stat['modifier'] ); ?>">
						<span class="looq-dashboard__stat-value"><?php echo esc_html( number_format_i18n( (int) $stat['value'] ) ); ?></span>
						<span class="looq-dashboard__stat-label"><?php echo esc_html( $stat['label'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="looq-dashboard__section">
				<h3 class="looq-dashboard__heading"><?php esc_html_e( 'Top forms by entries', 'form-looq' ); ?></h3>

				<?php if ( $recent ) : ?>
					<table class="looq-dashboard__table">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Form', 'form-looq' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Entries', 'form-looq' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Conversion', 'form-looq' ); ?></th>
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
									<td class="looq-dashboard__num"><?php echo esc_html( number_format_i18n( (int) $form['entries_count'] ) ); ?></td>
									<td>
										<span class="looq-dashboard__rate">
											<span class="looq-dashboard__rate-bar"><span style="width:<?php echo esc_attr( (string) min( 100, $rate ) ); ?>%"></span></span>
											<span class="looq-dashboard__num"><?php echo esc_html( number_format_i18n( $rate, 1 ) . '%' ); ?></span>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p class="looq-dashboard__empty"><?php esc_html_e( 'No forms yet.', 'form-looq' ); ?></p>
				<?php endif; ?>
			</div>

			<p class="looq-dashboard__actions">
				<a class="button button-primary" href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-new' ) ); ?>">
					<?php esc_html_e( 'New form', 'form-looq' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-inbox' ) ); ?>">
					<?php esc_html_e( 'Open inbox', 'form-looq' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
