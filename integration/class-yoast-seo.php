<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
* Update custom stock status text on yoast seo schema and meta tag's.
*/
class Woo_Stock_With_Yoast_Seo {
		
	public function __construct() {
		/**
		 * Modifing default stock status to custom stock status on yoast seo schema
		 **/
		add_filter( 'wpseo_schema_product', array($this,'change_schema_stock_status'));

		add_filter('wpseo_frontend_presenters', array($this, 'remove_availability_presenter_meta_tag' ), 20,1);
	}
	
	/**
     * Add meta tag with custom stock status on yoast seo schema
     */
	public function add_availability_presenter_meta_tag(){
		global $product; 
		$message_array = array();
		$message_array['availability'] = '';
		$message_array['class'] = '';
		$woo_stock_obj = new Woo_Stock_Base();
		$stock_status_array = $woo_stock_obj->woo_rename_stock_status($message_array ,  $product);
		if(isset($stock_status_array['availability']) && !empty($stock_status_array['availability'])){
			$availability = $stock_status_array['availability'];
			echo "\t" . '<meta property="og:availability" content="'.str_replace('"', "'", $availability).'" class="yoast-seo-meta-tag" />' . \PHP_EOL;
			echo "\t" . '<meta property="product:availability" content="'.str_replace('"', "'", $availability).'" class="yoast-seo-meta-tag" />' . \PHP_EOL;
		}
	}

	/**
     * Remove meta tag with default stock status on yoast seo schema
     */
	public function remove_availability_presenter_meta_tag($presenters){ 
		if (is_product()) { 
			$availability  = 0;
		    foreach ($presenters as $key => $object) {
		    	if (
					is_a( $object, 'WPSEO_WooCommerce_Pinterest_Product_Availability_Presenter' )
					|| is_a( $object, 'WPSEO_WooCommerce_Product_Availability_Presenter' )
				) {
					unset( $presenters[ $key ] );
					$availability++;
				}	
		    }

		    if($availability > 0 && !defined('UPDATE_META_TAG_YOAST_SEO')){
		    	add_action( 'wpseo_head', array( $this, 'add_availability_presenter_meta_tag' ), 10);
		    	define( 'UPDATE_META_TAG_YOAST_SEO', true);
		    }	   
		}
		return $presenters;
	}

	/**
     * Update custom stock status on yoast seo schema
     */
	public function change_schema_stock_status( $data ){ 
		if(isset($data['@type']) && (is_array($data['@type']) && in_array('Product', $data['@type'])) || $data['@type'] == 'Product'){
			$offers = $data['offers'];
			foreach ($offers as $key => $value) { 
				if(isset($value['@type']) && ( $value['@type'] == 'Offer' || $value['@type'] == 'AggregateOffer') ){
					global $product;
					$message_array = array();
					$message_array['availability'] = '';
					$message_array['class'] = '';
					$woo_stock_obj = new Woo_Stock_Base();
					$stock_status_array = $woo_stock_obj->woo_rename_stock_status($message_array ,  $product);
					if(isset($stock_status_array['availability']) && !empty($stock_status_array['availability']) && isset($value['availability'])){
						$value['availability'] = $stock_status_array['availability'];
					}
				}

				$offers[$key] = $value;
			} 
			$data['offers'] = $offers;
		}
		return $data;
	}
}
