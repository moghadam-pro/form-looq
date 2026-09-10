<?php
/** Main router and standard fallback. @package Formlooq_Theme */
$c     = formlooq_content();
$route = formlooq_route();
if ( 'home' === $route ) {
	require get_template_directory() . '/front-page.php';
	return;
}
$page = $c['pages'][ $route ] ?? null;
if ( ! $page ) {
	status_header( 404 );
	$page = array( 'title' => formlooq_is_fa() ? 'صفحه پیدا نشد' : 'Page not found', 'intro' => formlooq_is_fa() ? 'این آدرس وجود ندارد یا جابه‌جا شده است.' : 'This address does not exist or has moved.', 'body' => '' );
}
get_header();
?>
<main id="main"><header class="page-hero"><div class="looq-narrow"><span class="eyebrow">Form LOOQ</span><h1><?php echo esc_html( $page['title'] ); ?></h1><?php if ( $page['intro'] ) : ?><p><?php echo esc_html( $page['intro'] ); ?></p><?php endif; ?></div></header><div class="page-body looq-narrow"><?php echo wp_kses_post( $page['body'] ?? '' ); ?></div></main>
<?php get_footer(); ?>
