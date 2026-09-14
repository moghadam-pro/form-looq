<?php /** Standard WordPress page. @package Formlooq_Theme */ get_header(); ?>
<main id="main"><div class="page-body looq-narrow"><?php while ( have_posts() ) : the_post(); ?><article <?php post_class(); ?>><header class="page-hero"><h1><?php the_title(); ?></h1></header><?php the_content(); ?></article><?php endwhile; ?></div></main>
<?php get_footer(); ?>
