<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
* Display custom stock status on yith wishlist page
*/
class Woo_Stock_With_Yith_Wishlist {
		
	public function __construct() {
		add_filter( 'yith_wcwl_stock_status', array($this,'add_custom_stock_status_on_wishlist_page'),10,3 );
	}
	
	/**
	 * Change default stock status to custom stock status on yith wishlist page.
	 */
	public function add_custom_stock_status_on_wishlist_page($stock_status_html, $item, $wishlist){
		$message_array = array();
		$message_array['availability'] = $item->get_stock_status();
		$message_array['class'] = 'out-of-stock' === $item->get_stock_status() ? 'wishlist-out-of-stock' : 'wishlist-in-stock';
		$product_obj = $item->get_product();
		$woo_stock_obj = new Woo_Stock_Base();
		$stock_status_array = $woo_stock_obj->woo_rename_stock_status($message_array ,  $product_obj);
		$stock_status_html = '<span class="stock '.$stock_status_array['class'].'" >'.$stock_status_array['availability'].'</span>';
		return $stock_status_html;
	}
}
