<?php
/**
 * Fetches the add-on catalogue and documentation index from the project site.
 *
 * Every remote call is cached in a transient, degrades to a bundled fallback,
 * and can be switched off entirely in Settings → Add-ons.
 *
 * Expected JSON contract (documented in docs/REMOTE-CONTENT.md):
 *
 *   addons.json  { "version": 1, "addons": [ { "slug", "name", "description",
 *                  "icon", "status", "url", "badge" } ] }
 *   docs.json    { "version": 1, "sections": [ { "title", "description",
 *                  "url", "articles": [ { "title", "url", "excerpt" } ] } ] }
 *
 * @package FreeMPROForms
 */

namespace FreeMPROForms;

defined( 'ABSPATH' ) || exit;

final class Remote_Content {
	public const ADDONS_URL = Plugin::HOME_URL . '/addons';
	public const DOCS_URL   = Plugin::HOME_URL . '/docs';

	private const ADDONS_ENDPOINT = self::ADDONS_URL . '.json';
	private const DOCS_ENDPOINT   = self::DOCS_URL . '.json';

	private const TRANSIENT_ADDONS = 'fmpf_remote_addons';
	private const TRANSIENT_DOCS   = 'fmpf_remote_docs';

	private const TIMEOUT = 8;

	/**
	 * @return array{source: string, addons: array<int, array<string, string>>}
	 */
	public static function addons(): array {
		$remote = self::fetch( self::ADDONS_ENDPOINT, self::TRANSIENT_ADDONS );
		$addons = self::normalize_addons( (array) ( $remote['addons'] ?? array() ) );

		if ( $addons ) {
			return array(
				'source' => 'remote',
				'addons' => $addons,
			);
		}

		return array(
			'source' => 'bundled',
			'addons' => self::normalize_addons( self::fallback_addons() ),
		);
	}

	/**
	 * @return array{source: string, sections: array<int, array<string, mixed>>}
	 */
	public static function docs(): array {
		$remote   = self::fetch( self::DOCS_ENDPOINT, self::TRANSIENT_DOCS );
		$sections = self::normalize_docs( (array) ( $remote['sections'] ?? array() ) );

		if ( $sections ) {
			return array(
				'source'   => 'remote',
				'sections' => $sections,
			);
		}

		return array(
			'source'   => 'bundled',
			'sections' => self::normalize_docs( self::fallback_docs() ),
		);
	}

	public static function flush(): void {
		delete_transient( self::TRANSIENT_ADDONS );
		delete_transient( self::TRANSIENT_DOCS );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function fetch( string $url, string $transient ): array {
		if ( ! Settings::get( 'addons_remote_enabled', true ) ) {
			return array();
		}

		$cached = get_transient( $transient );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => self::TIMEOUT,
				'user-agent' => 'FreeMPROForms/' . FREE_MPRO_FORMS_VERSION . '; ' . home_url( '/' ),
				'headers'    => array( 'Accept' => 'application/json' ),
			)
		);

		$ttl = max( 1, (int) Settings::get( 'addons_cache_hours', 12 ) ) * HOUR_IN_SECONDS;

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			// Cache the miss briefly so a broken endpoint does not slow every page load.
			set_transient( $transient, array(), min( $ttl, HOUR_IN_SECONDS ) );

			return array();
		}

		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$decoded = is_array( $decoded ) ? $decoded : array();

		set_transient( $transient, $decoded, $ttl );

		return $decoded;
	}

	/**
	 * @param array<int, mixed> $addons Raw catalogue entries.
	 * @return array<int, array<string, string>>
	 */
	private static function normalize_addons( array $addons ): array {
		$clean = array();

		foreach ( array_slice( $addons, 0, 60 ) as $addon ) {
			if ( ! is_array( $addon ) ) {
				continue;
			}

			$name = sanitize_text_field( (string) ( $addon['name'] ?? '' ) );
			$slug = sanitize_key( (string) ( $addon['slug'] ?? '' ) );

			if ( '' === $name || '' === $slug ) {
				continue;
			}

			$clean[] = array(
				'slug'        => $slug,
				'name'        => $name,
				'description' => sanitize_text_field( (string) ( $addon['description'] ?? '' ) ),
				'icon'        => sanitize_key( (string) ( $addon['icon'] ?? 'admin-plugins' ) ),
				'status'      => in_array( ( $addon['status'] ?? '' ), array( 'available', 'planned' ), true )
					? (string) $addon['status']
					: 'planned',
				'url'         => esc_url_raw( (string) ( $addon['url'] ?? '' ) ),
				'badge'       => sanitize_text_field( (string) ( $addon['badge'] ?? '' ) ),
			);
		}

		return $clean;
	}

	/**
	 * @param array<int, mixed> $sections Raw documentation sections.
	 * @return array<int, array<string, mixed>>
	 */
	private static function normalize_docs( array $sections ): array {
		$clean = array();

		foreach ( array_slice( $sections, 0, 30 ) as $section ) {
			if ( ! is_array( $section ) ) {
				continue;
			}

			$title = sanitize_text_field( (string) ( $section['title'] ?? '' ) );

			if ( '' === $title ) {
				continue;
			}

			$articles = array();

			foreach ( array_slice( (array) ( $section['articles'] ?? array() ), 0, 30 ) as $article ) {
				if ( ! is_array( $article ) ) {
					continue;
				}

				$article_title = sanitize_text_field( (string) ( $article['title'] ?? '' ) );

				if ( '' === $article_title ) {
					continue;
				}

				$articles[] = array(
					'title'   => $article_title,
					'url'     => esc_url_raw( (string) ( $article['url'] ?? '' ) ),
					'excerpt' => sanitize_text_field( (string) ( $article['excerpt'] ?? '' ) ),
				);
			}

			$clean[] = array(
				'title'       => $title,
				'description' => sanitize_text_field( (string) ( $section['description'] ?? '' ) ),
				'url'         => esc_url_raw( (string) ( $section['url'] ?? '' ) ),
				'articles'    => $articles,
			);
		}

		return $clean;
	}

	/**
	 * Bundled catalogue used when the site is unreachable or fetching is off.
	 *
	 * @return array<int, array<string, string>>
	 */
	private static function fallback_addons(): array {
		return array(
			array(
				'slug'        => 'polls',
				'name'        => __( 'Polls', 'free-mpro-forms' ),
				'description' => __( 'Single-question polls with live result bars.', 'free-mpro-forms' ),
				'icon'        => 'chart-bar',
				'status'      => 'planned',
			),
			array(
				'slug'        => 'email-notifications',
				'name'        => __( 'Email notifications', 'free-mpro-forms' ),
				'description' => __( 'Send admin alerts and visitor confirmations after each entry.', 'free-mpro-forms' ),
				'icon'        => 'email-alt',
				'status'      => 'planned',
			),
			array(
				'slug'        => 'discount-codes',
				'name'        => __( 'Discount codes', 'free-mpro-forms' ),
				'description' => __( 'Validate coupon codes inside order forms.', 'free-mpro-forms' ),
				'icon'        => 'tickets-alt',
				'status'      => 'planned',
			),
			array(
				'slug'        => 'quiz',
				'name'        => __( 'Quiz', 'free-mpro-forms' ),
				'description' => __( 'Scored questions with pass marks and per-answer feedback.', 'free-mpro-forms' ),
				'icon'        => 'welcome-learn-more',
				'status'      => 'planned',
			),
			array(
				'slug'        => 'survey',
				'name'        => __( 'Survey', 'free-mpro-forms' ),
				'description' => __( 'Multi-page surveys with aggregated reporting.', 'free-mpro-forms' ),
				'icon'        => 'feedback',
				'status'      => 'planned',
			),
			array(
				'slug'        => 'registration',
				'name'        => __( 'User registration', 'free-mpro-forms' ),
				'description' => __( 'Create WordPress accounts from a form submission.', 'free-mpro-forms' ),
				'icon'        => 'admin-users',
				'status'      => 'planned',
			),
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function fallback_docs(): array {
		return array(
			array(
				'title'       => __( 'Getting started', 'free-mpro-forms' ),
				'description' => __( 'Create your first form and place it on a page.', 'free-mpro-forms' ),
				'url'         => self::DOCS_URL,
				'articles'    => array(
					array(
						'title'   => __( 'Creating a form', 'free-mpro-forms' ),
						'url'     => self::DOCS_URL,
						'excerpt' => __( 'Pick a template, name the form, and arrange fields in the builder.', 'free-mpro-forms' ),
					),
					array(
						'title'   => __( 'Embedding a form', 'free-mpro-forms' ),
						'url'     => self::DOCS_URL,
						'excerpt' => __( 'Copy the shortcode from the Embed tab into any post, page, or builder.', 'free-mpro-forms' ),
					),
				),
			),
			array(
				'title'       => __( 'Entries and privacy', 'free-mpro-forms' ),
				'description' => __( 'How submissions are stored, exported, and erased.', 'free-mpro-forms' ),
				'url'         => self::DOCS_URL,
				'articles'    => array(
					array(
						'title'   => __( 'Where entries are stored', 'free-mpro-forms' ),
						'url'     => self::DOCS_URL,
						'excerpt' => __( 'Entries live in dedicated database tables on your own site and are never sent anywhere else.', 'free-mpro-forms' ),
					),
					array(
						'title'   => __( 'Retention and erasure', 'free-mpro-forms' ),
						'url'     => self::DOCS_URL,
						'excerpt' => __( 'Set a retention window, and use the WordPress privacy tools for personal data requests.', 'free-mpro-forms' ),
					),
				),
			),
		);
	}
}
