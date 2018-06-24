<?php
/**
 * Earnings by Taxonomy list table.
 *
 * @package     EDD
 * @subpackage  Reports/Data/File_Downloads
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */
namespace EDD\Reports\Data\Downloads;

use EDD\Reports as Reports;
use EDD\Orders as Orders;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Earnings_By_Taxonomy_List_Table class.
 *
 * @since 3.0
 */
class Earnings_By_Taxonomy_List_Table extends \WP_List_Table {

	/**
	 * Query the database and fetch the top five most downloaded products.
	 *
	 * @since 3.0
	 *
	 * @return array Taxonomies.
	 */
	public function taxonomy_data() {
		global $wpdb;

		$date         = EDD()->utils->date( 'now' );
		$date_filters = Reports\get_dates_filter_options();
		$filter       = Reports\get_filter_value( 'dates' );
		$date_range   = Reports\parse_dates_for_range( $date, $filter['range'] );

		// Generate date query SQL if dates have been set.
		$date_query_sql = '';

		if ( ! empty( $date_range['start'] ) || ! empty( $date_range['end'] ) ) {
			if ( ! empty( $date_range['start'] ) ) {
				$date_query_sql .= $wpdb->prepare( 'AND date_created >= %s', $date_range['start'] );
			}

			// Join dates with `AND` if start and end date set.
			if ( ! empty( $date_range['start'] ) && ! empty( $date_range['end'] ) ) {
				$date_query_sql .= ' AND ';
			}

			if ( ! empty( $date_range['end'] ) ) {
				$date_query_sql .= $wpdb->prepare( 'date_created <= %s', $date_range['end'] );
			}
		}

		$stats = new Orders\Stats( array(
			'range' => $filter['range'],
		) );

		$taxonomies = get_object_taxonomies( 'download', 'names' );
		$taxonomies = array_map( 'sanitize_text_field', $taxonomies );

		$placeholders = implode( ', ', array_fill( 0, count( $taxonomies ), '%s' ) );

		$taxonomy__in = $wpdb->prepare( "tt.taxonomy IN ({$placeholders})", $taxonomies );

		$sql = "SELECT t.*, tt.*, tr.object_id
				FROM {$wpdb->terms} AS t
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
				INNER JOIN {$wpdb->term_relationships} AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE {$taxonomy__in}";

		$results = $wpdb->get_results( $sql );

		// Build intermediate array to allow for better data processing.
		$taxonomies = array();
		foreach ( $results as $r ) {
			$taxonomies[ absint( $r->term_id ) ]['name']         = esc_html( $r->name );
			$taxonomies[ absint( $r->term_id ) ]['object_ids'][] = absint( $r->object_id );
			$taxonomies[ absint( $r->term_id ) ]['parent']       = absint( $r->parent );
		}

		$data = array();

		foreach ( $taxonomies as $k => $t ) {
			$c = new \stdClass();
			$c->id   = $k;
			$c->name = $taxonomies[ $k ]['name'];

			$placeholders   = implode( ', ', array_fill( 0, count( $taxonomies[ $k ]['object_ids'] ), '%d' ) );
			$product_id__in = $wpdb->prepare( "product_id IN({$placeholders})", $taxonomies[ $k ]['object_ids'] );

			$sql = "SELECT total, COUNT(id) AS sales
					FROM {$wpdb->edd_order_items}
					WHERE {$product_id__in} {$date_query_sql}";

			$result = $wpdb->get_row( $sql );

			$earnings = null === $result->total
				? 0.00
				: floatval( $result->total );

			$sales = null === $result->sales
				? 0
				: absint( $result->sales );

			$c->sales    = $sales;
			$c->earnings = $earnings;
			$c->parent   = $t['parent'];

			$data[] = $c;
		}

		return $data;
	}

	/**
	 * Retrieve the table columns.
	 *
	 * @since 3.0
	 *
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {
		return array(
			'taxonomy'       => __( 'Taxonomy', 'easy-digital-downloads' ),
			'total_sales'    => __( 'Total Sales', 'easy-digital-downloads' ),
			'total_earnings' => __( 'Total Earnings', 'easy-digital-downloads' ),
			'avg_sales'      => __( 'Monthly Sales Average', 'easy-digital-downloads' ),
			'avg_earnings'   => __( 'Monthly Earnings Average', 'easy-digital-downloads' ),
		);
	}

	/**
	 * Setup the final data for the table.
	 *
	 * @since 3.0
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->taxonomy_data();
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 3.0
	 */
	public function no_items() {
		esc_html_e( 'No taxonomies found.', 'easy-digital-downloads' );
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @since 3.0
	 * @access protected
	 *
	 * @return string Name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'name';
	}

	/**
	 * Return empty array to disable sorting.
	 *
	 * @since 3.0
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array();
	}

	/**
	 * Return empty array to remove bulk actions.
	 *
	 * @since 3.0
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array();
	}

	/**
	 * Hide pagination.
	 *
	 * @since 3.0
	 *
	 * @param string $which
	 */
	protected function pagination( $which ) {

	}

	/**
	 * Hide table navigation.
	 *
	 * @since 3.0
	 *
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {

	}
}