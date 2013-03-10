<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../assets');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//authenticate session
$Auth = new Auth($db);

?>
<!DOCTYPE html> 
<html lang="en-us"> 
	<head> 
		<?php require RELATIVE_ASSETS_PATH . '/elements/head.element.php'; ?>
		<link type="text/css" href="<?=ASSETS_PATH?>/css/listing.css" rel="stylesheet" media="all" />		
	</head>
	<body>
		<div id="wrapper">
			<table id="outer_table" cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<td id="left_column">
							<?php require_once RELATIVE_ASSETS_PATH . '/elements/sections.element.php'; ?>
						</td><!-- end left_column -->
						<td id="right_column">
							<?php require_once RELATIVE_ASSETS_PATH . '/elements/subsections.element.php'; ?>
							<table id="inner_content_table" cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td id="inner_content_left">
											<div id="bucket">
												<ul class="listing_parent">
													<li class="record_listing">
														<strong style="font-size: 12px; color: #333333;">
															<?php if(false !== getSiteData('twitter_oauth_token') and false !== getSiteData('twitter_oauth_token_secret')){ ?>
																Re-connect to Twitter <span style="font-weight: normal;">[connected]</span>
															<?php }else{ ?>
																Connect to Twitter
															<?php } ?>
														</strong>
														<a href="<?=TWITTER_CONNECT_URL?>" title="Connect To Twitter" id="twitter_connect">
															<!-- block -->
														</a>
													</li>
													<li class="record_listing">
														<strong style="font-size: 12px; color: #333333;">
															<?php if(false !== getSiteData('facebook_user_id') and false !== getSiteData('facebook_user_name')){ ?>
																Re-connect to Facebook <span style="font-weight: normal;">[connected]</span>
															<?php }else{ ?>
																Connect to Facebook
															<?php } ?>
														</strong>
														<a href="https://graph.facebook.com/oauth/authorize?client_id=<?=FACEBOOK_APPLICATION_ID?>&amp;redirect_uri=http://<?=MANAGER_DOMAIN?><?=FACEBOOK_CATCH_COOKIE_URL?>&amp;type=user_agent&amp;display=page&amp;scope=<?=FACEBOOK_DATA_SCOPE?>" title="Connect To Facebook" id="facebook_connect">
															<!-- block -->
														</a>
													</li>
													<li class="record_listing">
														<strong style="font-size: 12px; color: #333333;">
															<?php if(isset($_SESSION['linkedin']) and !empty($_SESSION['linkedin'])){ ?>
																Re-connect to LinkedIn <span style="font-weight: normal;">[connected]</span>
															<?php }else{ ?>
																Connect to LinkedIn
															<?php } ?>
														</strong>
														<a href="<?=LINKEDIN_BASE_URL?>" title="Connect To LinkedIn" id="linkedin_connect">
															<!-- block -->
														</a>
													</li>
												</ul>
											</div>
										</td><!-- end inner_content_left -->
										<td id="inner_content_right">
											
										</td><!-- end inner_content_right -->	
									</tr>
								</tbody>
							</table><!-- end inner_content_table -->
						</td><!-- end right_column -->
					</tr>
				</tbody>
			</table><!-- end outer_table -->
		</div><!-- end wrapper -->
	</body>
</html>
<?php

//close the database connection
mysql_close($db);
?>