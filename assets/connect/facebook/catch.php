<?php
//where are we
define('RELATIVE_ASSETS_PATH', '../..');

//knock over the first domino
require RELATIVE_ASSETS_PATH . '/conf/general.settings.php';

//make sure we have a session
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
			function setCookie(cookieName, cookieValue, expire){
				if(expire != undefined){
					var date = new Date();
					date.setTime(date.getTime() + (expire * 24 * 3600));
					var expires = "; expires=" + date.toGMTString() + "; path=/;";
				}else{
					var expires = "; expires=;  path=/;";
				}
				document.cookie = cookieName + '=' + escape(cookieValue) + expires;
			}
			var pathArray = window.location.href.split('#');
			//alert('window.location.href:'+window.location.href);
			//alert('pathArray:' + pathArray);
			//for(x in pathArray){ alert('pathArray['+x+']:'+pathArray[x]); }
			if(pathArray[1] != undefined){
				var fbCookieArray = pathArray[1].split('&');
			}
			if(fbCookieArray != undefined){
				var cookieNavAndValue = fbCookieArray[0];
				var cookieName = '';
				var cookieValue = '';
				var cookieParts = cookieNavAndValue.split('=');
				if(cookieParts[1] != undefined){
					cookieName = cookieParts[0];
					cookieValue = cookieParts[1];
					if(fbCookieArray[1] != undefined && fbCookieArray[1] > 0){
						var fbCookieExpires = fbCookieArray[1].split('=');
						if(fbCookieExpires[1] != undefined){
							setCookie(cookieName, cookieValue, fbCookieExpires[1]);
							window.location.href = '<?=FACEBOOK_CONNECT_SCRIPT_URL?>';
						}
					}else{
						setCookie(cookieName, cookieValue, 2);
						setTimeout(function(){ window.location.href = '<?=FACEBOOK_CONNECT_SCRIPT_URL?>'; }, 500);
					}
				}else{
					<?php $Navigation = new Navigation($db); ?>
					window.location.href = '<?=$Navigation->returnLastPageUrl()?>';
				}
			}else{
				<?php $Navigation = new Navigation($db); ?>
				window.location.href = '<?=$Navigation->returnLastPageUrl()?>';
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