<?php
/************************************************************************/
/******************* CREATE GOOGLE PRODUCT FEED *************************/
/************************************************************************/
function createGoogleProductFeed(){
	
	//query the database
	$products = mysql_query_flat("
		SELECT 
			* 
		FROM
			dzpro_shop_items
		LEFT JOIN
			dzpro_shop_item_to_option USING ( dzpro_shop_item_id )
		LEFT JOIN 
			dzpro_shop_item_options USING ( dzpro_shop_item_option_id )
		LEFT JOIN
			dzpro_shop_item_to_tags USING ( dzpro_shop_item_id )
		LEFT JOIN
			dzpro_tags USING (dzpro_tag_id)
		WHERE 
			( dzpro_shop_item_id IS NOT NULL AND dzpro_shop_item_id != 0 )
		AND
			( dzpro_tag_id IS NOT NULL AND dzpro_tag_id != 0 )
		AND 
			dzpro_shop_item_active = 1
		ORDER BY 
			dzpro_shop_item_hits DESC
	");
	
	//organize the array
	$organized = array();
	if(have($products)){
		foreach($products as $product){
			$organized[$product['dzpro_shop_item_id']]['item'] = $product;
			$organized[$product['dzpro_shop_item_id']]['tags'][$product['dzpro_tag_id']] = $product['dzpro_tag_name'];
			$organized[$product['dzpro_shop_item_id']]['options'][$product['dzpro_shop_item_option_id']] = $product;
		}
	}

	//build output
	$output = '';
	$columns = array('link', 'title', 'description', 'expiration_date', 'price', 'image_link', 'product_type', 'id', 'condition', 'google_product_category', 'availability', 'brand', 'mpn');
	$output .= implode("\t", $columns) . "\n";
	if(have($organized)){
		foreach($organized as $organized_item){
			$column_count = 1;
			foreach($columns as $column){
				switch($column){
					case 'link':
						$output .= 'http://' . HOST_NAME . '/item/' . prepareStringForUrl($organized_item['item']['dzpro_shop_item_name']) . '-' . convertNumber((int)$organized_item['item']['dzpro_shop_item_id']) . '/';
					break;
					case 'title':
						$output .= removeWhitespace(prepareStringHtml($organized_item['item']['dzpro_shop_item_name']));
					break;
					case 'description':
						$output .= removeWhitespace(prepareStringHtml($organized_item['item']['dzpro_shop_item_description']));
					break;
					case 'expiration_date':
						$output .= date('Y-m-d\TH:i:s', strtotime('2 weeks'));
					break;
					case 'price':
						$output .= '$' . number_format($organized_item['item']['dzpro_shop_item_price'], 2);
					break;
					case 'image_link':
						$output .= 'http://' . HOST_NAME . $organized_item['item']['dzpro_shop_item_image'];
					break;
					case 'product_type':
					 	if(sizeof($organized_item['tags']) > 4){ array_splice($organized_item['tags'], 5); } 
						$output .= removeWhitespace(implode(', ', $organized_item['tags']));
					break;
					case 'google_product_category':
						if(sizeof($organized_item['tags']) > 3){ $organized_item['tags'] = array_splice($organized_item['tags'], 3); } 
						$output .= removeWhitespace(implode(', ', $organized_item['tags']));
					break;
					case 'availability':
						$output .= (0 < (int)$organized_item['item']['dzpro_shop_item_quantity']) ? 'in stock' : 'out of stock';
					break;
					case 'id':
						$output .= removeWhitespace($organized_item['item']['dzpro_shop_item_id']);
					break;
					case 'condition':
						$output .= 'new';
					break;
					case 'brand':
						$output .= (have($organized_item['item']['dzpro_shop_item_creamery'])) ? removeWhitespace(prepareStringHtml($organized_item['item']['dzpro_shop_item_creamery'])) : removeWhitespace(prepareStringHtml(SITE_NAME));
					break;
					case 'mpn':
						$output .= removeWhitespace(prepareStringHtml($organized_item['item']['dzpro_shop_item_pid']));
					break;
				}
				if(sizeof($columns) != $column_count){ $output .= "\t"; }
				$column_count++; 
			}
			$output .= "\n";
		}
	}
	
	//check output
	if(!have($output)){ handleError(1, 'No output generated'); exit(0); }
	
	//write to file
	if(false === ($fp = fopen(DOCUMENT_ROOT . GOOGLE_FEED_UPLOAD_FOLDER . GOOGLE_FEED_FILE_NAME, 'w'))){ handleError(1, 'Could not open file: ' . DOCUMENT_ROOT . GOOGLE_FEED_UPLOAD_FOLDER . GOOGLE_FEED_FILE_NAME); exit(0); }
	if(false === fwrite($fp, $output)){ handleError(1, 'Could not write to: ' . DOCUMENT_ROOT . GOOGLE_FEED_UPLOAD_FOLDER . GOOGLE_FEED_FILE_NAME); exit(0); }
	fclose($fp);
	
	//connect to ftp
	if(false === ($conn_id = ftp_connect(GOOGLE_FEED_SERVER))){ handleError(1, 'Could not connect to ftp server: ' . GOOGLE_FEED_SERVER); exit(0); }
	
	//login to ftp
	if(false === ftp_login($conn_id, GOOGLE_FEED_FTP_USERNAME, GOOGLE_FEED_FTP_PASSWORD)){ handleError(1, 'Could not login to ftp server: ' . GOOGLE_FEED_SERVER . ' with username: ' . GOOGLE_FEED_FTP_USERNAME); exit(0); }
	
	//return
	return ftp_put($conn_id, GOOGLE_FEED_FILE_NAME, DOCUMENT_ROOT . GOOGLE_FEED_UPLOAD_FOLDER . GOOGLE_FEED_FILE_NAME, FTP_ASCII);
	
}
?>