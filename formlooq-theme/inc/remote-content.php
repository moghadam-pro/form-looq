<?php
/** Machine-readable catalogues consumed by the Form LOOQ plugin. @package Formlooq_Theme */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function formlooq_addons_feed(): array {
	$items = array(
		array( 'polls', 'Polls', 'Single-question polls with live result bars.', 'chart-bar' ),
		array( 'email-notifications', 'Email notifications', 'Admin alerts and visitor confirmations after an entry.', 'email-alt' ),
		array( 'conditional-fields', 'Conditional fields', 'Show and hide fields based on previous answers.', 'randomize' ),
		array( 'multi-step', 'Multi-step forms', 'Break longer forms into focused steps.', 'editor-ol' ),
		array( 'file-uploads', 'File uploads', 'Receive validated files with form submissions.', 'media-default' ),
		array( 'sms-verification', 'SMS verification', 'Phone verification through configurable gateways.', 'smartphone' ),
	);
	return array(
		'version' => 1,
		'addons'  => array_map(
			static fn( array $item ): array => array(
				'slug' => $item[0], 'name' => $item[1], 'description' => $item[2], 'icon' => $item[3],
				'status' => 'planned', 'url' => formlooq_url( 'addons', 'en' ), 'badge' => 'Roadmap',
			),
			$items
		),
	);
}

function formlooq_docs_feed(): array {
	$docs = formlooq_url( 'docs', 'en' );
	return array(
		'version'  => 1,
		'sections' => array(
			array(
				'title' => 'Get started', 'description' => 'Install Form LOOQ and publish your first form.', 'url' => $docs . '#install',
				'articles' => array(
					array( 'title' => 'Install the plugin', 'url' => $docs . '#install', 'excerpt' => 'Upload the official ZIP, activate it, and open Forms.' ),
					array( 'title' => 'Create your first form', 'url' => $docs . '#first-form', 'excerpt' => 'Pick a template, name the form, and arrange fields.' ),
					array( 'title' => 'Embed a form', 'url' => $docs . '#embed', 'excerpt' => 'Use a shortcode, block, PHP template, or Elementor widget.' ),
				),
			),
			array(
				'title' => 'Manage submissions', 'description' => 'Work with entries, export files, spam controls, and privacy.', 'url' => $docs . '#entries',
				'articles' => array(
					array( 'title' => 'Entries and export', 'url' => $docs . '#entries', 'excerpt' => 'Review, filter, annotate, and export submissions.' ),
					array( 'title' => 'Spam and rate limits', 'url' => $docs . '#spam', 'excerpt' => 'Understand honeypot, timing, and hashed rate limits.' ),
					array( 'title' => 'Privacy and deletion', 'url' => $docs . '#privacy', 'excerpt' => 'Control retention, personal-data tools, and uninstall behavior.' ),
				),
			),
			array(
				'title' => 'Customize and extend', 'description' => 'Direction controls and public developer hooks.', 'url' => $docs . '#rtl',
				'articles' => array(
					array( 'title' => 'RTL and LTR', 'url' => $docs . '#rtl', 'excerpt' => 'Follow the site direction or override it per shortcode.' ),
					array( 'title' => 'Developer hooks', 'url' => $docs . '#developers', 'excerpt' => 'Extend forms, entries, templates, and rate-limit behavior.' ),
				),
			),
		),
	);
}
