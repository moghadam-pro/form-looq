<?php
/**
 * Bilingual product content.
 *
 * @package Formlooq_Theme
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function formlooq_content(): array {
	$data = require get_template_directory() . '/inc/content-data.php';
	return $data[ formlooq_language() ];
}
