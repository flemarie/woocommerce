<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Factory Class
 *
 * The WooCommerce order factory creating the right order objects.
 *
 * @class 		WC_Order_Factory
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooCommerce
 */
class WC_Order_Factory {

	/**
	 * Get order.
	 *
	 * @param bool $the_order (default: false)
	 * @return WC_Order|bool
	 */
	public static function get_order( $the_order = false ) {
		global $post;

		if ( false === $the_order ) {
			$the_order = $post;
		} elseif ( is_numeric( $the_order ) ) {
			$the_order = get_post( $the_order );
		} elseif ( $the_order instanceof WC_Abstract_Order ) {
			$the_order = get_post( $the_order->get_id() );
		}

		if ( ! $the_order || ! is_object( $the_order ) ) {
			return false;
		}

		$order_id  = absint( $the_order->ID );
		$post_type = $the_order->post_type;

		if ( $order_type = wc_get_order_type( $post_type ) ) {
			$classname = $order_type['class_name'];
		} else {
			$classname = false;
		}

		// Filter classname so that the class can be overridden if extended.
		$classname = apply_filters( 'woocommerce_order_class', $classname, $post_type, $order_id, $the_order );

		if ( ! class_exists( $classname ) ) {
			return false;
		}

		return new $classname( $the_order );
	}

	/**
	 * Get order item.
	 * @param int
	 * @return WC_Order_Item|false if not found
	 */
	public static function get_order_item( $item_id = 0 ) {
		global $wpdb;

		if ( is_numeric( $item_id ) ) {
			$item_data = $wpdb->get_row( $wpdb->prepare( "SELECT order_item_type FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d LIMIT 1;", $item_id ) );
			$item_type = $item_data->order_item_type;
			$id        = $item_id;
		} elseif ( $item_id instanceof WC_Order_Item ) {
			$item_type = $item_id->get_type();
			$id        = $item_id->get_id();
		} elseif ( is_object( $item_id ) && ! empty( $item_id->order_item_type ) ) {
			$id        = $item_id;
			$item_type = $item_id->order_item_type;
		} else {
			$item_data = false;
			$item_type = false;
			$id        = false;
		}

		if ( $id && $item_type ) {
			$classname = false;
			switch ( $item_type ) {
				case 'line_item' :
				case 'product' :
					$classname = 'WC_Order_Item_Product';
				break;
				case 'coupon' :
					$classname = 'WC_Order_Item_Coupon';
				break;
				case 'fee' :
					$classname = 'WC_Order_Item_Fee';
				break;
				case 'shipping' :
					$classname = 'WC_Order_Item_Shipping';
				break;
				case 'tax' :
					$classname = 'WC_Order_Item_Tax';
				break;
			}
			if ( $classname ) {
				try {
					return new $classname( $id );
				} catch ( Exception $e ) {
					return false;
				}
			}
		}
		return false;
	}
}
