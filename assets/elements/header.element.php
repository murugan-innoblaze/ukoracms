<div id="header">
	<div id="header_inner">
		<div style="position: absolute; top: 0px; left: 0px; height: 113px; width: 1024px; background: url('<?=have($this->current_page['dzpro_page_section_image']) ? $this->current_page['dzpro_page_section_image'] : '/uploads/dzpro_pages/dzpro_page_section_image/2011-07-29-16-10-42/cheese-header-bg-jpg_cropped.jpg'?>') top left no-repeat transparent;"><!-- background --></div>
		<div id="left_overlay"><!-- left overlay --></div>
		<div id="right_overlay"><!-- right overlay --></div>
		<a href="/" title="<?=SITE_NAME?>" id="home_link"><img src="/assets/layout/cheesemart-logo.png" alt="<?=SITE_NAME?>" /></a>
		<img src="/assets/layout/wisconsin-cheese-icon.gif" alt="<?=SITE_NAME?>" id="wisconsin_cheese_icon" />
		<h1 id="page_title"><?=prepareStringHtml($this->current_page['dzpro_page_title'])?></h1>
		<a href="/visit/" title="Visit us downtown Milwaukee" id="visit_us_tile"><img src="/assets/layout/visit-us-downtown-milwaukee.png" alt="Visit us downtown Milwaukee" /></a>
		<form action="/search/" method="get">
			<input id="search_text" type="search" name="q" value="<?php if(isset($this->search)){ echo prepareStringHtmlFlat($this->search); }?>" />
			<input id="search_button" type="submit" value="search" />
		</form>
		<?php if(!activeUserSession()){ ?>
		<a href="/connect/" title="Register/Connect or Login" class="connect button" style="position: absolute; z-index: 5; right: 15px; top: 10px;">Register/Login</a>
		<a href="/assets/ajax/subscribe.php" title="Subscribe to our mailing list" class="fancybox button" style="position: absolute; z-index: 5; right: 135px; top: 10px;">Subscribe</a>
		<?php }else{ ?>
		<a href="<?=addToGetString('logout', true)?>" title="Logout" class="button" style="position: absolute; z-index: 5; right: 15px; top: 10px;">Logout</a>
		<a href="/account/" title="Account" class="button" style="position: absolute; z-index: 5; right: 84px; top: 10px;">Account</a>
		<a href="/assets/ajax/subscribe.php" title="Subscribe to our mailing list" class="fancybox button" style="position: absolute; z-index: 5; right: 159px; top: 10px;">Subscribe</a>
		<?php } ?>
		<a href="/cart/" title="Checkout now" class="button" style="position: absolute; z-index: 5; right: 15px; top: 40px;"><img src="/assets/img/cart.png" alt="cart icon" style="vertical-align: middle;" /> Cart: (<span id="total_item_count"><?=(isset($_SESSION['cart']['product_count']) ? $_SESSION['cart']['product_count'] : 0)?></span> item<?=((isset($_SESSION['cart']['product_count']) and $_SESSION['cart']['product_count'] != 1) ? 's' : null)?>) $<span id="total_cart_amount"><?=(isset($_SESSION['cart']['total_amount']) ? number_format($_SESSION['cart']['total_amount'], 2) : '0.00')?></span></a>
	</div><!-- end header_inner -->
</div><!-- end header -->