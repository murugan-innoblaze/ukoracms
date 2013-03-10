var topNavHoverTimeout = '';
var topNavHoverTime = 500;
$().ready(function(){
	$('li a', '#nav_holder').click(function(){ $('li a.active', '#nav_holder').removeClass('active'); $(this).addClass('active'); });
	$('li a', '#sub_nav_holder').click(function(){ $('li a.active', '#sub_nav_holder, #default_links_holder').removeClass('active'); $(this).addClass('active'); });
	$('li a', '#nav_holder').hover(function(){
		$('li a.active_js', '#nav_holder').removeClass('active_js');
		$(this).addClass('active_js');
		var theseSubLinks = $(this).parent('li').children('.sub_menu_holer').html();
		$('#default_links_holder').hide();
		$('#hover_links_holder').html(theseSubLinks).show();
	});
	$('#top_navigation').hover(function(){
		clearTimeout(topNavHoverTimeout);
	}, function(){
		topNavHoverTimeout = setTimeout(function(){
			$('li a.active_js', '#nav_holder').removeClass('active_js');
			$('#hover_links_holder').hide();
			$('#default_links_holder').show();
		}, topNavHoverTime);
	});
	$('#sub_nav_holder').hover(function(){
		clearTimeout(topNavHoverTimeout);
	});
});