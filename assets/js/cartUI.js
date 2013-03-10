function updateItemCount(value){
	$('#total_item_count').text(value);
}
function updateCartTotal(value){
	$('#total_cart_amount').text(value);
}
function checkUserSession(){
	$.ajax({
		url : '/assets/ajax/session.php',
		type : 'post',
		data : '',
		success : function(mssg){
			if(mssg == 'true'){
				var userLoggedIn = true;
			}else{
				var userLoggedIn = false;
			}
		},
		error : function(){
			alert('Could not validate user session');
		}
	});
	setInterval(function(){
		if(userLoggedIn != undefined && userLoggedIn === true){
			alert('Your login has expired. Please login again. Thank you!');
			window.location = window.location;
		}
	}, 50);
}
function reloadCartCanvas(){
	$.blockUI();
	$.ajax({
		url : '/cart/',
		type : 'post',
		data : 'ajax=justcart',
		success : function(html){
			$('#the_cart_canvas').html(html);
			$.unblockUI();
		},
		error : function(){
			alert('an error occurred, please refresh the page (5)');
		}
	});
}
function updateShippingOptions(){
	$('.shipping_option_holder').each(function(){
		var address_holder_id = $(this).attr('id');
		var address_id = $(this).attr('id').substr(27);
		$.ajax({
			url : '/assets/ajax/shipping.php',
			type : 'post',
			data : 'address_id=' + encodeURIComponent(address_id),
			success : function(select_box){
				$('#' + address_holder_id).html(select_box);
			},
			error : function(error){
				alert('an error occurred, please refresh the page (6). Most likely this is because your shipping address has an invalid zipcode, please review your shipping address.');
			}
		});
	});
}
function updateShippingAddressForAllItems(object, old_address_id){
	$.blockUI();
	$.ajax({
		url : '/cart/',
		type : 'post',
		data : 'old_address_id=' + encodeURIComponent(old_address_id) + '&new_address_id=' + encodeURIComponent(object.children('option:selected').val()) + '&scope=all&ajax=justcart',
		success : function(html){
			$('#the_cart_canvas').html(html);
			$.unblockUI();
		},
		error : function(){
			alert('an error occurred, please refresh the page (7)');
		}
	});
}
function updateShippingAddressForItem(object, cart_item_id){
	$.blockUI();
	$.ajax({
		url : '/cart/',
		type : 'post',
		data : 'address_cart_item_id=' + encodeURIComponent(cart_item_id) + '&new_address_id=' + encodeURIComponent(object.children('option:selected').val()) + '&scope=item&ajax=justcart',
		success : function(html){
			$('#the_cart_canvas').html(html);
			$.unblockUI();
		},
		error : function(){
			alert('an error occurred, please refresh the page (8)');
		}
	});
}
function haveSelectedPreviousCard(){
	var selectedValue = getSelectedPreviousCard();
	if(selectedValue != undefined && selectedValue.length > 0){
		return true;
	}else{
		return false;
	}
}
function getSelectedPreviousCard(){
	return $('select[name=payment_method] option:selected', '#previous_payment_methods').val();
}
function handlePaymentResponse(response){
	if(response != undefined){
		if(response.status != undefined){
			if(response.status != 'true'){
				$('#payment_response_message').show().text(response.message);
			}else{
				alert('Your order has been completed!')
				window.location = '/cart/?completed=true';
			}
		}else{
			$('#payment_response_message').show().text('Please fill all fields and try again.');
		}
	}else{
		$('#payment_response_message').show().text('We could\'t contact our server at this time.');
	}
	$.unblockUI();
}
$().ready(function(){	
	$('td.quantity input[name=quantity]').live('keyup', function(event){
		if(event.keyCode == 13){
			var cart_item_id = $(this).parent('div').parent('td').attr('id').substr(13);
			var item_id_tag = $(this).parent('div').parent('td').attr('id');
			var quantity = $(this).val().replace(/[^0-9]+/, '');
			$.blockUI();
			$.ajax({
				url : '/cart/',
				type : 'post',
				data : 'cart_item_id=' + encodeURIComponent(cart_item_id) + '&quantity=' + encodeURIComponent(quantity) + '&ajax=justcart',
				success : function(html){
					$('#the_cart_canvas').html(html);
					$.unblockUI();
				},
				error : function(){
					alert('an error occurred, please refresh the page (9)');
				}
			});
		}else{
			var quantity = $(this).val().replace(/[^0-9]+/, '');
			$('.modify_cart').hide();
			if(quantity > 0){
				$(this).parent('div').children('.modify_cart').text('update').show().addClass('update_cart').removeClass('delete_cart');
			}else{
				$(this).parent('div').children('.modify_cart').text('delete').show().addClass('delete_cart').removeClass('update_cart');
			}								
		}								
	});
	$('.modify_cart').live('click', function(){
		var cart_item_id = $(this).parent('div').parent('td').attr('id').substr(13);
		var item_id_tag = $(this).parent('div').parent('td').attr('id');
		var quantity = $(this).parent('div').children('td.quantity input[name=quantity]').val().replace(/[^0-9]+/, '');
		$.blockUI();
		$.ajax({
			url : '/cart/',
			type : 'post',
			data : 'cart_item_id=' + encodeURIComponent(cart_item_id) + '&quantity=' + encodeURIComponent(quantity) + '&ajax=justcart',
			success : function(html){
				$('#the_cart_canvas').html(html);
				$.unblockUI();
			},
			error : function(){
				alert('an error occurred, please refresh the page (10)');
			}
		});
	});
	$('.change_address').live('click', function(){
		$('#pick_address_for_' + $(this).attr('id').substr(13)).toggle();
		$(this).text(($(this).text() == 'cancel') ? 'change address' : 'cancel');
	});
	$('.change_shipping_address').live('click', function(){
		$('.cart_overlay_outer').hide();
		$(this).parents('.change_shipping').children('.cart_overlay_outer').show();
	});
	$('.change_shipping_inner .close').live('click', function(){
		$(this).parent().parent().hide();
	});
	$('.change_item_options').live('click', function(){
		$('.cart_overlay_outer').hide();
		$(this).parents('.pick_options').children('.cart_overlay_outer').show();
	});
	$('.cart_overlay_inner .close').live('click', function(){
		$(this).parent().parent().parent().hide();
	});
	$('textarea[name=package_message]').live('blur', function(){
		var address_id = $(this).attr('id').substr(10);
		var message = $(this).val();
		$.blockUI();
		$.ajax({
			url : '/cart/',
			type : 'post',
			data : 'address_id=' + encodeURIComponent(address_id) + '&message=' + encodeURIComponent(message),
			success : function(mssg){
				$.unblockUI();
			},
			error : function(){
				alert('an error occurred, please refresh the page (11)');
			}
		});
	});
	$('.shipping_option_holder select').live('change', function(){
		var address_id = $(this).parents('.shipping_option_holder').attr('id').substr(27);
		var value = $(this).children('option:selected').val();
		$.blockUI();
		$.ajax({
			url : '/cart/',
			type : 'post',
			data : 'address_id=' + encodeURIComponent(address_id) + '&shipping_string=' + encodeURIComponent(value) + '&ajax=justcart',
			success : function(html){
				$('#the_cart_canvas').html(html);
				$.unblockUI();
			},
			error : function(){
				alert('an error occurred, please refresh the page (12)');
			}
		});
	});
	$('.add_options').live('click', function(){
		var options = {};
		$(this).parents('.options_holder').children('.options_container').children('input[type=checkbox]').each(function(){ options[($(this).prop('checked')) ? $(this).attr('name') : 0] =  $(this).val(); });
		var json_options_string = JSON.stringify(options);
		$.blockUI();
		$.ajax({
			url : '/cart/',
			type : 'post',
			data : 'options_string=' + encodeURIComponent(json_options_string) + '&ajax=justcart',
			success : function(html){
				$('#the_cart_canvas').html(html);
				$.unblockUI();
			},
			error : function(){
				alert('an error occurred, please refresh the page (13)');
			}
		});
	});
	$('#agree_to_terms_and_conditions').live('click', function(){
		var checked = 0;
		$.blockUI();
		if($('#agree_to_terms_and_conditions').prop('checked')){ checked = 1; }
		$.ajax({
			url : '/cart/',
			type : 'post',
			data : 'agreed_to_conditions=' + encodeURIComponent(checked) + '&ajax=justcart',
			success : function(html){
				$('#the_cart_canvas').html(html);
				$.unblockUI();
			},
			error : function(){
				alert('an error occurred, please refresh the page (14)');
			}
		});
	});
	$('select[name=payment_method]', '#previous_payment_methods').change(function(){
		if(haveSelectedPreviousCard()){
			$('#use_previous_card_or_add').hide();
			$('#payment_values_form').hide();
			$('select[name=payment_method] option:first', '#previous_payment_methods').text('User a different card.');
		}else{
			$('#use_previous_card_or_add').show();
			$('#payment_values_form').show();
			$('select[name=payment_method] option:first', '#previous_payment_methods').text('Choose previously used card');
		}
	});	
	$('select[name=payment_method]', '#previous_payment_methods').live('change', function(){
		if(haveSelectedPreviousCard()){
			$('#use_previous_card_or_add').hide();
			$('.cvv_holder_previous_card').show();
			$('#payment_values_form').slideUp();
			$('select[name=payment_method] option:first', '#previous_payment_methods').text('User a different card.');
		}else{
			$('#use_previous_card_or_add').show() 
			$('.cvv_holder_previous_card').hide();
			$('#payment_values_form').slideDown();
			$('select[name=payment_method] option:first', '#previous_payment_methods').text('Choose previously used card');
		}
	});
	$('#cvv_code_holder').keyup(function(event){
		if(event.keyCode == 13){ $('#confirm_order_button').trigger('click'); }
	});
	$('#confirm_order_button').live('click', function(){
		$.blockUI();
		$('#payment_response_message').hide();
		if(haveSelectedPreviousCard()){
			var thisCardKey = getSelectedPreviousCard();
			var thisCardCVV = $('#cvv_code_holder').val();
			var paymentMethod = 'authorize.net';
			if(thisCardCVV.length < 3){
				alert('Please enter your CVV code. The CVV code is the 3 or 4 digit code on the back of your card.');
				$.unblockUI();
				$('#cvv_code_holder').focus();
			}else{
				$.ajax({
					url: '/cart/',
					type: 'POST',
					data: 'ajax=processPreviousCard&paymentMethod=' + encodeURIComponent(paymentMethod) + '&card_key=' + encodeURIComponent(thisCardKey) + '&cvv_code=' + encodeURIComponent(thisCardCVV),
					dataType : 'json',
					success : handlePaymentResponse,
					//success : function(mssg){ alert(mssg); },
					error : function(){
						alert('an error occurred, please refresh the page (15). If your cart is empty after you refresh your page, that means that we did receive your order, and you should receive your receipt within minutes. Thank you!');
					}
				});
			}
		}else{
			$.ajax({
				url : '/cart/',
				type : 'POST',
				data : 'cc_request=true&' + $('#payment_values_form').serialize(),
				dataType : 'json',
				success : handlePaymentResponse,
				//success : function(mssg){ alert(mssg); },
				error : function(){
					alert('an error occurred, please refresh the page (16).  If your cart is empty after you refresh your page, that means that we did receive your order, and you should receive your receipt within minutes. Thank you!');
				}
			});
		}
	});
	$('#confirm_order_button_paypal').live('click', function(){
		$.blockUI();
		$.ajax({
			url : '/cart/',
			type : 'POST',
			data : 'paypal_request=true&' + $('#paypal_data_form').serialize(),
			dataType : 'json',
			success : handlePaymentResponse,
			//success : function(mssg){ alert(mssg); },
			error : function(){
				alert('an error occurred, please refresh the page (17). If your cart is empty after you refresh your page, that means that we did receive your order, and you should receive your receipt within minutes. Thank you!');
			}
		});
	});
});