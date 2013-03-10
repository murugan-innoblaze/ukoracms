var topNavTimeoutHolder = '';
var topNavSubTimeoutHolder = '';
var topNavSubTimeoutHolderAlt = '';
var topNavHideDelay = 600;
$().ready(function(){
	$('#top_nav > li').hover(function(){
		clearTimeout(topNavTimeoutHolder);
		$('.openTopLevelNav').removeClass('openTopLevelNav').hide();
		$(this).children('.sub_top_nav_out').addClass('openTopLevelNav').show();
	}, function(){
		var thisObject = $(this);
		topNavTimeoutHolder = setTimeout(function(){
			thisObject.children('.sub_top_nav_out').hide();
		}, topNavHideDelay);
	});
	$('li.second-nav-item').hover(function(){
		$('.openSubLevelNav').removeClass('openSubLevelNav').hide();
		$(this).children('.sub_sub_nav_out').addClass('openSubLevelNav').show();
		clearTimeout(topNavSubTimeoutHolder);
	}, function(){
		var thisSubObject = $(this);
		topNavSubTimeoutHolder = setTimeout(function(){
			thisSubObject.children('.sub_sub_nav_out').hide();
		}, topNavHideDelay);	
	});
	$('.sub_sub_nav_out').hover(function(){
		clearTimeout(topNavSubTimeoutHolderAlt);
	}, function(){
		var thisSubObject = $(this);
		topNavSubTimeoutHolderAlt = setTimeout(function(){
			thisSubObject.hide();
		}, topNavHideDelay);									
	});
});