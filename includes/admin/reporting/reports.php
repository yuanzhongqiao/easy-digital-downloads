<?php
/**
 * Admin Reports Page
 *
 * @package     Easy Digital Downloads
 * @subpackage  Admin Reports Page
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0 
*/


/**
 * Reports Page
 *
 * Renders the reports page contents.
 *
 * @access      private
 * @since       1.0
 * @return      void
*/

function edd_reports_page() {
	global $edd_options;	

	$current_page = admin_url( 'edit.php?post_type=download&page=edd-reports' );
	
	$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'reports';  

	?>
	<div class="wrap">

		<h2 class="nav-tab-wrapper">
			<a href="<?php echo add_query_arg( array( 'tab' => 'reports', 'settings-updated' => false ) ); ?>" class="nav-tab <?php echo $active_tab == 'reports' ? 'nav-tab-active' : ''; ?>">
				<?php _e('Reports', 'edd'); ?>
			</a>
			<a href="<?php echo add_query_arg( array( 'tab' => 'export', 'settings-updated' => false ) ); ?>" class="nav-tab <?php echo $active_tab == 'export' ? 'nav-tab-active' : ''; ?>">
				<?php _e('Export', 'edd'); ?>
			</a>
		</h2>
	
		<?php
	
		do_action( 'edd_reports_page_top' );

		if( $active_tab == 'reports' ) {
			do_action( 'edd_reports_tab_reports' );
		} elseif ( $active_tab == 'export' ) {
			do_action( 'edd_reports_tab_export' );
		}

		do_action( 'edd_reports_page_bottom' );

		?>

		<p>
			<a class="button" href="<?php echo wp_nonce_url( add_query_arg( array( 'edd-action' => 'generate_pdf' ) ), 'edd_generate_pdf' ); ?>">
				<?php _e( 'Download Sales and Earnings PDF Report for all Products', 'edd' ); ?>
			</a>
			<a class="button" href="<?php echo wp_nonce_url( add_query_arg( array( 'edd-action' => 'email_export' ) ), 'edd_email_export' ); ?>">
				<?php _e( 'Download a CSV Customers List', 'edd') ; ?>
			</a>
		</p>
		<p><?php _e( 'Please Note: Transactions created while in test mode are not included on this page or in the PDF reports.', 'edd' ); ?></p>

	</div><!--end wrap-->
	<?php
}

function edd_reports_tab_reports() {

	// default reporting views
	$views = array(
		'downloads' => edd_get_label_plural(),
		'customers'	=> __( 'Customers', 'edd' ),
		'earnings'	=> __( 'Earnings', 'edd' )
	);

	$views = apply_filters( 'edd_report_views', $views );

	// current view
	$current_view = isset( $_GET['view'] ) ? $_GET['view'] : 'downloads';

	?>
	<form id="edd-reports-filter" method="get">
		<div class="tablenav top">
			<div class="alignleft actions">
				<span><?php _e( 'Reporting Views', 'edd' ); ?></span>
		       	<input type="hidden" name="post_type" value="download"/>
		       	<input type="hidden" name="page" value="edd-reports"/>
		       	<select id="edd-reports-view" name="view">
		       		<?php foreach( $views as $view_id => $label ) : ?>
		       			<option value="<?php echo esc_attr( $view_id ); ?>" <?php selected( $view_id, $current_view ); ?>><?php echo $label; ?></option>
			       	<?php endforeach; ?>
		       	</select>
		       	<input type="submit" class="button-secondary" value="<?php _e( 'Apply', 'edd' ); ?>"/>
			</div>
		</div>
	</form>
	<?php

	do_action( 'edd_reports_view_' . $current_view );

}
add_action( 'edd_reports_tab_reports', 'edd_reports_tab_reports' );



function edd_reports_downloads_table() {

	include( dirname( __FILE__ ) . '/class-download-reports-table.php' );

	$downloads_table = new EDD_Download_Reports_Table();
    $downloads_table->prepare_items();
    $downloads_table->display();

}
add_action( 'edd_reports_view_downloads', 'edd_reports_downloads_table' );


function edd_reports_customers_table() {

	include( dirname( __FILE__ ) . '/class-customer-reports-table.php' );

	$downloads_table = new EDD_Customer_Reports_Table();
    $downloads_table->prepare_items();
    $downloads_table->display();

}
add_action( 'edd_reports_view_customers', 'edd_reports_customers_table' );

