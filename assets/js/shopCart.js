function updateCartTotals(){
	setTimeout(function(){
		$.ajax({
			url : '/assets/ajax/cart.php',
			type : 'post',
			data : '',
			dataType : 'json',
			success : function(mssg){
				if(mssg.total_products != undefined){ $('#total_item_count').text(mssg.total_products); }
				if(mssg.total_amount != undefined){ $('#total_cart_amount').text(mssg.total_amount); }
			}
		});
	}, 500);
}
function addToCart(id){
	$.blockUI();
	setTimeout(function(){
		$.ajax({
			url : '/assets/ajax/cart.php',
			type : 'post',
			data : $('#' + id).serialize(),
			dataType : 'json',
			success : function(mssg){
				if(mssg.total_products != undefined){ $('#total_item_count').text(mssg.total_products); }
				if(mssg.total_amount != undefined){ $('#total_cart_amount').text(mssg.total_amount); }
				$.unblockUI();
				if(parent.$.fancybox != undefined){ parent.$.fancybox.close(); }
				$('#added_to_cart_message').fadeIn(300);
				setTimeout(function(){
					$('#added_to_cart_message').fadeOut(500);
				}, 4000);
			}, 
			error : function(error){
				$.unblockUI();
			}
		});
	}, 500);
}
function openProductOverlay(product_id){
	$.fancybox({
		overlayShow : true, 
		overlayColor : '#222222', 
		overlayOpacity : 0.5, 
		opacity : true, 
		transitionIn : 'elastic', 
		transitionOut : 'elastic', 
		titleShow : false, 
		easingIn : 'easeOutBack',
		easingOut : 'easeInBack',
		href : '/assets/ajax/product_overlay.php?product_id=' + encodeURIComponent(product_id),
		width : '800px'
	});
	return false;
}
$().ready(function(){ 
	updateCartTotals();
});