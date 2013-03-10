<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../../');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//assure session
assureSession();

/******************************************************************************/
/******************************************************************************/
/******************************************************************************/
/*         THIS SCRIPT CATCHES URL AND SETS THE COOKIE THEN FORWARDS          */
/******************************************************************************/
/******************************************************************************/
/******************************************************************************/

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html>
	<head>
		<script type="text/javascript">
			<!--
			function setCookie(nameAndvalue,expire){
				if(expire != undefined){
					var date = new Date();
					date.setTime(date.getTime() + (expire * 1000));
					var expires = "; expires=" + date.toGMTString() + "; path=/";
				}else{
					var expires = "; expires=;  path=/";
				}
				document.cookie = nameAndvalue + ((expires==null) ? "" : expires);
			}
			var pathArray = window.location.href.split('#');
			if(pathArray[1] != undefined){
				var fbCookieArray = pathArray[1].split('&');
			}
			if(fbCookieArray != undefined){
				var cookieNavAndValue = fbCookieArray[0];
				if(fbCookieArray[1] != undefined && fbCookieArray[1] > 0){
					var fbCookieExpires = fbCookieArray[1].split('=');
					if(fbCookieExpires[1] != undefined){
						setCookie(cookieNavAndValue, fbCookieExpires[1]);
						window.location.href = '<?=FACEBOOK_CONNECT_SCRIPT_URL?>';
					}
				}else{
					setCookie(cookieNavAndValue);
					window.location.href = '<?=FACEBOOK_CONNECT_SCRIPT_URL?>';
				}
			}else{
				window.location.href = '<?=TALK_SECTION_SITE_PATH?>';
			}
			-->
		</script>
	</head>
	<body>
		<!-- forwarding -->
	</body>
</html>
<?php
//close db connection
mysql_close($db);
?>