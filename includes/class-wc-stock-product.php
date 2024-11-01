<?php
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
* WC stock status for Products if product stock status are empty they get global stock status ( Setting tab Status )
*/

class Woo_Stock_Product extends Woo_Stock_Base {
	
	public function __construct() {
		
		// add stock status tab to product tab
		add_filter( 'woocommerce_product_data_tabs', array( $this , 'woo_add_simple_product_stock_status' ) );

		// display stock status fields for ( Simple,Grouped,External ) Products
		add_action( 'woocommerce_product_data_panels' , array( $this , 'woo_stock_status_fields' ) );

		// save stock fields value for ( Simple ) Product
		add_action( 'woocommerce_process_product_meta_simple' , array( $this , 'save_stock_status_message' ) );
		
		// save stock fields value for ( Bundle ) Product
		add_action( 'woocommerce_process_product_meta_bundle' , array( $this , 'save_stock_status_message' ) );

		// save stock fields value for ( Composite ) Product
		add_action( 'woocommerce_process_product_meta_composite' , array( $this , 'save_stock_status_message' ) );
		
		//check current theme and add action based on theme
		$current_theme = wp_get_theme();
		$theme_name = strtolower($current_theme->get( 'Name' ));
		$template_name = strtolower($current_theme->get( 'Template' ));

		if($theme_name == 'oceanwp' || $template_name == 'oceanwp' || strpos($theme_name, 'oceanwp') !== false || strpos($template_name, 'oceanwp') !== false){			

			$wc_slr_stock_status_before_price = get_option( 'wc_slr_stock_status_before_price', 'no' );
	 		if($wc_slr_stock_status_before_price=='yes'){
	 			// add stock status message in owp-archive-product theme page
				add_action( 'ocean_before_archive_product_price' , array( $this , 'add_stack_status_in_summary' ) , 15 );// before price themes\oceanwp\woocommerce\owp-archive-product.php line:111
	 		}else{
	 			// add stock status message in owp-archive-product theme page
				add_action( 'ocean_after_archive_product_inner' , array( $this , 'add_stack_status_in_summary' ) , 15 );// after price themes\oceanwp\woocommerce\owp-archive-product.php line:120
	 		}
		}else{
			// add stock status message in before/after price
			add_action( 'woocommerce_get_price_html' , array( $this , 'add_stack_status_before_after_price' ) , 99999,2 ); 

			add_action( 'woocommerce_after_shop_loop_item_title' , array( $this , 'add_stack_status_in_product_detail' ) , 15 ); 
			//Display stock status when b2bking plugin is activated
			if( is_plugin_active( 'b2bking/b2bking.php' ) ) {
				add_filter('b2bking_hide_price_product_text', array($this,'display_custom_stock_status_on_b2bking'),9999,3);
			}
		}	

		/**
		 * Hide save stock fields value for External Products
		 */
		
		 add_action( 'woocommerce_process_product_meta_grouped' , array( $this , 'save_stock_status_message' ) );
		// add_action( 'woocommerce_process_product_meta_external' , array( $this , 'save_stock_status_message' ) );

		// variration stock status field
		add_action( 'woocommerce_variation_options_inventory' , array( $this , 'woo_variation_stock_status_field' ) , 10 , 3 ); 

		//save variation stock status
		add_action( 'woocommerce_save_product_variation' , array( $this , 'save_variation_stock_status' ) , 10 , 2 );

		//backorder woo custom stock status in order confirmation
		add_action('woocommerce_order_item_meta_start',array($this,'add_stock_status_in_order_confirmation'),10,3);

		add_filter( 'woocommerce_order_item_get_formatted_meta_data', array($this, 'format_backend_stock_status_label'), 10, 2 );

		/**
		 * Display custom stock status on shortcode based cart page
		 * @since 1.5.3
		 */
		add_filter( 'woocommerce_cart_item_name', array($this, 'cart_page_stock_status'), 10, 3);
	    //add_filter( 'woocommerce_blocks_cart_item_name', array($this, 'cart_page_stock_status'), 10, 3);
		
		/**
		 * Display custom stock status on shortcode based checkout page
		 * @since 1.5.3
		 */
		add_filter( 'woocommerce_checkout_cart_item_quantity', array($this, 'checkout_page_stock_status'), 10, 3);

		//add_order_item_meta
		add_action( 'woocommerce_new_order_item', array( $this, 'update_stock_status_to_order_item_meta' ), 10, 3 );

		add_shortcode( 'woo_custom_stock_status', array( $this, 'woo_custom_stock_status_func' ) );

		add_filter( 'woocommerce_cart_item_backorder_notification', array( $this, 'hide_cart_default_backorder_notification' ), 10);

		add_filter( 'woocommerce_blocks_product_grid_item_html', array( $this, 'woocommerce_blocks_product_grid_stock_status' ), 10, 3 );

		/**
		 * Add custom stock status on cart and checkout page
		 * @since 1.5.3
		 */
		add_action( 'woocommerce_add_cart_item', array($this, 'add_stock_status_as_cart_item_data'), 10, 2 );

		/**
		 * Removed this hook to avoid duplication
		 * @since 1.5.3
		 */
		//add_filter( 'woocommerce_get_item_data', array($this , 'display_stock_status_on_cart'), 10, 2 );

		add_filter( 'woocommerce_attribute_label', array($this , 'rename_order_meta_key_on_invoice'), 10, 2 );

		//Add option to hide stock status for individual product
		add_action( 'woocommerce_product_options_stock_status', array($this,'add_hide_option_in_product_inventry'));
		add_action('woocommerce_process_product_meta', array($this, 'save_custom_inventory_option'));
    
        //Hide the stock status entry on the invoice PDF. 
		add_filter( 'bewpi_hidden_order_itemmeta', array($this ,'hide_order_meta_data') );
		add_filter( 'wpi_item_description_data', array($this ,'hide_order_meta_data_in_decription'),20,3 );

		add_filter( 'render_block', array($this, 'add_product_id_to_collection_block'), 10, 2 );

		add_action('woocommerce_blocks_loaded', array($this,'register_custom_store_api_data'));

		add_action('wp_enqueue_scripts', array($this,'enqueue_custom_cart_script'));

		add_action('wp_footer',array($this,'custom_script'));

		add_filter('woocommerce_get_stock_html' , array($this,'prevent_stock_html_duplication'),99,2);

	}

	/**
	* Cleared default stock status HTML to prevent duplication on the single product page
	* @since 1.5.6
	*/
	public function prevent_stock_html_duplication($html,$product){
		if ( is_plugin_active( 'b2bking-private-store-for-woocommerce/b2bking.php' ) && (get_option('b2bking_plugin_status_setting', 'disabled') !== 'disabled') &&
			(get_option('b2bking_guest_access_restriction_setting', 'hide_prices') === 'hide_prices')){
			if(is_product() && !is_user_logged_in()){
				global $product;
				if($product->is_type('variable') || $product->is_type('variation')){
					$html = '';
				}
			}
		}
		return $html;
	}


	/**
	 * Display custom stock status when B2bking pro plugin is activated
	 */
	public function display_custom_stock_status_on_b2bking($pricetext, $product, $price){
		if( !is_user_logged_in() || (intval(get_option( 'b2bking_multisite_separate_b2bb2c_setting', 0 )) === 1 && get_user_meta(get_current_user_id(), 'b2bking_b2buser', true) !== 'yes')){ 
			if(is_product()){
				return $pricetext;
			}
			$pricetext .= $this->get_custom_stock_status($product);
		}
		return $pricetext;
	}

	/**
	 * Custom script to rearrage the stock status on the correct position
	 */
	public function custom_script(){ 
		if (is_cart() || is_checkout()) {
		?>
			<script>
				jQuery( document ).ready(function() {
				   setTimeout(function() {
				   		show_cart_checkout_stock_status();
				   }, 2000);
				});
				
				function show_cart_checkout_stock_status(){
					jQuery('.wc-block-cart-item__product').each(function() {
			            var $product = jQuery(this);
			            var $stockStatus = $product.find('.woo-custom-stock-status');
			           console.log($product.find('.wc-block-components-product-metadata'));
			            if ($stockStatus.length > 0) {
			               $stockStatus.detach();
			               $stockStatus.insertBefore($product.find('.wc-block-cart-item__quantity'));
			            }
			        });

			        jQuery('.wc-block-components-order-summary-item__description').each(function() { 
			            var $product = jQuery(this);
			            var $stockStatus = $product.find('.woo-custom-stock-status');
	                    console.log($product.find('.wc-block-components-product-metadata'));

			            if ($stockStatus.length > 0) {
			                $stockStatus.detach();
			            	jQuery($stockStatus).insertAfter($product.find('.wc-block-components-product-metadata'));
			            }
			        });
			    }   
			</script>
			<style type="text/css">
				.wc-block-components-product-name .stock{
					display: none;
				}
				.stock{
					display: none;
				}
			</style>
 			<?php
	 		foreach($this->status_array as $status=>$label){
	 			${$status} = get_option( 'wc_slr_show_'.$status.'_in_cart_checkout' , 'yes' );	 
	 			if (${$status} == 'yes') {
			    ?>
			    <style type="text/css"> .stock.<?php echo esc_attr($status).'_color'; ?> { display: block; }.wc-block-components-product-name .stock{ display: none; }</style>
			    <?php
			    }
	 		}
 		}
	}

	/**
	 * Enqueue js file to add custom stock status on block based cart and checkout page
	 */
	public function enqueue_custom_cart_script() { 
	    if (is_cart() || is_checkout()) {
	    	$script_path = WCSS_PLUGIN_PATH.'/assets/js/woo-custom-stock-status.js';
	    	$script_url = WCSS_PLUGIN_URL.'/assets/js/woo-custom-stock-status.js';

	        wp_enqueue_script(
	            'woo-custom-stock-status',
	            $script_url,
	            array('wp-blocks', 'wp-element', 'wp-components', 'wp-data', 'wp-hooks', 'wp-i18n', 'wc-blocks-checkout', 'wc-settings','wp-components', 'wp-element','jquery','wp-plugins' ),
	            filemtime($script_path),
	            true
	        );  
	    }
	}

	/**
	 * Function to add custom stock status to endpoint data. This data will be available on javascript
	 */
	public function register_custom_store_api_data() {
	    if (function_exists('woocommerce_store_api_register_endpoint_data')) {
	        woocommerce_store_api_register_endpoint_data(
				array(
					'endpoint'        => CartItemSchema::IDENTIFIER,
					'namespace'       => 'woo_custom_stock_status',
					'data_callback'   => function( $cart_item ) { 
						$custom_stockstatus = $cart_item['custom_stockstatus']; 
						return array(
							'custom_status' => $custom_stockstatus,
						);
					},
					'schema_callback' => function() {
						return array(
							'properties' => array(
								'custom_status' => array(
					                'type'        => array('string', 'null'),
					                'context'     => array('view', 'edit'),
					                'readonly'    => true,
					            ),
							),
						);
					},
					'schema_type'     => ARRAY_A,
				)
			);
	    }
	}

	/**
	 * Function to add custom stock status on woocommerce product collection block
	 */
	public function add_product_id_to_collection_block( $block_content, $block ) {
	    if ( isset( $block['blockName'] ) && 'woocommerce/related-products' === $block['blockName'] ) {

        // Use regex pattern to find all post IDs from the class attribute of <li> elements
        preg_match_all( '/class="[^"]*wp-block-post post-(\d+)[^"]*"/', $block_content, $matches );

        if ( ! empty( $matches[1] ) ) {
            foreach ( $matches[1] as $post_id ) {
                // Get custom content for each post ID, e.g., stock status
                $custom_content = do_shortcode( '[woo_custom_stock_status product_id="' . $post_id . '"]' );

                // Use DOMDocument to manipulate HTML content
                $dom = new DOMDocument();
                libxml_use_internal_errors( true ); // Suppress warnings for HTML5 tags
                $dom->loadHTML( mb_convert_encoding( $block_content, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
                libxml_clear_errors();

                $xpath = new DOMXPath( $dom );

                // Locate the target product's price block
                $query = '//li[contains(concat(" ", normalize-space(@class), " "), " wp-block-post post-' . $post_id . ' ")]//div[@data-block-name="woocommerce/product-price"]';
                $target_element = $xpath->query( $query )->item(0);

                // Insert the custom content before the price block
                if ( $target_element ) {
                    $custom_node = $dom->createDocumentFragment();
                    $custom_node->appendXML( $custom_content );

                    $target_element->parentNode->insertBefore( $custom_node, $target_element );
                }

                if(is_plugin_active( 'b2bking/b2bking.php' )){
					$hidestock = get_option( 'b2bking_hide_stock_for_b2c_setting', 'disabled' );
					$is_b2b_user = get_user_meta(get_current_user_id(),'b2bking_b2buser', true);
						
					if (is_user_logged_in() && $is_b2b_user !== 'yes'&& $hidestock === 'hidecompletely'){
							return $block_content;
					}
				}

                // Save the updated HTML
                $block_content = $dom->saveHTML();
            }
        }
    }
    return $block_content;
	}

	/**
	 * Function to save hide stock status option value on postmeta
	 */
	public function save_custom_inventory_option($post_id){ 
		$checkbox = isset($_POST['hide_stock_status']) ? 'yes' : 'no'; 
    	update_post_meta($post_id, 'hide_stock_status', $checkbox);
	}

	/**
	 * Function to add hide stock status option on inventry tab
	 */
	public function add_hide_option_in_product_inventry(){
		 global $post;

    	$hide_stock_status = get_post_meta($post->ID, 'hide_stock_status', true);

		woocommerce_wp_checkbox(array(
			        'id' => 'hide_stock_status',
			        'label' => __('Hide stock status', 'woo-custom-stock-status'),
			        'description' => __('Hide stock status message on frontend', 'woo-custom-stock-status'),
			        'value'  => $hide_stock_status == 'yes' ? 'yes' : 'no' 
			    ));
	}

	/**
	 * Hide the stock status entry on the invoice PDF within the micro template.
	 * This function will only be supported by the "PDF Invoices & Packing Slips for WooCommerce" plugin.
	 */
	public function hide_order_meta_data( $keys ) { 
		$hide_status_invoice = get_option( 'wc_slr_hide_in_woocommerce_invoice' , 'no' ); 

		if($hide_status_invoice == 'yes'){
			$keys[] = '_woo_custom_stock_status_email_txt';
		}
		
	    return $keys;
	}

	/**
	 * Hide the stock status entry on the invoice PDF within the minimal template.
	 * This function will only be supported by the "PDF Invoices & Packing Slips for WooCommerce" plugin.
	 */
	public function hide_order_meta_data_in_decription($description, $item_id, $item){
		if ( $item instanceof WC_Order_Item_Product ) {

	        $meta_data = $item->get_meta_data();

	        foreach ( $meta_data as $meta ) {
	        	$hide_status_invoice = get_option( 'wc_slr_hide_in_woocommerce_invoice' , 'no' ); 
	            if ( $meta->key === '_woo_custom_stock_status_email_txt' && $hide_status_invoice == 'yes') {
	                $stock_status = $meta->value;
	
	                $description = str_replace( $stock_status, '', $description );
	                $description = str_replace( "<strong class='woo-custom-stock-status stock'>".__( 'Stock Status', 'woo-custom-stock-status' ).':</strong> ', '', $description );
	                break; 
	            }
	        }
	    }

   	 	return $description;
	}

	/**
	 * Function to rename "_woo_custom_stock_status_email_txt" to "Stock Status" in invoice
	 */
	public function rename_order_meta_key_on_invoice( $label, $name ){
		if($name == '_woo_custom_stock_status_email_txt'){
			$label = 'Stock Status';
		}
		return $label;
	}

	/**
	 * Function to add custom stock status in cart item data
	 */
	public function add_stock_status_as_cart_item_data($cart_item_data, $cart_item_key) {

		global $woocommerce;
		
		$availability_html = '';

		$product_id = $cart_item_data['product_id'];
		$variation_id = $cart_item_data['variation_id'];

		if( $variation_id>0 ) {

			$variation 				= 	new WC_Product_Variation( $variation_id );
			$product_availabilty 	= 	$variation->get_availability();

		} elseif( $variation_id==0 ) {

			$product 				= 	new WC_Product( $product_id );
			$product_availabilty 	= 	$product->get_availability();

		} 

		if (strpos($product_availabilty['availability'], '[wcss_learn_more') !== false) {
			$product_availabilty['availability'] = do_shortcode($product_availabilty['availability']);
		}

		if (strpos($product_availabilty['availability'], '[wcss_delivery_date') !== false) {
			$product_availabilty['availability'] = do_shortcode($product_availabilty['availability']);
		}

		$availability_html = empty( $product_availabilty['availability'] ) ? '' : '<p class="stock ' . esc_attr( $product_availabilty['class'] ) . ' woocss_shortcode">' . __(esc_html( $product_availabilty['availability'] ),'woo-custom-stock-status') . '</p>';

	    $cart_item_data['custom_stockstatus'] = $availability_html;

	    return $cart_item_data;
	}

	/**
	 * Function to display custom stock status in cart and checkout page
	 */
	public function display_stock_status_on_cart( $item_data, $cart_item_data ) {

		$show_status = get_option( 'wc_slr_show_in_cart_page' , 'yes' ); 
		$show_status_in_shop_page = get_option( 'wc_slr_show_in_shop_page' , 'yes' );

	    if ( isset( $cart_item_data['custom_stockstatus'] ) && !empty( $cart_item_data['custom_stockstatus'] )) {
	    	if( $show_status == 'yes'){
	    			$item_data[] = array(
			            'key'   =>  __( 'Stock Status' , 'woo-custom-stock-status' ),
			            'value' => html_entity_decode( $cart_item_data['custom_stockstatus'] ),
			        );
			} else {
				if( $show_status_in_shop_page == 'yes' && is_checkout()) {
					$item_data[] = array(
				            'key'   => __( 'Stock Status' , 'woo-custom-stock-status' ),
				            'value' => html_entity_decode( $cart_item_data['custom_stockstatus'] ),
			        );
				}
			}	        
	    }

	    return $item_data;
	}


	//Function to hide the default "Available on backorder" message in /woocommerce/templates/cart/cart.php 
	public function hide_cart_default_backorder_notification($backorder_notification){
		return '';
	}

	//[woo_custom_stock_status product_id="1234"] short code call back function
	public function woo_custom_stock_status_func( $atts ) {
		global $product;
		
		$has_product_obj = false;
		$availability_html = '';

		if ( ( isset($atts['product_id'] ) ) && !empty( $atts['product_id'] ) ) {
			$product = 	new WC_Product( $atts['product_id'] );
			$has_product_obj = true;
		} else if( isset( $product ) ){
			$has_product_obj = true;
		}

		if( $has_product_obj == true ){
			$availability      = $product->get_availability();

			if (strpos($availability['availability'], '[wcss_learn_more') !== false) {
			   $availability['availability'] = do_shortcode($availability['availability']);
			}

			if (strpos($availability['availability'], '[wcss_delivery_date') !== false) {
			   $availability['availability'] = do_shortcode($availability['availability']);
			}

			$availability_html = empty( $availability['availability'] ) ? '' : '<p class="stock ' . esc_attr( $availability['class'] ) . ' woocss_shortcode">' . __( $availability['availability'] ,'woo-custom-stock-status') . '</p>';
		}

		return $availability_html;
	}

	/**
	 * Stock Status add in order_item_meta table
	 * @param POST $posted
	 * 
	 */
	public function update_stock_status_to_order_item_meta( $item_id, $posted, $orderId ) {	

		if (! self::isItemValid($posted))
	    {
	        return;
	    }	
		wc_add_order_item_meta( $item_id, '_woo_custom_stock_status_email_txt', $posted->legacy_values['custom_stockstatus'] );
		 
	}
	
	/**
	 * @param WC_Order_Item_Product|WC_Order_Item_Shipping $item
	 *
	 * @return bool
	*/
	public function isItemValid($item)
	{ 
	    return (
	        $item instanceof WC_Order_Item_Product &&
	        isset($item->legacy_values) &&
	        isset($item->legacy_values['custom_stockstatus']) &&
	        !empty($item->legacy_values['custom_stockstatus'])
	    );
	}

	public function cart_page_stock_status($item_name, $cart_item, $cart_item_key){
		$show_status = get_option( 'wc_slr_show_in_cart_page' , 'yes' ); 
		if( $show_status == 'no' ){
			return $item_name;
		} else {
			if ( !is_checkout() ) {
				return $this->cart_stock_status($item_name, $cart_item, $cart_item_key, true);
			} else {
				return $item_name;
			}
		}
	}

	public function checkout_page_stock_status($item_name, $cart_item, $cart_item_key){
		$show_status_in_cart = get_option( 'wc_slr_show_in_cart_page' , 'yes' ); 
		$show_status_in_shop_page = get_option( 'wc_slr_show_in_shop_page' , 'yes' );
		if($show_status_in_cart === "yes" || $show_status_in_shop_page === "yes") {
			return $this->cart_stock_status($item_name, $cart_item, $cart_item_key, true);
		}
		return $this->cart_stock_status($item_name, $cart_item, $cart_item_key);
	}


	/**
	 * Stock Status name add in cart page and checkout page
	 * @param POST values
	 * @return html
	 */

	public function cart_stock_status($item_name, $cart_item, $cart_item_key, $show_in_cart=false ) {
		$show_status = get_option( 'wc_slr_show_in_shop_page' , 'yes' );
		$product_id  =  $cart_item['product_id'];
		$variation_id = $cart_item['variation_id'];
		global $woocommerce;
		
		$availability_html = '';
		if( $variation_id>0 ) {
		
			$variation 				= 	new WC_Product_Variation( $variation_id );
			$product_availabilty 	= 	$variation->get_availability();
			if(isset($woocommerce->cart->cart_contents[$cart_item_key])){
				$woocommerce->cart->cart_contents[$cart_item_key]['woo_custom_status'] = $product_availabilty['availability'];
			}
			if ( class_exists( 'WooCommerce' ) && is_object( $woocommerce->cart ) ) {
			    $woocommerce->cart->set_session(); 
			}
			
		} elseif( $variation_id==0 ) {
			$product 				= 	new WC_Product( $product_id );
			$product_availabilty 	= 	$product->get_availability();
			if(isset($woocommerce->cart->cart_contents[$cart_item_key])){
				$woocommerce->cart->cart_contents[$cart_item_key]['woo_custom_status'] = $product_availabilty['availability'];
			}
			if ( class_exists( 'WooCommerce' ) && is_object( $woocommerce->cart ) ) {
			    $woocommerce->cart->set_session(); 
			}
		}

		if( $show_status === 'yes' || $show_in_cart === true) {

			if (strpos($product_availabilty['availability'], '[wcss_learn_more') !== false) {
			   $product_availabilty['availability'] = do_shortcode($product_availabilty['availability']);
			}

			if (strpos($product_availabilty['availability'], '[wcss_delivery_date') !== false) {
			   $product_availabilty['availability'] = do_shortcode($product_availabilty['availability']);
			}

			$availability_html      =   empty( $product_availabilty['availability'] ) ? '' : '<p class="stock ' . esc_attr( $product_availabilty['class'] ) . '">' . __($product_availabilty['availability'],'woo-custom-stock-status') . '</p>';
		}
		return $item_name.' <br>'.$availability_html;
	}
	
	//Renames "_woo_custom_stock_status_email_txt" to "Stock Status" in admin order details page
	public function format_backend_stock_status_label($formatted_meta, $this_obj ){
		foreach($formatted_meta as $key => $value){
			if(isset($value->key) && !empty($value->key) && ($value->key=='_woo_custom_stock_status_email_txt')){
				$value->display_key = __( 'Stock Status', 'woo-custom-stock-status' );
				$value->display_value = html_entity_decode($value->value);
			}
			$formatted_meta[$key] = $value;

		}

		return $formatted_meta;
	}

	public function woo_add_simple_product_stock_status( $tabs ) {
		$tabs['stockstatus'] = array(
										'label'  => __( 'Stock Status', 'woo-custom-stock-status' ),
										'target' => 'custom_stock_status_data',
										'class'  => array( 'show_if_simple' ), // depend upon product type to show & hide
									);

		$tabs['grouped_stockstatus'] = array(
										'label'  => __( 'Stock Status', 'woo-custom-stock-status' ),
										'target' => 'grouped_custom_stock_status_data',
										'class'  => array( 'show_if_grouped' ), // depend upon product type to show & hide
									);

		return $tabs;
	}

	public function woo_stock_status_fields() {
		echo '<div id="custom_stock_status_data" class="panel woocommerce_options_panel" style="display: flex;">';
			echo '<div style="width: 75%;">';
			foreach ($this->status_array as $key => $value) {
				woocommerce_wp_text_input(
											array( 
													'id' => $key, 
													'label' => __( $value , 'woo-custom-stock-status' ),
													'placeholder' => $value,
													'style' => 'width: 100%;',
												)
										);
			}
			echo '</div>';
		if( ! is_plugin_active( 'woo-custom-stock-status-pro/woo-custom-stock-status-pro.php' ) ){
			echo '<div style="background: #e4efff;border: #bdd9fe solid 4px;width: 25%;font-size: 17px;padding: 23px;">';
			echo '<a href="https://softound.com/products/woo-custom-stock-status-pro/" target="_blank" style="font-weight: bold;">Get Woo Custom Stock Status Pro</a> to edit stock status using <strong>bulk edit option</strong> with <strong>WPML</strong> compatibility and custom stock status for <strong>Product Categories</strong>.';
			echo '</div>';
		}
		echo '</div>';

		echo '<div id="grouped_custom_stock_status_data" class="panel woocommerce_options_panel" style="display: flex;">';
			echo '<div style="width: 75%;">';
			woocommerce_wp_text_input(
				array( 
					'id' => 'grouped_product_stock_status', 
					'label' => __( 'Stock status for category page ' , 'woo-custom-stock-status' ),
					'placeholder' => 'Stock status for category page',
					'style' => 'width: 100%;',
					'description' => 'Note: Only for Grouped product in the category page.'
				)
			);

			echo '</div>';
		if( ! is_plugin_active( 'woo-custom-stock-status-pro/woo-custom-stock-status-pro.php' ) ){
			echo '<div style="background: #e4efff;border: #bdd9fe solid 4px;width: 25%;font-size: 17px;padding: 23px;">';
			echo '<a href="https://softound.com/products/woo-custom-stock-status-pro/" target="_blank" style="font-weight: bold;">Get Woo Custom Stock Status Pro</a> to edit stock status using <strong>bulk edit option</strong> with <strong>WPML</strong> compatibility and custom stock status for <strong>Product Categories</strong>.';
			echo '</div>';
		}
		echo '</div>';
	}

	public function save_stock_status_message( $post_id ) {  
		foreach ($this->status_array as $meta_key => $val) {
			if(isset( $_POST[$meta_key] ) && !empty( $_POST[$meta_key] ) ) {
				update_post_meta( $post_id , $meta_key , sanitize_text_field( $_POST[$meta_key] ) );
			} else {
				delete_post_meta( $post_id, $meta_key );
			}
		}

		if(isset($_POST['grouped_product_stock_status']) && !empty($_POST['grouped_product_stock_status'])){
			update_post_meta( $post_id , 'grouped_product_stock_status' , sanitize_text_field( $_POST['grouped_product_stock_status'] ) );
		}else{
			delete_post_meta( $post_id , 'grouped_product_stock_status');
		}
	}

	public function woo_variation_stock_status_field( $loop, $variation_data, $variation ) {
		$right_side = array('in_stock','can_be_backordered','available_on_backorder');
		echo '<div style="clear:both"></div><p style="font-size:14px;"><b>'.__( 'Custom Stock Status' , 'woo-custom-stock-status' ).'</b></p>';

		if( ! is_plugin_active( 'woo-custom-stock-status-pro/woo-custom-stock-status-pro.php' ) ){
			echo '<div style="background: #e4efff;border: #bdd9fe solid 4px;font-size: 17px;padding: 23px;"><a href="https://softound.com/products/woo-custom-stock-status-pro/" target="_blank" style="font-weight: bold;">Get Woo Custom Stock Status Pro</a> to edit stock status using <strong>bulk edit option</strong> with <strong>WPML</strong> compatibility and custom stock status for <strong>Product Categories</strong>.</div>';
		}

		foreach ($this->status_array as $key => $name) { ?>
			<p class="form-row <?php echo in_array( $key,$right_side ) ? 'form-row-first' : 'form-row-last' ?>">
				<label><?php _e( $name , 'woo-custom-stock-status' ); ?></label>
				<input type="text" placeholder="<?php echo $name; ?>" name="variable_<?php echo $key; ?>_status[<?php echo $loop; ?>]" value="<?php echo esc_html(get_post_meta( $variation->ID , '_'.$key.'_status' , true )); ?>" />
			</p>
		<?php
		}
	}

	public function save_variation_stock_status( $post_id , $variation_key ) {
		foreach ($this->status_array as $meta_key => $val) {
			if(isset( $_POST['variable_'.$meta_key.'_status'][$variation_key] ) && !empty( $_POST['variable_'.$meta_key.'_status'][$variation_key] ) ) {
				update_post_meta( $post_id , '_'.$meta_key.'_status' , sanitize_text_field( $_POST['variable_'.$meta_key.'_status'][$variation_key] ) );
			} else {
				delete_post_meta( $post_id, '_'.$meta_key.'_status' );
			}
		}
	}

	/**
	 * Show stock status after price on product listing page
	 */
	public function add_stack_status_before_after_price($price ,$product ){  
		if (is_shop() || is_product_category() || is_archive() || (!is_product() && !is_cart() && !is_checkout()) )  { 
			//Compatible with b2bking-pro plugin
			if(is_plugin_active( 'b2bking/b2bking.php' )){
				$hidestock = get_option( 'b2bking_hide_stock_for_b2c_setting', 'disabled' );
				$is_b2b_user = get_user_meta(get_current_user_id(),'b2bking_b2buser', true);

				if (is_user_logged_in() && $is_b2b_user !== 'yes'&& $hidestock === 'hidecompletely'){
					return $price;
				}
			}

		    $stock_status = $this->get_custom_stock_status($product);
			$wc_slr_stock_status_before_price = get_option( 'wc_slr_stock_status_before_price', 'no' );

		 	if($wc_slr_stock_status_before_price=='yes'){
		 		$price = $stock_status.$price;
		 	}else{
		 		$price = $price.$stock_status;
		 	}
		}
		return $price;
	}
	

	/**
	 * Show stock status in product detail page
	 */
	public function add_stack_status_in_product_detail(){
		if(is_product()){
			global $product;
			$availability_html = $this->get_custom_stock_status($product);

			if(is_plugin_active( 'b2bking/b2bking.php' )){
				$hidestock = get_option( 'b2bking_hide_stock_for_b2c_setting', 'disabled' );
				$is_b2b_user = get_user_meta(get_current_user_id(),'b2bking_b2buser', true);

				if (is_user_logged_in() && $is_b2b_user !== 'yes'&& $hidestock === 'hidecompletely'){
					$availability_html = '';
				}
			}

			echo $availability_html;
		}
	}


	/**
	 * Show stock status in product listing page
	 */
	public function add_stack_status_in_summary(){
		global $product;
		$availability_html = $this->get_custom_stock_status($product);
		echo $availability_html;
	}

	/**
	 * Get product custom stock status for product listing page
	 */
	public function get_custom_stock_status($product){
		$show_status = get_option( 'wc_slr_show_in_shop_page' , 'yes' );
		$availability_html = '';
		if( $show_status === 'yes' ) {

			if (!empty($product) ) {
				$availability      = $product->get_availability();

				if (strpos($availability['availability'], '[wcss_learn_more') !== false) {
				   $availability['availability'] = do_shortcode($availability['availability']);
				}

				if (strpos($availability['availability'], '[wcss_delivery_date') !== false) {
				   $availability['availability'] = do_shortcode($availability['availability']);
				}

				$availability_html = empty( $availability['availability'] ) ? '' : '<p class="stock ' . esc_attr( $availability['class'] ) . '">' . __($availability['availability'],'woo-custom-stock-status') . '</p>';
			}
		}

		return $availability_html;
	}

	/**
	 *	Show the stock status in "New In", "Fan Favorites", "On Sale", and "Best Sellers" blocks 
	 */
	public function woocommerce_blocks_product_grid_stock_status($html, $data, $product){
		$show_status = get_option( 'wc_slr_show_in_wordpress_blocks' , 'no' );
		if($show_status === "no") {
			return $html;
		} 
		$availability      = $product->get_availability();
		if (strpos($availability['availability'], '[wcss_learn_more') !== false) {
			$availability['availability'] = do_shortcode($availability['availability']);
		}

		if (strpos($availability['availability'], '[wcss_delivery_date') !== false) {
			$availability['availability'] = do_shortcode($availability['availability']);
		}

		$availability_html = empty( $availability['availability'] ) ? '' : '<p class="stock ' . esc_attr( $availability['class'] ) . ' woocss_shortcode">' . __(esc_html( $availability['availability'] ),'woo-custom-stock-status') . '</p>';

		return "<li class=\"wc-block-grid__product\">
				<a href=\"{$data->permalink}\" class=\"wc-block-grid__product-link\">
					{$data->image}
					{$data->title}
				</a>
				{$data->badge}
				{$data->price}
				{$data->rating}
				{$availability_html}
				{$data->button}
			</li>";
	}

	/**
	* Woo custom stock status in order confirmation (for backorders) (Improved)
	*/
	public function add_stock_status_in_order_confirmation( $item_id , $item , $order  ) {	
		$variation_id 				= 	$item->get_variation_id();
		$product_id 				= 	$item->get_product_id();

		if($variation_id>0){
			$variation 				= 	new WC_Product_Variation( $variation_id );
			$product_availabilty 	= 	$variation->get_availability();
		} else {
			$product 				= 	new WC_Product( $product_id );
			$product_availabilty 	= 	$product->get_availability();
		}

		$order_items     		=	$order->get_items();
		$on_backorder 			= 	false;
		$order_id 				=	$order->get_id();
		$show_status_in_email	= 	get_option( 'wc_slr_show_in_order_email' , 'no' );
		foreach(  $order_items as $items_ ) {
			$itemId = $items_->get_id(); 
			if(	$itemId == $item_id && $items_['Backordered']	) {
				$on_backorder = true;
			}
		}

		if (strpos($product_availabilty['availability'], '[wcss_learn_more') !== false) {
			$product_availabilty['availability'] = do_shortcode($product_availabilty['availability']);
		}

		if (strpos($product_availabilty['availability'], '[wcss_delivery_date') !== false) {
			$product_availabilty['availability'] = do_shortcode($product_availabilty['availability']);
		}
	
		$hide_product_status = get_post_meta($product_id,'hide_stock_status',true);
		if( ( ( $on_backorder === true ) || ( $show_status_in_email == 'yes' ) ) && ( $product_id > 0 ) ) {

			if( $on_backorder === true && $hide_product_status != 'yes'){

				$woo_custom_stock_status = $product_availabilty['availability'];
				$custom_message		 =  serialize(array(
					'class'   => esc_html($product_availabilty['class']),
					'status'  => $woo_custom_stock_status
				));

				$show_instockstatus_on_backordered_product	= 	get_option( 'wc_slr_show_instock_backordered' , 'no' );

				if($show_instockstatus_on_backordered_product == 'yes'){
					$woo_custom_stock_status_email_txt = html_entity_decode ( wc_get_order_item_meta($item_id, '_woo_custom_stock_status_email_txt') );

					echo $woo_custom_stock_status_email_txt;
				}else{
					$backorder_message       = get_post_meta($order_id,'woo_custom_stock_status_backorder_status_'.$item_id,true);
					if( ($backorder_message == '') || ( is_null($backorder_message) ) || (empty($backorder_message)) ) {
						update_post_meta($order_id, 'woo_custom_stock_status_backorder_status_'.$item_id, $custom_message);
						wc_update_order_item_meta($item_id, '_woo_custom_stock_status_email_txt', $woo_custom_stock_status);
					}
					$custom_message       = unserialize(get_post_meta($order_id,'woo_custom_stock_status_backorder_status_'.$item_id,true));
					echo wp_kses_post( '<p class="stock '.esc_html( $custom_message['class'] ) .'">'.__($custom_message['status'],'woo-custom-stock-status').'</p>' );
				}
			} else if( $show_status_in_email == 'yes' && $hide_product_status != 'yes'){
				//Include stock status in order email
				if( isset( $product_availabilty['availability'] ) ) {
					$woo_custom_stock_status_email_txt = html_entity_decode ( wc_get_order_item_meta($item_id, '_woo_custom_stock_status_email_txt') );
					if(empty($woo_custom_stock_status_email_txt)){
						wc_update_order_item_meta($item_id, '_woo_custom_stock_status_email_txt', $product_availabilty['availability']);
						$woo_custom_stock_status_email_txt = $product_availabilty['availability'];
					}
					//Show or hide "Stock Status" tag before custom stock status text in Order Email based on "wc_slr_show_stock_status_tag_in_email" option
					$show_stock_status_tag_in_email	= get_option( 'wc_slr_show_stock_status_tag_in_email','yes');

					if($show_stock_status_tag_in_email == 'no'){
						echo "<br />".$woo_custom_stock_status_email_txt;
					}else{
						echo "<br /><strong class='woo-custom-stock-status stock'>".__( 'Stock Status', 'woo-custom-stock-status' ).':</strong> '.$woo_custom_stock_status_email_txt;
					}
					
				}
			}
		}
	}


}
