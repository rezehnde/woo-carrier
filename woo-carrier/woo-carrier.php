<?php
/*
  Plugin Name: Woo Carrier
  Plugin URI: http://rezehnde.com/
  Description: Creates and saves a new text input field “Carrier ID” for the shipping methods "Flat-rate" and "Free-shipping".
  Author: Marcos Rezende
  Version: 1.0.0
  Author URI: http://rezehnde.com/
*/

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

  function add_extended_shipping_methods( $shipping_methods ) {
    if ( ! class_exists( 'WC_Shipping_Flat_Rate_Carrier' ) ) {
      include( 'class-wc-shipping-flat-rate-carrier.php' );
      $shipping_methods['flat_rate'] = 'WC_Shipping_Flat_Rate_Carrier';
    }
    if ( ! class_exists( 'WC_Shipping_Free_Shipping_Carrier' ) ) {
      include( 'class-wc-shipping-free-shipping-carrier.php' );
      $shipping_methods['free_shipping'] = 'WC_Shipping_Free_Shipping_Carrier';
    }
    return $shipping_methods;
  }
  add_filter( 'woocommerce_shipping_methods', 'add_extended_shipping_methods' );

  /**
   * Update Carrier ID field inside order
   */
  function woocommerce_processing_changed( $order_id, $from, $to, $instance ){

    if ( $to == 'processing' ) {

      $order = wc_get_order( $order_id );

      if ( $order->has_shipping_method( 'flat_rate' ) || $order->has_shipping_method( 'free_shipping' ) ) { 

        foreach ( $order->get_shipping_methods() as $order_item_shipping ) {
          
          if ( $order_item_shipping->get_method_id() == 'flat_rate' ){
            include( 'class-wc-shipping-flat-rate-carrier.php' );
            $shipping_method = new WC_Shipping_Flat_Rate_Carrier( $order_item_shipping->get_instance_id() );
            $carrier_id = $shipping_method->carrier_id;
            break;
          }
          if ( $order_item_shipping->get_method_id() == 'free_shipping' ){
            include( 'class-wc-shipping-free-shipping-carrier.php' );
            $shipping_method = new WC_Shipping_Free_Shipping_Carrier( $order_item_shipping->get_instance_id() );
            $carrier_id = $shipping_method->carrier_id;
            break;
          }
          
        }

        if ( $carrier_id ) {
          $order->update_meta_data( '_carrier_id', $carrier_id );
          $order->save();
        }

      }
    }
  }
  add_action( 'woocommerce_order_status_changed', 'woocommerce_processing_changed', 10, 4 );

  /**
   * Add the Carrier ID column
   */
  function edit_shop_order_columns($columns)
  {
    $columns['carrier_id'] = 'Carrier ID';
    return $columns;
  }
  add_filter( 'manage_edit-shop_order_columns', 'edit_shop_order_columns', 20 );

  /**
   * Show the Carrier ID column content
   */
  function shop_order_posts_custom_column( $column, $post_id )
  {
    if ( $column == 'carrier_id' ) {
      echo get_post_meta( $post_id, '_carrier_id', true );  
    }
  }  
  add_action( 'manage_shop_order_posts_custom_column' , 'shop_order_posts_custom_column', 20, 2 );

}