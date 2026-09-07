<?php
/**
 * Fetches the add-on catalogue and documentation index from the project site.
 *
 * Off by default. Nothing is requested until an admin explicitly turns this
 * on under Settings → Add-ons, where the exact request contents are disclosed
 * first. Every call is cached in a transient, degrades to bundled content,
 * and can be switched back off at any time.
 *
 * Expected JSON contract (documented in docs/REMOTE-CONTENT.md):
 *
 *   addons.json  { "version": 1, "addons": [ { "slug", "name", "description",
 *                  "icon", "status", "url", "badge" } ] }
 *   docs.json    { "version": 1, "sections": [ { "title", "description",
 *                  "url", "articles": [ { "title", "url", "excerpt" } ] } ] }
 *
 * @package FormLooq
 */

namespace FormLooq;

defined( 'ABSPATH' ) || exit;

final class Remote_Content {
	public const ADDONS_URL = Plugin::HOME_URL . '/addons';
	public const DOCS_URL   = Plugin::HOME_URL . '/docs';

	private const ADDONS_ENDPOINT = Plugin::HOME_URL . '/addons.json';
	private const DOCS_ENDPOINT   = Plugin::HOME_URL . '/docs.json';

	private const TRANSIENT_ADDONS = 'form_looq_remote_addons';
	private const TRANSIENT_DOCS   = 'form_looq_remote_docs';

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
			'sections' => array(),
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
		if ( ! Settings::get( 'addons_remote_enabled', false ) ) {
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
				'user-agent' => 'FormLOOQ/' . FORM_LOOQ_VERSION . '; ' . home_url( '/' ),
				'headers'    => array( 'Accept' => 'application/json' ),
			)
		);

		$ttl = max( 1, (int) Settings::get( 'addons_cache_hours', 12 ) ) * HOUR_IN_SECONDS;

		// A misconfigured host can return its own homepage with a 200 status for
		// any missing path (including this JSON endpoint) instead of a 404, so
		// the response code alone isn't proof the body is the JSON expected here.
		$content_type = (string) wp_remote_retrieve_header( $response, 'content-type' );

		if (
			is_wp_error( $response )
			|| 200 !== (int) wp_remote_retrieve_response_code( $response )
			|| ! str_contains( $content_type, 'json' )
		) {
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
	 * Bundled catalogue used when the setting is off or the site is unreachable.
	 *
	 * @return array<int, array<string, string>>
	 */
	private static function fallback_addons(): array {
		return array(
			array(
				'slug'        => 'polls',
				'name'        => __( 'Polls', 'form-looq' ),
				'description' => __( 'Single-question polls with live result bars.', 'form-looq' ),
				'icon'        => 'chart-bar',
				'status'      => 'planned',
			),
			array(
				'slug'        => 'email-notifications',
				'name'        => __( 'Email notifications', 'form-looq' ),
				'description' => __( 'Send admin alerts and visitor confirmations after each entry.', 'form-looq' ),
				'icon'        => 'email-alt',
				'status'      => 'planned',
			),
			array(
				'slug'        => 'discount-codes',
				'name'        => __( 'Discount codes', 'form-looq' ),
				'description' => __( 'Validate coupon codes inside order forms.', 'form-looq' ),
				'icon'        => 'tickets-alt',
				'status'      => 'planned',
			),
			array(
				'slug'        => 'quiz',
				'name'        => __( 'Quiz', 'form-looq' ),
				'description' => __( 'Scored questions with pass marks and per-answer feedback.', 'form-looq' ),
				'icon'        => 'welcome-learn-more',
				'status'      => 'planned',
			),
			array(
				'slug'        => 'survey',
				'name'        => __( 'Survey', 'form-looq' ),
				'description' => __( 'Multi-page surveys with aggregated reporting.', 'form-looq' ),
				'icon'        => 'feedback',
				'status'      => 'planned',
			),
			array(
				'slug'        => 'registration',
				'name'        => __( 'User registration', 'form-looq' ),
				'description' => __( 'Create WordPress accounts from a form submission.', 'form-looq' ),
				'icon'        => 'admin-users',
				'status'      => 'planned',
			),
		);
	}
}
