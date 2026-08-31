<?php
/**
 * WordPress dashboard summary widget.
 *
 * @package FreeMPROForms
 */

namespace FreeMPROForms;

defined( 'ABSPATH' ) || exit;

final class Dashboard_Widget {
	public static function init(): void {
		add_action( 'wp_dashboard_setup', array( self::class, 'register' ) );
	}

	public static function register(): void {
		if ( ! Settings::get( 'dashboard_widget', true ) || ! Plugin::current_user_can() ) {
			return;
		}

		wp_add_dashboard_widget(
			'fmpf_dashboard',
			Plugin::menu_label(),
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
		?>
		<div class="fmpf-dashboard">
			<ul class="fmpf-dashboard__stats">
				<li>
					<strong><?php echo esc_html( number_format_i18n( $total_forms ) ); ?></strong>
					<span><?php esc_html_e( 'Forms', 'free-mpro-forms' ); ?></span>
				</li>
				<li>
					<strong><?php echo esc_html( number_format_i18n( $active_forms ) ); ?></strong>
					<span><?php esc_html_e( 'Active', 'free-mpro-forms' ); ?></span>
				</li>
				<li>
					<strong><?php echo esc_html( number_format_i18n( $total_entries ) ); ?></strong>
					<span><?php esc_html_e( 'Entries', 'free-mpro-forms' ); ?></span>
				</li>
				<li>
					<strong><?php echo esc_html( number_format_i18n( $unread ) ); ?></strong>
					<span><?php esc_html_e( 'Unread', 'free-mpro-forms' ); ?></span>
				</li>
			</ul>

			<?php if ( $recent ) : ?>
				<table class="fmpf-dashboard__table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Form', 'free-mpro-forms' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Entries', 'free-mpro-forms' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Conversion', 'free-mpro-forms' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent as $form ) : ?>
							<tr>
								<td>
									<a href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-inbox', array( 'form' => (int) $form['id'] ) ) ); ?>">
										<?php echo esc_html( (string) $form['title'] ); ?>
									</a>
								</td>
								<td><?php echo esc_html( number_format_i18n( (int) $form['entries_count'] ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( Form_Repository::conversion_rate( $form ), 1 ) . '%' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php esc_html_e( 'No forms yet.', 'free-mpro-forms' ); ?></p>
			<?php endif; ?>

			<p class="fmpf-dashboard__actions">
				<a class="button button-primary" href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-new' ) ); ?>">
					<?php esc_html_e( 'New form', 'free-mpro-forms' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( Plugin::admin_url( Plugin::MENU_SLUG . '-inbox' ) ); ?>">
					<?php esc_html_e( 'Open inbox', 'free-mpro-forms' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
