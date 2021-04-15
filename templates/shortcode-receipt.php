<?php
/**
 * Shortcode: Receipt - [edd_receipt]
 *
 * @package EDD
 * @category Template
 *
 * @since 3.0 Check status of order item when showing download link, instead of order itself.
 *            Show "Refunded" next to any refunded order items.
 */

global $edd_receipt_args;
$order = edd_get_order( $edd_receipt_args['id'] );

// Display a notice if the order was not found in the database.
if ( ! $order ) : ?>

	<div class="edd_errors edd-alert edd-alert-error">
		<?php esc_html_e( 'The specified receipt ID appears to be invalid.', 'easy-digital-downloads' ); ?>
	</div>

	<?php
	return;
endif;

$meta    = edd_get_payment_meta( $order->id );
$cart    = edd_get_payment_meta_cart_details( $order->id, true );

/**
 * Allows additional output before displaying the receipt table.
 *
 * @since 3.0
 *
 * @param \EDD\Orders\Order $order          Current order.
 * @param array             $edd_receipt_args [edd_receipt] shortcode arguments.
 */
do_action( 'edd_order_receipt_before_table', $order, $edd_receipt_args );
?>
<table id="edd_purchase_receipt" class="edd-table">
	<thead>
		<?php do_action( 'edd_order_receipt_before', $order, $edd_receipt_args ); ?>

		<?php if ( filter_var( $edd_receipt_args['payment_id'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
		<tr>
			<th><strong><?php echo esc_html_x( 'Order', 'heading', 'easy-digital-downloads' ); ?>:</strong></th>
			<th><?php echo esc_html( $order->get_number() ); ?></th>
		</tr>
		<?php endif; ?>
	</thead>

	<tbody>
		<tr>
			<td class="edd_receipt_payment_status"><strong><?php esc_html_e( 'Order Status', 'easy-digital-downloads' ); ?>:</strong></td>
			<td class="edd_receipt_payment_status <?php echo esc_html( strtolower( $order->status ) ); ?>"><?php echo esc_html( edd_get_status_label( $order->status ) ); ?></td>
		</tr>

		<?php if ( filter_var( $edd_receipt_args['payment_key'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
			<tr>
				<td><strong><?php esc_html_e( 'Payment Key', 'easy-digital-downloads' ); ?>:</strong></td>
				<td><?php echo esc_html( $order->payment_key ); ?></td>
			</tr>
		<?php endif; ?>

		<?php if ( filter_var( $edd_receipt_args['payment_method'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
			<tr>
				<td><strong><?php esc_html_e( 'Payment Method', 'easy-digital-downloads' ); ?>:</strong></td>
				<td><?php echo esc_html( edd_get_gateway_checkout_label( $order->gateway ) ); ?></td>
			</tr>
		<?php endif; ?>
		<?php if ( filter_var( $edd_receipt_args['date'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
		<tr>
			<td><strong><?php esc_html_e( 'Date', 'easy-digital-downloads' ); ?>:</strong></td>
			<td><?php echo esc_html( edd_date_i18n( EDD()->utils->date( $order->date_created, null, true )->toDateTimeString() ) ); ?></td>
		</tr>
		<?php endif; ?>

		<?php
		$fees = edd_get_order_adjustments(
			array(
				'type'        => 'fee',
				'object_type' => 'order',
				'object_id'   => $order->id,
			)
		);
		if ( ! empty( $fees ) ) :
			?>
			<tr>
				<td><strong><?php esc_html_e( 'Fees', 'easy-digital-downloads' ); ?>:</strong></td>
				<td>
					<ul class="edd_receipt_fees">
					<?php
					foreach ( $fees as $fee ) :
						$label = __( 'Order Fee', 'easy-digital-downloads' );
						if ( ! empty( $fee->description ) ) {
							$label .= ': ' . $fee->description;
						}
						?>
						<li>
							<span class="edd_fee_label"><?php echo esc_html( $label ); ?></span>
							<span class="edd_fee_sep">&nbsp;&ndash;&nbsp;</span>
							<span class="edd_fee_amount"><?php echo esc_html( edd_currency_filter( edd_format_amount( $fee->subtotal ) ) ); ?></span>
						</li>
					<?php endforeach; ?>
					</ul>
				</td>
			</tr>
		<?php endif; ?>

		<?php
		if ( filter_var( $edd_receipt_args['discount'], FILTER_VALIDATE_BOOLEAN ) ) :
			$discounts = $order->get_discounts();
			if ( $discounts ) :
				$label = _n( 'Discount', 'Discounts', count( $discounts ), 'easy-digital-downloads' );
				?>
				<tr>
					<td><strong><?php echo esc_html( $label ); ?>:</strong></td>
					<?php
					foreach ( $discounts as $discount ) {
						$name   = $discount->description;
						$amount = edd_currency_filter( edd_format_amount( edd_negate_amount( $discount->total ) ) );
						if ( 'percent' === edd_get_discount_type( $discount->type_id ) ) {
							$rate  = edd_format_discount_rate( 'percent', edd_get_discount_amount( $discount->type_id ) );
							$name .= "&nbsp;({$rate})";
						}
						?>
						<td><?php echo esc_html( $name . ': ' . $amount ); ?></td>
						<?php
					}
					?>
				</tr>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( $order->tax > 0 ) : ?>
			<tr>
				<td><strong><?php esc_html_e( 'Tax', 'easy-digital-downloads' ); ?>:</strong></td>
				<td><?php echo esc_html( edd_payment_tax( $order->id ) ); ?></td>
			</tr>
		<?php endif; ?>

		<?php if ( filter_var( $edd_receipt_args['price'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
			<tr>
				<td><strong><?php esc_html_e( 'Subtotal', 'easy-digital-downloads' ); ?>:</strong></td>
				<td>
					<?php echo esc_html( edd_currency_filter( edd_format_amount( $order->subtotal ) ) ); ?>
				</td>
			</tr>

			<tr>
				<td><strong><?php esc_html_e( 'Total Price', 'easy-digital-downloads' ); ?>:</strong></td>
				<td><?php echo esc_html( edd_currency_filter( edd_format_amount( $order->total ) ) ); ?></td>
			</tr>
		<?php endif; ?>

		<?php
		/**
		 * Fires at the end of the order receipt `tbody`.
		 *
		 * @since 3.0
		 * @param \EDD\Orders\Order $order          Current order.
		 * @param array             $edd_receipt_args [edd_receipt] shortcode arguments.
		 */
		do_action( 'edd_order_receipt_after', $order, $edd_receipt_args );
		?>
	</tbody>
</table>

<?php
/**
 * Fires after the order receipt table.
 *
 * @since 3.0
 * @param \EDD\Orders\Order $order          Current order.
 * @param array             $edd_receipt_args [edd_receipt] shortcode arguments.
 */
do_action( 'edd_order_receipt_after_table', $order, $edd_receipt_args );

if ( filter_var( $edd_receipt_args['products'], FILTER_VALIDATE_BOOLEAN ) ) :
	$order_items = $order->get_items();
	if ( ! empty( $order_items ) ) :
		?>

		<h3><?php echo esc_html( apply_filters( 'edd_payment_receipt_products_title', __( 'Products', 'easy-digital-downloads' ) ) ); ?></h3>

		<table id="edd_purchase_receipt_products" class="edd-table">
			<thead>
				<th><?php esc_html_e( 'Name', 'easy-digital-downloads' ); ?></th>
				<?php if ( edd_use_skus() ) { ?>
					<th><?php esc_html_e( 'SKU', 'easy-digital-downloads' ); ?></th>
				<?php } ?>
				<?php if ( edd_item_quantities_enabled() ) : ?>
					<th><?php esc_html_e( 'Quantity', 'easy-digital-downloads' ); ?></th>
				<?php endif; ?>
				<th><?php esc_html_e( 'Price', 'easy-digital-downloads' ); ?></th>
			</thead>

			<tbody>
				<?php foreach ( $order_items as $key => $item ) : ?>
					<?php
					// Skip this item if we can't view it.
					if ( ! apply_filters( 'edd_user_can_view_receipt_item', true, $item ) ) {
						continue;
					}
					$item_fees = edd_get_order_adjustments(
						array(
							'type'        => 'fee',
							'object_type' => 'order_item',
							'object_id'   => $item->id,
						)
					);
					?>

					<tr>
						<td>
							<?php $download_files = edd_get_download_files( $item->product_id, $item->price_id ); ?>

							<div class="edd_purchase_receipt_product_name">
								<?php
								echo esc_html( $item->product_name );

								if ( ! empty( $item->status ) && 'complete' !== $item->status ) {
									echo ' &ndash; ' . esc_html( edd_get_status_label( $item->status ) );
								}
								?>
							</div>
							<?php if ( $item_fees ) : ?>
								<ul class="edd_purchase_receipt_order_item_fees">
									<?php
									foreach ( $item_fees as $item_fee ) :
										$label = __( 'Fee', 'easy-digital-downloads' );
										if ( ! empty( $item_fee->description ) ) {
											$label .= ': ' . $item_fee->description;
										}
										?>
										<li>
											<span class="edd_fee_label"><?php echo esc_html( $label ); ?></span> &mdash; <span class="edd_fee_amount"><?php echo esc_html( edd_currency_filter( edd_format_amount( $item_fee->total ) ) ); ?></span>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
							<?php
							$notes = edd_get_product_notes( $item->product_id );
							if ( ! empty( $notes ) ) : ?>
								<div class="edd_purchase_receipt_product_notes"><?php echo wp_kses_post( wpautop( $notes ) ); ?></div>
							<?php endif; ?>

							<?php if ( 'refunded' !== $item->status && edd_receipt_show_download_files( $item->product_id, $edd_receipt_args, $cart[ $item->cart_index ] ) ) : ?>
							<ul class="edd_purchase_receipt_files">
								<?php
								if ( ! empty( $download_files ) && is_array( $download_files ) ) :
									foreach ( $download_files as $filekey => $file ) :
										?>
										<li class="edd_download_file">
											<a href="<?php echo esc_url( edd_get_download_file_url( $order->payment_key, $order->email, $filekey, $item->product_id, $item->price_id ) ); ?>" class="edd_download_file_link"><?php echo esc_html( edd_get_file_name( $file ) ); ?></a>
										</li>
										<?php
										do_action( 'edd_receipt_files', $filekey, $file, $item->product_id, $order->id, $meta );
									endforeach;
								elseif ( edd_is_bundled_product( $item->product_id ) ) :
									$bundled_products = edd_get_bundled_products( $item->product_id, $item->price_id );

									foreach ( $bundled_products as $bundle_item ) :
										?>

										<li class="edd_bundled_product">
											<span class="edd_bundled_product_name"><?php echo esc_html( edd_get_bundle_item_title( $bundle_item ) ); ?></span>
											<ul class="edd_bundled_product_files">
												<?php
												$download_files = edd_get_download_files( edd_get_bundle_item_id( $bundle_item ), edd_get_bundle_item_price_id( $bundle_item ) );

												if ( $download_files && is_array( $download_files ) ) :
													foreach ( $download_files as $filekey => $file ) :
														?>
														<li class="edd_download_file">
															<a href="<?php echo esc_url( edd_get_download_file_url( $order->payment_key, $order->email, $filekey, $bundle_item, $item->price_id ) ); ?>" class="edd_download_file_link"><?php echo esc_html( edd_get_file_name( $file ) ); ?></a>
														</li>
														<?php
														do_action( 'edd_receipt_bundle_files', $filekey, $file, $item->product_id, $bundle_item, $order->id, $meta );
													endforeach;
												else :
													echo '<li>' . esc_html__( 'No downloadable files found for this bundled item.', 'easy-digital-downloads' ) . '</li>';
												endif;
												?>
											</ul>
										</li>
										<?php
									endforeach;

								else :
									echo '<li>' . esc_html( apply_filters( 'edd_receipt_no_files_found_text', __( 'No downloadable files found.', 'easy-digital-downloads' ), $item->product_id ) ) . '</li>';
								endif;
								?>
							</ul>
							<?php endif; ?>

							<?php
							// Allow extensions to extend the product cell
							do_action( 'edd_purchase_receipt_after_files', $item->product_id, $order->id, $meta, $item->price_id );
							?>
						</td>
						<?php if ( edd_use_skus() ) : ?>
							<td><?php echo esc_html( edd_get_download_sku( $item->product_id ) ); ?></td>
						<?php endif; ?>
						<?php if ( edd_item_quantities_enabled() ) { ?>
							<td><?php echo esc_html( $item->quantity ); ?></td>
						<?php } ?>
						<td>
							<?php echo esc_html( edd_currency_filter( edd_format_amount( $item->total ) ) ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>

		</table>
	<?php endif; ?>
<?php endif; ?>
