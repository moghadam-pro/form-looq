<?php
/** Header. @package Formlooq_Theme */
$content = formlooq_content();
$route   = formlooq_route();
$other   = formlooq_is_fa() ? 'en' : 'fa';
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#main"><?php echo esc_html( formlooq_is_fa() ? 'رفتن به محتوا' : 'Skip to content' ); ?></a>
<header class="site-header">
	<div class="looq-container header-inner">
		<a class="brand" href="<?php echo esc_url( formlooq_url() ); ?>" aria-label="Form LOOQ">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo-mark.svg' ); ?>" alt="" width="64" height="64">
			<span class="brand-logotype"><?php echo esc_html( formlooq_is_fa() ? 'فرم‌لوک' : 'Form LOOQ' ); ?></span>
		</a>
		<nav class="site-nav" aria-label="<?php echo esc_attr( formlooq_is_fa() ? 'منوی اصلی' : 'Primary navigation' ); ?>">
			<?php foreach ( array( 'features', 'demos', 'docs', 'addons' ) as $item ) : ?>
				<a href="<?php echo esc_url( formlooq_url( $item ) ); ?>" <?php echo $route === $item ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $content['nav'][ $item ] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<div class="header-actions">
			<a class="lang-link" href="<?php echo esc_url( formlooq_url( $route, $other ) ); ?>" hreflang="<?php echo esc_attr( $other ); ?>"><?php echo esc_html( strtoupper( $other ) ); ?></a>
			<a class="button button-primary" href="<?php echo esc_url( formlooq_download_url() ); ?>"><?php echo esc_html( $content['nav']['download'] ); ?></a>
			<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-navigation"><span></span><span class="screen-reader-text"><?php echo esc_html( formlooq_is_fa() ? 'باز کردن منو' : 'Open menu' ); ?></span></button>
		</div>
	</div>
</header>
