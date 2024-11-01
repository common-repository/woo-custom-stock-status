<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
* WC Stock Status Setting Tab functions
*/

class Woo_Stock_Setting extends Woo_Stock_Base {
	
	public function __construct() {
		
		// add stock status tab in woocommerce setting page
		add_filter( 'woocommerce_settings_tabs_array', array( $this , 'add_settings_tab' ) , 50 );
		// stock status color css
		add_action( 'wp_head',array( $this,'woo_custom_stock_status_color' ) );

		add_action( 'woocommerce_sections_wc_stock_list_rename', array($this,'add_sections_on_wcss_settings'), 10 );

		add_action( 'admin_head',array($this,'admin_custom_style'));

		add_filter('gettext', array($this,'custom_wc_save_button_text'), 20, 3);
	}

	public function custom_wc_save_button_text($translated_text, $text, $domain) { 
	    if ($text === 'Save changes') {
	        $translated_text = __( 'Save Changes', 'woo-custom-stock-status' );
	    }
	    return $translated_text;
	}


	/**
	 * Used to add custom styles on wp-admin
	 */
	public function admin_custom_style(){
		?>
		<style type="text/css">
			.woo-custom-stock-status.form-table th{
				padding: 10px!important;
			}
			.woo-custom-stock-status.form-table th p{
				font-size: 16px;
				padding-left: 10px;
			}
			.woo-custom-stock-status.form-table td{
				padding: 10px!important;
				border-right: 1px solid #dfdfdf;
			}
			.woo-custom-stock-status .forminp-checkbox{
				text-align: center;
			}
			.woo-custom-stock-status .stock-text{
				width: 440px!important;
			}

			.woo-custom-stock-status .forminp-number{
				display: flex;
    			align-items: center;
    			margin-bottom:0px ;
			}

		</style>
		<?php
	}

	/**
	 * Used to add subsections under the Woo Custom Stock Status settings tab
	 */
	public function add_sections_on_wcss_settings() {
	    global $current_section;

	    $tab_id = 'wc_stock_list_rename';

	    $sections = array(
	        ''              => __( 'General', 'woo-custom-stock-status' ),
	        'stock_status'  => __( 'Stock Status', 'woo-custom-stock-status' ),
	    );

	    echo '<ul class="subsubsub">';

	    $array_keys = array_keys( $sections );

	    foreach ( $sections as $id => $label ) {
	        echo '<li><a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . $tab_id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
	    }

	    echo '</ul><br class="clear" />';
	}
	
	/**
	 * Add a new settings tab to the WooCommerce settings tabs array.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
	 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs['wc_stock_list_rename'] = __( 'Custom Stock', 'woo-custom-stock-status' );
		return $settings_tabs;
	}

	/**
	 * load custom stock color css in head
	 */
	public function woo_custom_stock_status_color() {
		$css = '<style id="woo-custom-stock-status" data-wcss-ver="'.WCSS_PLUGIN_VER.'">';

		$status_array = $this->status_array;
		$status_array['grouped_product_stock_status'] = 'Grouped product stock status';
		foreach ($status_array as $key => $label) {
			$color_options_default = $this->status_color_array[$key.'_color']['default'];
			$status_color = $key.'_color';
			$status_color_code = (get_option('wc_slr_'.$status_color,$color_options_default)=='') ? $color_options_default : get_option('wc_slr_'.$status_color,$color_options_default);


			$font_size_options_default = $this->status_font_size_array[$key.'_font_size']['default'];
			$status_font_size = $key.'_font_size';
			$status_font_size_code = (get_option('wc_slr_'.$status_font_size,$font_size_options_default)=='') ? $font_size_options_default : get_option('wc_slr_'.$status_font_size,$font_size_options_default);
			if(!empty($status_font_size_code)){
				if($status_font_size_code=='inherit'){
					$status_font_size_code = 'font-size: '.$status_font_size_code;
				} else {
					$status_font_size_code = 'font-size: '.$status_font_size_code.'px;';
				}
			}

			$css .= sprintf('.woocommerce div.product .woo-custom-stock-status.%s { color: %s !important; %s }', $status_color, $status_color_code, $status_font_size_code);

			$css .= sprintf('.woo-custom-stock-status.%s { color: %s !important; %s }', $status_color, $status_color_code, $status_font_size_code);

			$css .= '.wc-block-components-product-badge{display:none!important;}';
			//For details page
			$css .= sprintf('ul .%s,ul.products .%s, li.wc-block-grid__product .%s { color: %s !important; %s }', $status_color, $status_color, $status_color, $status_color_code, $status_font_size_code);//For listing page
			$css .= sprintf('.woocommerce-table__product-name .%s { color: %s !important; %s }', $status_color,$status_color_code, $status_font_size_code);
			$css .= sprintf('p.%s { color: %s !important; %s }', $status_color,$status_color_code, $status_font_size_code);
			
			$css .= '.woocommerce div.product .wc-block-components-product-price .woo-custom-stock-status{font-size:16px}';
		}

		$wc_slr_hide_sad_face = get_option( 'wc_slr_hide_sad_face', 'no' );
		if($wc_slr_hide_sad_face=='yes'){
			$css .= '.woocommerce div.product .woo-custom-stock-status.stock.out-of-stock::before { display: none; }';
		}
		
		$css .= '.wd-product-stock.stock{display:none}';

		$css .= '.woocommerce-variation-price:not(:empty)+.woocommerce-variation-availability { margin-left: 0px; }.woocommerce-variation-price, .woocommerce-variation-availability{
			display:block}';
		$css .= '</style><!-- woo-custom-stock-status-color-css -->';
		echo $css;

		$js = '<script>';
		$wc_slr_stock_status_after_addtocart = get_option( 'wc_slr_stock_status_after_addtocart', 'no' );
		if($wc_slr_stock_status_after_addtocart=='yes'){
			$js .= "jQuery(function(){ var stock_html = jQuery('.product .summary .stock').clone();jQuery('.product .summary .stock').remove();jQuery(stock_html).insertAfter('form.cart'); });";
			//For block theme
			$js .= "jQuery(function(){ var stock_html = jQuery('.product .wp-block-column .wp-block-woocommerce-product-price .stock').clone();jQuery('.product .wp-block-column .wp-block-woocommerce-product-price .stock').remove();jQuery(stock_html).insertAfter('form.cart'); });";
		}
		$js .= '</script><!-- woo-custom-stock-status-js -->';
		echo $js;
		
	}
}
