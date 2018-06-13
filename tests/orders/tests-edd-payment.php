<?php
namespace EDD\Orders;

/**
 * EDD_Payment Tests.
 *
 * @group edd_orders
 *
 * @coversDefaultClass \EDD_Payment
 */

class EDD_Payment_Tests extends \EDD_UnitTestCase {

	/**
	 * Payment test fixture.
	 *
	 * @var \EDD_Payment
	 */
	protected $payment;

	public function setUp() {
		parent::setUp();

		$payment_id = \EDD_Helper_Payment::create_simple_payment();

		$this->payment = edd_get_payment( $payment_id );

		// Make sure we're working off a clean object caching in WP Core.
		// Prevents some payment_meta from not being present.
		clean_post_cache( $payment_id );
		update_postmeta_cache( array( $payment_id ) );
	}

	public function tearDown() {
		parent::tearDown();

		\EDD_Helper_Payment::delete_payment( $this->payment->ID );

		$this->payment = null;
	}

	public function test_IDs() {
		$this->assertSame( $this->payment->_ID, $this->payment->ID );
	}

	public function test_saving_updated_ID() {
		$expected = $this->payment->ID;

		$this->payment->ID = 12121222;
		$this->payment->save();

		$this->assertSame( $expected, $this->payment->ID );
	}

	public function test_EDD_Payment_total() {
		$this->assertEquals( 120.00, $this->payment->total );
	}

	public function test_edd_get_payment_by_transaction_ID_should_be_true() {
		$payment = edd_get_payment( 'FIR3SID3', true );

		$this->assertEquals( $payment->ID, $this->payment->ID );
	}

	public function test_instantiating_EDD_Payment_with_no_args_should_be_null() {
		$payment = new \EDD_Payment();
		$this->assertEquals( NULL, $payment->ID );
	}

	public function test_edd_get_payment_with_no_args_should_be_false() {
		$payment = edd_get_payment();

		$this->assertFalse( $payment );
	}

	public function test_edd_get_payment_with_invalid_id_should_be_false() {
		$payment = edd_get_payment( 99999999999 );

		$this->assertFalse( $payment );
	}

	public function test_instantiating_EDD_Payment_with_invalid_transaction_id_should_be_null() {
		$payment = new \EDD_Payment( 'false-txn', true );

		$this->assertEquals( NULL, $payment->ID );
	}

	public function test_edd_get_payment_with_invalid_transaction_id_should_be_false() {
		$payment = edd_get_payment( 'false-txn', true );

		$this->assertFalse( $payment );
	}

	public function test_updating_payment_status_to_pending() {
		$this->payment->update_status( 'pending' );
		$this->assertEquals( 'pending', $this->payment->status );
		$this->assertEquals( 'Pending', $this->payment->status_nicename );
	}

	public function test_updating_payment_status_to_publish() {
		// Test backwards compat
		edd_update_payment_status( $this->payment->ID, 'publish' );

		// Need to get the payment again since it's been updated
		$this->payment = edd_get_payment( $this->payment->ID );
		$this->assertEquals( 'publish', $this->payment->status );
		$this->assertEquals( 'Completed', $this->payment->status_nicename );
	}

	public function test_add_download() {

		// Test class vars prior to adding a download.
		$this->assertEquals( 2, count( $this->payment->downloads ) );
		$this->assertEquals( 120.00, $this->payment->total );

		$new_download = \EDD_Helper_Download::create_simple_download();

		$this->payment->add_download( $new_download->ID );
		$this->payment->save();

		$this->assertEquals( 3, count( $this->payment->downloads ) );
		$this->assertEquals( 140.00, $this->payment->total );
	}

	public function test_add_download_with_an_item_price_of_0() {

		// Test class vars prior to adding a download.
		$this->assertEquals( 2, count( $this->payment->downloads ) );
		$this->assertEquals( 120.00, $this->payment->total );

		$new_download = \EDD_Helper_Download::create_simple_download();

		$args = array(
			'item_price' => 0,
		);

		$this->payment->add_download( $new_download->ID, $args );
		$this->payment->save();

		$this->assertEquals( 3, count( $this->payment->downloads ) );
		$this->assertEquals( 120.00, $this->payment->total );
	}

	public function test_add_download_with_fee() {
		$args = array(
			'fees' => array(
				array(
					'amount' => 5,
					'label'  => 'Test Fee',
				),
			),
		);

		$new_download = \EDD_Helper_Download::create_simple_download();

		$this->payment->add_download( $new_download->ID, $args );
		$this->payment->save();

		$this->assertFalse( empty( $this->payment->cart_details[2]['fees'] ) );
	}

	public function test_remove_download() {
		$download_id = $this->payment->cart_details[0]['id'];
		$amount      = $this->payment->cart_details[0]['price'];
		$quantity    = $this->payment->cart_details[0]['quantity'];

		$remove_args = array(
			'amount'   => $amount,
			'quantity' => $quantity,
		);

		$this->payment->remove_download( $download_id, $remove_args );
		$this->payment->save();

		$this->assertEquals( 1, count( $this->payment->downloads ) );
		$this->assertEquals( 100.00, $this->payment->total );
	}

	public function test_remove_download_by_index() {
		$download_id = $this->payment->cart_details[1]['id'];

		$remove_args = array(
			'cart_index' => 1,
		);

		$this->payment->remove_download( $download_id, $remove_args );
		$this->payment->save();

		$this->assertEquals( 1, count( $this->payment->downloads ) );
		$this->assertEquals( 20.00, $this->payment->total );
	}

	public function test_remove_download_with_quantity() {
		global $edd_options;

		$edd_options['item_quantities'] = true;

		$payment_id = \EDD_Helper_Payment::create_simple_payment_with_quantity_tax();

		$payment = edd_get_payment( $payment_id );

		$testing_index = 1;
		$download_id   = $payment->cart_details[ $testing_index ]['id'];

		$remove_args = array(
			'quantity' => 1,
		);

		$payment->remove_download( $download_id, $remove_args );
		$payment->save();

		$payment = edd_get_payment( $payment_id );

		$this->assertEquals( 2, count( $payment->downloads ) );
		$this->assertEquals( 1, $payment->cart_details[ $testing_index ]['quantity'] );
		$this->assertEquals( 140.00, $payment->subtotal );
		$this->assertEquals( 12, $payment->tax );
		$this->assertEquals( 152.00, $payment->total );

		\EDD_Helper_Payment::delete_payment( $payment_id );
		unset( $edd_options['item_quantities'] );
	}

	public function test_payment_add_fee() {
		$this->payment->add_fee( array(
			'amount' => 5,
			'label'  => 'Test Fee 1',
		) );

		$this->assertEquals( 1, count( $this->payment->fees ) );
		$this->assertEquals( 125, $this->payment->total );

		$this->payment->save();

		$this->payment = edd_get_payment( $this->payment->ID );
		$this->assertEquals( 5, $this->payment->fees_total );
		$this->assertEquals( 125, $this->payment->total );

		// Test backwards compatibility with _edd_payment_meta.
		$payment_meta = edd_get_payment_meta( $this->payment->ID, '_edd_payment_meta', true );
		$this->assertArrayHasKey( 'fees', $payment_meta );

		$fees = $payment_meta['fees'];
		$this->assertEquals( 1, count( $fees ) );
	}

	public function test_user_info() {
		$this->assertSame( 'Admin', $this->payment->first_name );
		$this->assertSame( 'User', $this->payment->last_name );
	}

	public function test_for_serialized_user_info() {

		// Issue #4248
		$this->payment->user_info = serialize( array(
			'first_name' => 'John',
			'last_name' => 'Doe',
		) );

		$this->payment->save();

		$this->assertInternalType( 'array', $this->payment->user_info );

		foreach ( $this->payment->user_info as $key => $value ) {
			$this->assertFalse( is_serialized( $value ), $key . ' returned a searlized value' );
		}
	}

	public function test_modify_amount() {
		$args = array(
			'item_price' => '1,001.95',
		);

		$this->payment->modify_cart_item( 0, $args );
		$this->payment->save();

		$this->assertEquals( 1001.95, $this->payment->cart_details[0]['price'] );
	}

	public function test_payment_remove_fee() {
		for ( $i = 0; $i <= 2; $i++ ) {
			$this->payment->add_fee( array(
				'amount' => 5,
				'label'  => 'Test Fee ' . $i,
				'type'   => 'fee',
			) );
		}

		$this->payment->save();

		$this->assertEquals( 3, count( $this->payment->fees ) );
		$this->assertEquals( 'Test Fee 1', $this->payment->fees[1]['label'] );
		$this->assertEquals( 135, $this->payment->total );

		$this->payment->remove_fee( 1 );
		$this->payment->save();

		$this->assertEquals( 2, count( $this->payment->fees ) );
		$this->assertEquals( 130, $this->payment->total );
		$this->assertEquals( 'Test Fee 2', $this->payment->fees[1]['label'] );

		// Test that it saves to the DB
		$payment_meta = edd_get_payment_meta( $this->payment->ID, '_edd_payment_meta', true );

		$this->assertArrayHasKey( 'fees', $payment_meta );

		$fees = $payment_meta['fees'];

		$this->assertEquals( 2, count( $fees ) );
		$this->assertEquals( 'Test Fee 2', $fees[2]['label'] );
	}

	public function test_payment_remove_fee_by_index() {
		for ( $i = 0; $i <= 2; $i++ ) {
			$this->payment->add_fee( array(
				'amount' => 5,
				'label'  => 'Test Fee ' . $i,
				'type'   => 'fee',
			) );
		}

		$this->payment->save();

		$this->assertEquals( 3, count( $this->payment->fees ) );
		$this->assertEquals( 'Test Fee 1', $this->payment->fees[1]['label'] );
		$this->assertEquals( 135, $this->payment->total );

		$this->payment->remove_fee_by( 'index', 1, true );
		$this->payment->save();

		$this->assertEquals( 2, count( $this->payment->fees ) );
		$this->assertEquals( 130, $this->payment->total );
		$this->assertEquals( 'Test Fee 2', $this->payment->fees[1]['label'] );

		// Test that it saves to the DB
		$payment_meta = edd_get_payment_meta( $this->payment->ID, '_edd_payment_meta', true );

		$this->assertArrayHasKey( 'fees', $payment_meta );

		$fees = $payment_meta['fees'];

		$this->assertEquals( 2, count( $fees ) );
		$this->assertEquals( 'Test Fee 2', $fees[2]['label'] );
	}

	public function test_payment_with_initial_fee() {
		add_filter( 'edd_cart_contents', '__return_true' );
		add_filter( 'edd_item_quantities_enabled', '__return_true' );

		$payment_id = \EDD_Helper_Payment::create_simple_payment_with_fee();

		$payment = edd_get_payment( $payment_id );

		$this->assertFalse( empty( $payment->fees ) );
		$this->assertEquals( 47, $payment->total );

		remove_filter( 'edd_cart_contents', '__return_true' );
		remove_filter( 'edd_item_quantities_enabled', '__return_true' );
	}

	public function test_update_date_future() {
		$current_date = $this->payment->date;

		$new_date = strtotime( $this->payment->date ) + DAY_IN_SECONDS;
		$this->payment->date = date( 'Y-m-d H:i:s', $new_date );
		$this->payment->save();

		$date2 = strtotime( $this->payment->date );
		$this->assertEquals( $new_date, $date2 );
	}

	public function test_update_date_past() {
		$current_date = $this->payment->date;

		$new_date = strtotime( $this->payment->date ) - DAY_IN_SECONDS;
		$this->payment->date = date( 'Y-m-d H:i:s', $new_date );
		$this->payment->save();

		$date2    = strtotime( $this->payment->date );
		$this->assertEquals( $new_date, $date2 );
	}

	public function test_refund_payment() {
		$this->payment->status = 'complete';
		$this->payment->save();

		$download = new \EDD_Download( $this->payment->downloads[0]['id'] );
		$earnings = $download->earnings;
		$sales    = $download->sales;

		$store_earnings = edd_get_total_earnings();
		$store_sales    = edd_get_total_sales();

		$this->payment->refund();

		wp_cache_flush();

		$this->assertEquals( 'refunded', $this->payment->status );

		$download2 = new \EDD_Download( $download->ID );

		$this->assertEquals( $earnings - $download->price, $download2->earnings );
		$this->assertEquals( $sales - 1, $download2->sales );

		$this->assertEquals( $store_earnings - $this->payment->total, edd_get_total_earnings() );
		$this->assertEquals( $store_sales - 1, edd_get_total_sales() );
	}

	public function test_modifying_address() {
		$this->payment->address = array(
			'line1'   => '123 Main St',
			'line2'   => '',
			'city'    => 'New York City',
			'state'   => 'New York',
			'zip'     => '10010',
			'country' => 'US',
		);
		$this->payment->save();

		$this->assertEquals( $this->payment->address, $this->payment->user_info['address'] );
	}

	public function test_modify_cart_item_price() {
		$this->payment->status = 'publish';
		$this->payment->save();

		$this->payment->modify_cart_item( 0, array( 'item_price' => 1 ) );
		$this->payment->save();

		$this->assertEquals( 1, $this->payment->cart_details[0]['item_price'] );

		$download = new \EDD_Download( $this->payment->cart_details[0]['id'] );
		$this->assertEquals( 1, $download->get_earnings() );
	}

	public function test_modify_cart_item_tax() {
		$this->payment->status = 'publish';
		$this->payment->save();

		$this->payment->modify_cart_item( 0, array( 'tax' => 2 ) );
		$this->payment->save();

		$this->assertEquals( 2, $this->payment->cart_details[0]['tax'] );
		$this->assertEquals( 2, $this->payment->tax );
	}

	public function test_modify_cart_item_with_disallowed_changes_should_return_false() {
		$this->payment->status = 'publish';
		$this->payment->save();

		$change_permitted = $this->payment->modify_cart_item( 0, array(
			'quantity'   => $this->payment->cart_details[0]['quantity'],
			'item_price' => $this->payment->cart_details[0]['price'],
		) );

		$this->assertFalse( $change_permitted );
	}

	/* Helpers ***************************************************************/

	public function alter_payment_meta( $meta, $payment_data ) {
		$meta['user_info']['address']['country'] = 'PL';

		return $meta;
	}

	public function add_meta() {
		$this->assertTrue( $this->payment->add_meta( '_test_add_payment_meta', 'test' ) );
	}

	public function add_meta_false_empty_key() {
		$this->assertFalse( $this->payment->add_meta( '', 'test' ) );
	}

	public function add_meta_unique_false() {
		$this->assertFalse( $this->payment->add_meta( '_edd_payment_key', 'test', true ) );
	}

	public function delete_meta() {
		$this->assertTrue( $this->payment->delete_meta( '_edd_payment_key' ) );
	}

	public function delete_meta_no_key() {
		$this->assertFalse( $this->payment->delete_meta( '' ) );
	}

	public function delete_meta_missing_key() {
		$this->assertFalse( $this->payment->delete_meta( '_edd_nonexistant_key' ) );
	}
}
