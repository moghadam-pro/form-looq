<?php
/**
 * Exercise prepared repository queries against a real WordPress database.
 *
 * Run with wp eval-file after installing/activating the plugin on a test site.
 */

use FormLooq\DB;
use FormLooq\Entry_Repository;
use FormLooq\Form_Repository;

function form_looq_query_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$baseline_forms = Form_Repository::count();
$baseline_entries = Entry_Repository::count();
$form_id = Form_Repository::create(
	array(
		'title' => "SQL fixture 100%_O'Reilly",
		'status' => Form_Repository::STATUS_ACTIVE,
		'fields' => array(
			array( 'type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true ),
		),
	)
);
form_looq_query_assert( $form_id > 0, 'Could not create the SQL fixture.' );

try {
	form_looq_query_assert( $baseline_forms + 1 === Form_Repository::count(), 'Unfiltered form count failed.' );
	form_looq_query_assert( $baseline_forms + 1 === count( Form_Repository::query( array( 'per_page' => 200 ) ) ), 'Unfiltered form query failed.' );
	$args = array( 'search' => "100%_O'Reilly", 'status' => 'active', 'orderby' => 'title', 'order' => 'ASC' );
	$rows = Form_Repository::query( $args );
	form_looq_query_assert( 1 === count( $rows ) && $form_id === $rows[0]['id'], 'Literal wildcard/quote form search failed.' );
	form_looq_query_assert( 1 === Form_Repository::count( $args ), 'Filtered form count disagrees with its query.' );
	$args['search'] = "' OR 1=1 --";
	form_looq_query_assert( 0 === Form_Repository::count( $args ) && array() === Form_Repository::query( $args ), 'SQL-like search was not treated as literal data.' );
	$args['search'] = "100%_O'Reilly";
	$args['orderby'] = 'id; DROP TABLE wp_looq_forms';
	$args['order'] = 'DESC; DROP TABLE wp_looq_forms';
	form_looq_query_assert( 1 === count( Form_Repository::query( $args ) ) && DB::table_exists( DB::forms_table() ), 'Ordering allowlist failed.' );
	Form_Repository::record_view( $form_id );
	form_looq_query_assert( 1 === Form_Repository::get( $form_id )['views'], 'View update/cache invalidation failed.' );

	for ( $index = 0; $index < 3; ++$index ) {
		form_looq_query_assert( Entry_Repository::create( $form_id, array( 'name' => "100%_O'Reilly " . $index ) ) > 0, 'Entry insert failed.' );
	}
	form_looq_query_assert( $baseline_entries + 3 === Entry_Repository::count(), 'Unfiltered entry count failed.' );
	form_looq_query_assert( $baseline_entries + 3 === Entry_Repository::total(), 'Total entry count failed.' );
	$args = array( 'form_id' => $form_id, 'search' => "100%_O'Reilly", 'status' => 'unread', 'per_page' => 2, 'page' => 1, 'orderby' => 'id', 'order' => 'ASC' );
	$first_page = Entry_Repository::query( $args );
	$args['page'] = 2;
	$second_page = Entry_Repository::query( $args );
	form_looq_query_assert( 2 === count( $first_page ) && 1 === count( $second_page ), 'Filtered entry pagination failed.' );
	form_looq_query_assert( 3 === Entry_Repository::count( $args ), 'Filtered entry count failed.' );
	form_looq_query_assert( $first_page[1]['id'] < $second_page[0]['id'], 'Entry pages overlapped or ordering failed.' );
	form_looq_query_assert( null !== Entry_Repository::get( $first_page[0]['id'] ), 'Prepared single-entry read failed.' );
	$args['search'] = "' OR 1=1 --";
	form_looq_query_assert( 0 === Entry_Repository::count( $args ) && array() === Entry_Repository::query( $args ), 'SQL-like entry search was not literal.' );
	Entry_Repository::recount_all();
	form_looq_query_assert( 3 === Form_Repository::get( $form_id )['entries_count'], 'Full recount failed.' );
} finally {
	Form_Repository::delete( $form_id );
}
form_looq_query_assert( $baseline_forms === Form_Repository::count() && $baseline_entries === Entry_Repository::count(), 'Fixture cleanup failed.' );
echo "Repository query integration checks passed.\n";
