<?php
/**
 * Form LOOQ Theme functions.
 *
 * @package Formlooq_Theme
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FORMLOOQ_THEME_VERSION', '1.0.0' );

add_action(
	'after_setup_theme',
	static function (): void {
		load_theme_textdomain( 'formlooq-theme', get_template_directory() . '/languages' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 300, 'flex-height' => true, 'flex-width' => true ) );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
		register_nav_menus( array( 'primary' => __( 'Primary navigation', 'formlooq-theme' ), 'footer' => __( 'Footer navigation', 'formlooq-theme' ) ) );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style( 'formlooq-theme', get_stylesheet_uri(), array(), FORMLOOQ_THEME_VERSION );
		wp_enqueue_script( 'formlooq-theme', get_template_directory_uri() . '/assets/js/theme.js', array(), FORMLOOQ_THEME_VERSION, true );
	}
);

/** Return the language selected by the canonical URL. */
function formlooq_language(): string {
	$lang = get_query_var( 'formlooq_lang' );
	if ( in_array( $lang, array( 'fa', 'en' ), true ) ) {
		return $lang;
	}
	$path = trim( (string) wp_parse_url( home_url( add_query_arg( array() ) ), PHP_URL_PATH ), '/' );
	return str_starts_with( $path, 'fa' ) ? 'fa' : 'en';
}

function formlooq_is_fa(): bool {
	return 'fa' === formlooq_language();
}

function formlooq_route(): string {
	$route = sanitize_key( (string) get_query_var( 'formlooq_route', 'home' ) );
	return '' === $route ? 'home' : $route;
}

function formlooq_url( string $route = 'home', ?string $lang = null ): string {
	$lang = $lang ?: formlooq_language();
	$path = 'home' === $route ? $lang . '/' : $lang . '/' . trim( $route, '/' ) . '/';
	return home_url( '/' . $path );
}

function formlooq_download_url(): string {
	return (string) apply_filters( 'formlooq_theme_download_url', 'https://github.com/moghadam-pro/form-looq/releases/latest/download/form-looq.zip' );
}

add_filter(
	'query_vars',
	static function ( array $vars ): array {
		$vars[] = 'formlooq_lang';
		$vars[] = 'formlooq_route';
		$vars[] = 'formlooq_feed';
		return $vars;
	}
);

add_action(
	'init',
	static function (): void {
		$routes = 'features|demos|docs|addons|download|changelog|support|privacy|terms|about';
		add_rewrite_rule( '^(fa|en)/?$', 'index.php?formlooq_lang=$matches[1]&formlooq_route=home', 'top' );
		add_rewrite_rule( '^(fa|en)/(' . $routes . ')/?$', 'index.php?formlooq_lang=$matches[1]&formlooq_route=$matches[2]', 'top' );
		add_rewrite_rule( '^(addons|docs)\.json$', 'index.php?formlooq_feed=$matches[1]', 'top' );
		add_rewrite_rule( '^(' . $routes . ')/?$', 'index.php?formlooq_route=$matches[1]', 'top' );
		remove_action( 'wp_head', 'rel_canonical' );
	}
);

add_filter(
	'pre_handle_404',
	static function ( $preempt, $query ) {
		if ( get_query_var( 'formlooq_lang' ) && isset( formlooq_content()['pages'][ formlooq_route() ] ) ) {
			$query->is_404 = false;
			status_header( 200 );
			return true;
		}
		return $preempt;
	},
	10,
	2
);

add_action(
	'after_switch_theme',
	static function (): void {
		flush_rewrite_rules();
	}
);

add_action(
	'template_redirect',
	static function (): void {
		$feed = sanitize_key( (string) get_query_var( 'formlooq_feed' ) );
		if ( in_array( $feed, array( 'addons', 'docs' ), true ) ) {
			nocache_headers();
			wp_send_json( 'addons' === $feed ? formlooq_addons_feed() : formlooq_docs_feed() );
		}
		$route = formlooq_route();
		if ( ! get_query_var( 'formlooq_lang' ) && 'home' !== $route ) {
			wp_safe_redirect( formlooq_url( $route, 'en' ), 301 );
			exit;
		}
		if ( is_front_page() && ! get_query_var( 'formlooq_lang' ) ) {
			wp_safe_redirect( formlooq_url( 'home', 'en' ), 302 );
			exit;
		}
	}
);

add_filter(
	'pre_get_document_title',
	static function ( string $title ): string {
		if ( ! get_query_var( 'formlooq_lang' ) ) {
			return $title;
		}
		$content = formlooq_content();
		$route   = formlooq_route();
		return ( $content['pages'][ $route ]['title'] ?? 'Form LOOQ' ) . ' — Form LOOQ';
	}
);

add_filter(
	'language_attributes',
	static function ( string $output ): string {
		if ( ! get_query_var( 'formlooq_lang' ) ) {
			return $output;
		}
		$lang = formlooq_language();
		return 'lang="' . esc_attr( 'fa' === $lang ? 'fa-IR' : 'en-US' ) . '" dir="' . esc_attr( 'fa' === $lang ? 'rtl' : 'ltr' ) . '"';
	}
);

add_action(
	'wp_head',
	static function (): void {
		if ( ! get_query_var( 'formlooq_lang' ) ) {
			return;
		}
		$route = formlooq_route();
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( formlooq_url( $route ) ) );
		printf( '<link rel="alternate" hreflang="en" href="%s">' . "\n", esc_url( formlooq_url( $route, 'en' ) ) );
		printf( '<link rel="alternate" hreflang="fa" href="%s">' . "\n", esc_url( formlooq_url( $route, 'fa' ) ) );
		printf( '<link rel="alternate" hreflang="x-default" href="%s">' . "\n", esc_url( formlooq_url( $route, 'en' ) ) );
	}
);

require_once get_template_directory() . '/inc/content.php';
require_once get_template_directory() . '/inc/remote-content.php';
