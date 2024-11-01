<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
* Base Class For Common var & functions
*/
class Woo_Stock_Base {
		
	public function __construct() {
		//change stock status based on product
		add_filter( 'woocommerce_get_availability', array( $this,'woo_rename_stock_status' ) , 10 , 2);
		add_filter( 'woocommerce_get_bundled_item_availability', array( $this, 'woo_bundled_rename_stock_status' ), 5, 3 );
		add_shortcode('wcss_learn_more', array($this,'learn_more_shortcode'));
		add_shortcode('wcss_delivery_date', array($this,'delivery_date_shortcode'));
	}

	/**
	 * Default stock status with delivery date
	 */
	public function delivery_date_shortcode($atts){

		$days = (isset($atts['days'])) ? abs(intval($atts['days'])) : 0; 

		$excluded_days_str = isset($atts['excluded_days']) && !empty($atts['excluded_days']) ? $atts['excluded_days'] : '';
		
		$tmp_excluded_days = explode(",", $excluded_days_str);
    
        $excluded_days = array();
        foreach ($tmp_excluded_days as $day) {
            $_day = substr(trim(strtolower($day)),0,3);
            if(!empty($_day)) {
                $excluded_days[] = $_day;
            }
        }
 
        $current_date = current_datetime();
        $target_date = $current_date; 
        $days_counted = 0;
        while ($days_counted < $days) {
            $target_date = $target_date->modify('+1 day');  // Increment the date
            $day_of_week = $target_date->format('l');
            $short_day = strtolower(substr($day_of_week, 0, 3)); 
            // Check if the day is excluded
            if (!in_array($short_day, $excluded_days)) {
              $days_counted++;
            }
         }
        
         return $target_date->format('jS F');  // Return formatted date
	}

	/**
	 * Default stock status and its names (array format key as meta_key value as Label)
	 */
	public function learn_more_shortcode($atts, $content = null){
	    $url =  isset($atts['url']) && !empty($atts['url']) ? esc_url($atts['url']) : "";
	    $text = isset($atts['text']) && !empty($atts['text']) ? esc_html($atts['text']) : "Learn More";
	    $output = '<a href="' . $url . '" title="' . $text . '">' . $text . '</a>';
	    return $output;
	}

	public function woo_bundled_rename_stock_status( $message_array, $this_obj, $product_obj ) {
		return $this->woo_rename_stock_status( $message_array ,  $product_obj );
	}

	/**
	 * Default stock status and its names (array format key as meta_key value as Label)
	 */
	public $status_array = array(
							'in_stock' 				=> 'In stock',
							'only_s_left_in_stock' 	=> 'Only %s left in stock',
							'can_be_backordered' 	=> '(can be backordered)',
							's_in_stock'			=> '%s in stock',
							'available_on_backorder'=> 'Available on backorder',
							'out_of_stock' 			=> 'Out of stock',
						);

	/**
	 * Default stock status colors
	 */
	public $status_color_array = array(
							'in_stock_color' 				=> array('default'=> '#77a464', 'label' => 'In stock color'),
							'only_s_left_in_stock_color' 	=> array('default'=> '#77a464', 'label' => 'Only %s left in stock color'),
							's_in_stock_color'				=> array('default'=> '#77a464', 'label' => '%s in stock color'),
							'available_on_backorder_color'	=> array('default'=> '#77a464', 'label' => 'Available on backorder color'),
							'can_be_backordered_color' 		=> array('default'=> '#77a464', 'label' => '(can be backordered) color'),
							'out_of_stock_color' 			=> array('default'=> '#ff0000', 'label' => 'Out of stock color'),
							'grouped_product_stock_status_color' => array('default'=> '#77a464', 'label' => 'Grouped product stock status color'),
						);
	

	/**
	 * Default stock status font size
	 */
	public $status_font_size_array = array(
							'in_stock_font_size' 				=> array('default'=> 'inherit', 'label' => 'In stock font size'),
							'only_s_left_in_stock_font_size' 	=> array('default'=> 'inherit', 'label' => 'Only %s left in stock font size'),
							's_in_stock_font_size'				=> array('default'=> 'inherit', 'label' => '%s in stock font size'),
							'available_on_backorder_font_size'	=> array('default'=> 'inherit', 'label' => 'Available on backorder font size'),
							'can_be_backordered_font_size' 		=> array('default'=> 'inherit', 'label' => '(can be backordered) font size'),
							'out_of_stock_font_size' 			=> array('default'=> 'inherit', 'label' => 'Out of stock font size'),
							'grouped_product_stock_status_font_size' => array('default'=> 'inherit', 'label' => 'Grouped product stock status font size'),
						);

	/*
	 * Rename the default stock list names based on Product
	 */
	public function woo_rename_stock_status( $message_array ,  $this_obj ) {
		
		$availability = $class = '';

		foreach($this->status_array as $status=>$label){
			if ( function_exists( 'pll_translate_string' ) ) {
				$current_language = pll_current_language(); 
				$label = pll_translate_string($label, $current_language);
			}
			$$status = $label;
		}
		
		/**
		 * Change Stock Status Based on Product wise. now, we used simple & variation product types only 
		 * if product doesn't have stock status we use category stock status (Products->Category) tab
		 * if product doesn't have category status we use global stock status (Settings->Custom Stock) tab
		 */
		foreach( $this->status_array as $status=>$label ) {
			 if( is_plugin_active( 'woo-custom-stock-status-pro/woo-custom-stock-status-pro.php' ) ) {
				$terms = $this_obj->get_category_ids();
				foreach ( $terms as $term ) {
					 $cat_id = $term;
					 $cat_stock_status = get_term_meta( $cat_id , '_'.$status.'_status' , true );
					 if( !empty( $cat_stock_status ) ){
						$cat_stock_status = get_term_meta( $cat_id , '_'.$status.'_status' , true );
						break;
					 }
				}
			 } else {
				 $cat_stock_status=null;
			 }
			if( $this_obj->is_type( 'simple' ) || $this_obj->is_type( 'composite' ) || $this_obj->is_type('bundle') ) {
				$stock_status = get_post_meta( $this_obj->get_id() , $status , true );
				if( !empty( $stock_status ) ) {
					$$status = get_post_meta( $this_obj->get_id() , $status , true );
				} elseif( !empty( $cat_stock_status ) ) {
					$$status = get_term_meta( $cat_id , '_'.$status.'_status' , true );
				} else {
					$$status = (get_option('wc_slr_'.$status,$$status)=='') ? $$status : get_option('wc_slr_'.$status,$$status);
				}
			} elseif( $this_obj->is_type( 'variable' ) ){
				$variations = $this_obj->get_children();
				$same_value	= false;
				$once_assign= false;
				//BOF sfnd-category-page-changes
				//get category page stock status key words
				$fastest_stock_status = get_option('wc_slr_show_fastest_stock_status_category_page');
				if($fastest_stock_status != ""){
					$fastest_stock_status_array = explode(',', $fastest_stock_status);
				}
				$cat_status = array(); 
				//EOF sfnd-category-page-changes
				foreach( $variations as $variation_product ) {
					$temp_status		= get_post_meta( $variation_product , '_' . $status . '_status' , true );
					//BOF sfnd-category-page-changes
					//Get matched stock status and assign to $cat_status array
					if(isset($fastest_stock_status_array) && is_array($fastest_stock_status_array) && !empty($fastest_stock_status_array)){ 
						foreach ($fastest_stock_status_array as $key => $fastest) {
							//check variation stock status contains the key word
						 	$text_contain       = str_contains($temp_status, trim($fastest));
						 	if($text_contain == 1 ){  
						 		$numberPattern = '/(\d+)/'; //status with number
						 		$datePattern = '/\b(\d{1,2}-\d{1,2}-\d{4})\b/'; // status with date
							 	if (preg_match($datePattern, $temp_status, $matches)) {
							 		$cat_status[$fastest][] = $temp_status;
							 	}else if (preg_match($numberPattern, $temp_status, $matches) && preg_match($numberPattern, $fastest, $matches1)) {
								    $extracted_number = $matches[0];
								    $given_number = $matches1[0];
								    if($extracted_number == $given_number){
								    	$cat_status[$fastest][] = $temp_status;
								    }
								} else{
									$cat_status[$fastest][] = $temp_status;
								}
						 	}
						}
					}
					//EOF sfnd-category-page-changes
					if( false === $once_assign ) {
						$temp		= get_post_meta( $variation_product , '_' . $status . '_status' , true );
						$once_assign= true;
					} else {
						$same_value	= (get_post_meta( $variation_product , '_' . $status . '_status' , true ) == $temp) ? true : false;
						if( true != $same_value ) {
							//break;
						}
					}
				} 
				//BOF sfnd-category-page-changes
				if(!empty($cat_status)){  //check if $cat_status not empty
					if(isset($fastest_stock_status_array) && is_array($fastest_stock_status_array) && !empty($fastest_stock_status_array)){ 
						foreach ($fastest_stock_status_array as $key => $fastest) {
						 	if(isset($cat_status[$fastest])){
						 		if(count($cat_status[$fastest]) > 1){ 
						 		   $lowestDate = null;
						 		   $isSame = count(array_unique($cat_status[$fastest])) === 1;
						 		   if($isSame){ //if all variation products has same stock status
						 		   		$dateStatus = $cat_status[$fastest][0];
						 		   }else{
									   //if variation product has date in stock status then show smallest date
						 		   		foreach ($cat_status[$fastest] as $key => $value) {
							 		   		$datePattern = '/\b(\d{1,2}-\d{1,2}-\d{4})\b/';
										 	if (preg_match($datePattern, $value, $matches)) {
										 		$currentDate = $matches[1];
										        if ($lowestDate === null || $currentDate < $lowestDate) {
										            $lowestDate = $currentDate;
										            $dateStatus = $cat_status[$fastest][$key];
									       		}
										 	}
							 		   }
						 		   }

						 		   if(isset($dateStatus) && !empty($dateStatus)){ //assign stock status to $$status variable
						 		   		$$status = $dateStatus;
						 		   		break;
						 		   }
						 		}else{
						 			$$status = $cat_status[$fastest][0]; //assign stock status to $$status variable
						 			break;
						 		}
						 	}
						}
					}
				//EOF sfnd-category-page-changes	
				}else if( 1 === count( $variations ) ) {
					if( !empty( $temp ) ) {
						$$status = $temp;
					} elseif( !empty( $cat_stock_status ) ) {
						$$status = get_term_meta( $cat_id , '_'.$status.'_status' , true );
					} else {
						$$status = (get_option('wc_slr_' . $status, $$status) == '') ? $$status : get_option('wc_slr_' . $status, $$status);
					}
				} elseif( true == $same_value ) {
					if( !empty( $temp ) ) {
						$$status = $temp;
					} elseif( !empty( $cat_stock_status ) ) {
						$$status = get_term_meta( $cat_id , '_'.$status.'_status' , true );
					} else {
						$$status = (get_option('wc_slr_' . $status, $$status) == '') ? $$status : get_option('wc_slr_' . $status, $$status);
					}
				}elseif( !empty( $cat_stock_status ) ) {
					$$status = get_term_meta( $cat_id , '_'.$status.'_status' , true );
				} else {
					$$status = (get_option('wc_slr_' . $status,$$status) == '') ? $$status : get_option('wc_slr_' . $status, $$status);
				}	
			} elseif ( $this_obj->is_type( 'variation' ) ) {
				
				$stock_status = get_post_meta( $this_obj->get_id() , '_'.$status.'_status' , true );
				if( !empty( $stock_status ) ) {
					$$status = get_post_meta( $this_obj->get_id() , '_'.$status.'_status' , true );
				} elseif( !empty( $cat_stock_status ) ) {
					$$status = get_term_meta( $cat_id , '_'.$status.'_status' , true );
				} else {
					$$status = (get_option('wc_slr_'.$status,$$status)=='') ? $$status : get_option('wc_slr_'.$status,$$status);
				}	
			}elseif( $this_obj->is_type( 'grouped' ) ){
				$stock_status = get_post_meta( $this_obj->get_id() , 'grouped_product_stock_status' , true );
				$grouped_product_stock_status = true;
				$grouped_product_status = get_option('wc_slr_grouped_product_stock_status_for_category_page');

				if( !empty( $stock_status ) ) {
					$$status = get_post_meta( $this_obj->get_id() , 'grouped_product_stock_status' , true );
				} elseif( !empty( $grouped_product_status ) ) {
					$$status = $grouped_product_status;
				} else {
					$$status = (get_option('wc_slr_'.$status,$$status)=='') ? $$status : get_option('wc_slr_'.$status,$$status);
				}
			} else {
				$$status = (get_option('wc_slr_'.$status,$$status)=='') ? $$status : get_option('wc_slr_'.$status,$$status);
			} 
      
			if (strpos($$status, '[wcss_learn_more') !== false) {
			    $$status = do_shortcode($$status);
			} 

			if (strpos($$status, '[wcss_delivery_date') !== false ) {
			    $$status = do_shortcode($$status);
			}

		}
		
		if ( $this_obj->managing_stock() ) {

			if ( $this_obj->is_in_stock() && $this_obj->get_stock_quantity() > get_option( 'woocommerce_notify_no_stock_amount' ) ) {

				switch ( get_option( 'woocommerce_stock_format' ) ) {

					case 'no_amount' :
						$availability = __( $in_stock, 'woocommerce' );
						$extra_class  = 'in_stock_color';
					break;

					case 'low_amount' :
						if ( $this_obj->get_stock_quantity() <= wc_get_low_stock_amount( $this_obj ) ) {
							$availability = sprintf( __( $only_s_left_in_stock, 'woocommerce' ), $this_obj->get_stock_quantity() );

							$extra_class  = 'only_s_left_in_stock_color';

							if ( $this_obj->backorders_allowed() && $this_obj->backorders_require_notification() ) {
								$availability .= ' ' . __( $can_be_backordered, 'woocommerce' );
								$extra_class  .= ' can_be_backordered_color';
							}
						} else {
							$availability = __( $in_stock, 'woocommerce' );
							$extra_class  = 'in_stock_color';
						}
					break;

					default :
						$availability = sprintf( __( $s_in_stock, 'woocommerce' ), $this_obj->get_stock_quantity() );

						$extra_class  = 's_in_stock_color';

						if ( $this_obj->backorders_allowed() && $this_obj->backorders_require_notification() ) {
							$availability .= ' ' . __( $can_be_backordered, 'woocommerce' );
							$extra_class  .= ' can_be_backordered_color';
						}
					break;
				}

				$class        = 'in-stock ';

				$class		 .= $extra_class;

			} elseif ( $this_obj->backorders_allowed() && $this_obj->backorders_require_notification() ) {

				$availability = __( $available_on_backorder, 'woocommerce' );
				$class        = 'available-on-backorder available_on_backorder_color';

			} elseif ( $this_obj->backorders_allowed() ) {

				$availability = __( $in_stock, 'woocommerce' );
				$class        = 'in-stock in_stock_color';

			} else {

				$availability = __( $out_of_stock, 'woocommerce' );
				$class        = 'out-of-stock out_of_stock_color';
			}

		} else {
			
			$stock_status_no_inventory = $this_obj->get_stock_status();
			if($stock_status_no_inventory=='instock') {
				$availability = __( $in_stock, 'woocommerce' );
				$class        = 'in-stock in_stock_color';
				if(isset($grouped_product_stock_status) && $grouped_product_stock_status === true){
					$availability = __( $in_stock, 'woocommerce' );
				    $class        = 'in-stock grouped_product_stock_status_color';
				}

			} elseif($stock_status_no_inventory=='outofstock') {

				$availability = __( $out_of_stock, 'woocommerce' );
				$class        = 'out-of-stock out_of_stock_color';
				
			} elseif($stock_status_no_inventory=='onbackorder') {

				$availability = __( $available_on_backorder, 'woocommerce' );
				$class        = 'available-on-backorder available_on_backorder_color';

			}

		}

		if( is_plugin_active( 'woo-custom-stock-status-pro/woo-custom-stock-status-pro.php' ) ){
			$hide_status = get_option( 'wc_slr_hide_variation_status_category_page' );
			if( $hide_status === 'yes' && is_product_category()) {

				if ($this_obj->is_type('variable')) {
					$availability = "";
					$$status = "";
					$class = "";
				}
			}
		}

		$message_array['availability'] = $availability;
		$message_array['class'] = $class.' woo-custom-stock-status';
		
		//hide stock status on product
		$message_array = $this->hide_stock_status($message_array,$this_obj);
		
		return apply_filters( 'wcss_woo_custom_stock_status', $message_array , $this_obj);
	}

	/**
	 * Hide stock status on individual product
	 */
	public function hide_stock_status($message_array,$this_obj){
		$product_id = $this_obj->get_id();
		if( $this_obj->is_type( 'simple' ) || $this_obj->is_type( 'composite' ) || $this_obj->is_type('bundle') || $this_obj->is_type( 'grouped' ) || $this_obj->is_type( 'variable' )) { 
			$hide_status = get_post_meta($product_id,'hide_stock_status',true);
			if ($hide_status == 'yes') {
			    $message_array = array('availability'=>'','class'  => '');
		    }
		} elseif( $this_obj->is_type( 'variation' ) ){ 
			$variation_id = $this_obj->get_parent_id();
			$hide_status = get_post_meta($variation_id,'hide_stock_status',true);
			if ($hide_status == 'yes') {
				$message_array = array('availability'=>'','class'  => '');
			}
		}

		return $message_array;
	}
}
