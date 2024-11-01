<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
* Updates custom stock status on products when it is imported via "Wp All Import" plugin
*/
class Woo_Stock_With_WpAllImport {
		
	public function __construct() {
		add_action('pmxi_saved_post', array($this , 'update_stock_status_on_WpAllImport') , 10, 3);
		add_action('pmxi_extend_options_custom_fields', array($this,'custom_fields'),10,2);
	}

	public function custom_fields($post_type, $post){ 

		$woo_stock_obj = new Woo_Stock_Base();
		$status_array = $woo_stock_obj->status_array;
		$status_array['grouped_product_stock_status'] = 'Grouped product stock status';
		?>
		<div class="wpallimport-collapsed wpallimport-section wpallimport-custom-fields">
			<div class="wpallimport-content-section">
				<div class="wpallimport-collapsed-header">
					<h3>Woo Custom Stock Status Fields</h3>
				</div>
				<div class="wpallimport-collapsed-content" style="padding: 0px; display: block;">
					<div class="wpallimport-collapsed-content-inner">
						<table class="form-table wpallimport-custom-fields-list" style="max-width:none;">
							<tbody>
								<tr>
									<td colspan="3" style="padding-top:20px;">

										<table class="form-table custom-params" style="max-width:none; border:none;">
											<thead>
												<tr>
													<td style="padding-bottom:10px;">Name</td>
													<td style="padding-bottom:10px;">Value</td>
													</tr>
											</thead>
											<tbody>
											<?php foreach ($status_array as $key => $value) { ?>	
												<tr class="form-field">
													<td style="width: 45%;">
														<input type="text" name="custom_name[]" value="<?php echo $key; ?>" class="widefat wp_all_import_autocomplete ui-autocomplete-input" style="margin-bottom:10px;" autocomplete="off" rel="">
														<input type="hidden" name="custom_format[]" value="0">
													</td>
													<td class="action">
														<div class="custom_type" rel="default">
															<textarea name="custom_value[]" class="widefat"></textarea>	
														</div>
														<span class="action remove">
															<a href="#remove" style="top: 8px; right: 0;"></a>
														</span>
													</td>
												</tr>	
											<?php } ?>
											</tbody>
										</table>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Update custom stock status when products imported via wp-import plugin
	 */
	public function update_stock_status_on_WpAllImport( $post_id, $xml_node, $is_update ) {

		$product = wc_get_product($post_id);

		if($product){
			$product_type = $product->get_type();

			$status_array = array('woocustomstockstatus_in_stock' => 'in_stock',
								'woocustomstockstatus_only_s_left_in_stock' => 'only_s_left_in_stock',
								'woocustomstockstatus_can_be_backordered' => 'can_be_backordered',
								'woocustomstockstatus_s_in_stock' => 's_in_stock',
								'woocustomstockstatus_available_on_backorder' => 'available_on_backorder',
								'woocustomstockstatus_out_of_stock' => 'out_of_stock',
								'woocustomstockstatus_grouped_product_stock_status' => 'grouped_product_stock_status');

			foreach ($status_array as $label => $status_meta_key) {
				$meta_key = $status_meta_key; 
				if( isset($xml_node->$label) ){ 
					if($product_type == 'simple'){ 
						$$status_meta_key = (string) $xml_node->$label; 
						update_post_meta($post_id,$status_meta_key,$$status_meta_key);
					}else if($product_type == 'variation'){	
						$status_meta_key = '_'.$status_meta_key.'_status';	
						$$status_meta_key = (string) $xml_node->$label;	
						update_post_meta($post_id,$status_meta_key,$$status_meta_key);
					}else if($product_type == 'grouped'){ 
						$status_meta_key = 'grouped_product_stock_status';
						$$status_meta_key = (string) $xml_node->$label;
						update_post_meta($post_id,$status_meta_key,$$status_meta_key);
					}
				}else{ 
					if($product_type == 'simple'){
						delete_post_meta($post_id,'grouped_product_stock_status');
					}else if($product_type == 'variable'){
						delete_post_meta($post_id,$status_meta_key);
					}
					else if($product_type == 'variation'){	
						$meta_value = get_post_meta($post_id,$status_meta_key,true);
						delete_post_meta($post_id,$status_meta_key);
						$status_meta_key = '_'.$status_meta_key.'_status';	
						update_post_meta($post_id,$status_meta_key,$meta_value);
					}else if($product_type == 'grouped'){ 
						if($status_meta_key != 'grouped_product_stock_status'){
							delete_post_meta($post_id, $status_meta_key);
						}
					}
				}
			}
		}
		
	}
}
