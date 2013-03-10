function countAndTagColumns(){ var columns = 0; $('#article_target .column').each(function(){ $(this).attr('id', 'article_column_'+columns); columns += 1; }); return columns; }

function setColumnHeight(takeOffHeight){
	var windowHeight = $(window).height();
	var articlePostionObject = $('#article_content_column').position();
	var articleContentTop = articlePostionObject.top;
	var bottomArticleColumnHeight = windowHeight - articleContentTop;
	var articleWindowHeight = bottomArticleColumnHeight - $('#article_head_block').height() - $('#paginate_article_area').height() - takeOffHeight;
	$('#article_content_column_in').css('height', bottomArticleColumnHeight+'px');
	$('#article_layout_table').css('height', bottomArticleColumnHeight+'px');
	$('#article_target').css('max-height', articleWindowHeight+'px');
	columnizeAndPaginate();
}

function columnizeAndPaginate(){
	$('#article_target').columnize({balance : false});	
	var columnsCount = countAndTagColumns();
	var paginationHtmlHolder = '';
	var prev_holder = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	var next_holder = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';	
	if(columnsCount > 1){
		prev_holder = '<a href="javascript: return false;" id="article_paginate_prev">&lt;&nbsp;prev</a>';
		next_holder = '<a href="javascript: return false;" id="article_paginate_next">next&nbsp;&gt;</a>';
	}
	paginationHtmlHolder = prev_holder;
	for(var i = 1; i <= columnsCount; i+=1){
		paginationHtmlHolder = paginationHtmlHolder+'<a class="article_paginate_pages" href="javascript: return false;" id="article_paginate_page_'+i+'">&nbsp;'+i+'&nbsp;</a>';
	}
	paginationHtmlHolder = paginationHtmlHolder+next_holder;
	$('#paginate_article_area').html(paginationHtmlHolder);	
	modifyPaginateArea($('#article_content_window').scrollLeft());	
}

function modifyPaginateArea(currentLeftScroll){
	var articleColumnPadding = 20;
	var columnInFocus = Math.floor(currentLeftScroll / ($('#article_target .column').width() - 0 + articleColumnPadding)) + 1;
	var columnsCount = countAndTagColumns();
	var prev_text = '&lt;&nbsp;prev';
	var next_text = 'next&nbsp;&gt;';
	var place_text = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	if(columnsCount == columnInFocus){ $('#article_paginate_next').html(place_text); }else{ $('#article_paginate_next').html(next_text); }
	if(columnInFocus == 1){ $('#article_paginate_prev').html(place_text); }else{ $('#article_paginate_prev').html(prev_text); }
	$('.article_paginate_pages.focus').removeClass('focus');
	$('#article_paginate_page_'+columnInFocus).addClass('focus');
}

function paginateActions(){
	var columnsCount = countAndTagColumns();
	var columnInFocus = '1';
	var articleColumnPadding = 20;
	var currentLeftScroll = $('#article_content_window').scrollLeft();
	modifyPaginateArea(currentLeftScroll);
	$('#article_paginate_prev').live('click', function(){
		columnsCount = countAndTagColumns();
		currentLeftScroll -= ($('#article_target .column').width() - 0 + articleColumnPadding);	
		if(currentLeftScroll < 0){ currentLeftScroll = 0; }	
		scrollToNewLocation('#article_content_window', currentLeftScroll);
		modifyPaginateArea(currentLeftScroll);
	});
	$('#article_paginate_next').live('click', function(){
		columnsCount = countAndTagColumns();
		currentLeftScroll += ($('#article_target .column').width() - 0 + articleColumnPadding);
		if(currentLeftScroll > (columnsCount - 1) * ($('#article_target .column').width() - 0 + articleColumnPadding)){ currentLeftScroll = (columnsCount - 1) * ($('#article_target .column').width() - 0 + articleColumnPadding); }
		scrollToNewLocation('#article_content_window', currentLeftScroll);
		modifyPaginateArea(currentLeftScroll);
	});
	$('.article_paginate_pages').live('click', function(){
		columnsCount = countAndTagColumns();
		currentLeftScroll = ($(this).attr('id').substr(22) - 1) * ($('#article_target .column').width() - 0 + articleColumnPadding);
		scrollToNewLocation('#article_content_window', currentLeftScroll);
		modifyPaginateArea(currentLeftScroll);
	});
}

function columizeComments(){
	var commentCount = 0;
	var columnCount = 1;
	var commentPaddingMargin = 17;
	var tempHeight = $('.add_comments').height(); $('.add_comments:first').hide();
	var maxColumnHeight = $('#article_content_column_in').height() + 12;
	var newHtmlString = '<table cellpadding="0" cellspacing="0"><tbody><tr><td><div class="add_comments shadow_light">'+$('.add_comments').html()+'</div>'; 
	$('.comment_element').each(function(){
		var thisCommentHeight = $(this).height()-0+commentPaddingMargin;
		tempHeight += thisCommentHeight;
		commentCount += 1;
		if(tempHeight > maxColumnHeight){
			newHtmlString = newHtmlString+'</td><td><div class="'+$(this).attr('class')+'">'+$(this).html()+'</div>';
			tempHeight = thisCommentHeight;
			columnCount += 1;
		}else{
			newHtmlString = newHtmlString+'<div class="'+$(this).attr('class')+'">'+$(this).html()+'</div>';
		}
	});
	newHtmlString = newHtmlString+'</td></tr></tbody></table>';
	$('.contents_target').html(newHtmlString);
}

function assignScrollWheelToContent(){
	$('#article_content_column_in').mousewheel(function(event, delta){
		var step = 50;
		var columLeftOffset = 0;
		var windowWidth = $(window).width();
		var columnTableWidth = $('#article_layout_table').width();
		columLeftOffset = $('#article_content_column_in').scrollLeft(); 
		columLeftOffset = columLeftOffset - (step * delta);	
		if(columLeftOffset < 0){ columLeftOffset = 0; }	
		if(columLeftOffset > (columnTableWidth - windowWidth)){ columLeftOffset = columnTableWidth - windowWidth; }
		$('#article_content_column_in').scrollLeft(columLeftOffset);
	});		
}

function scrollToNewLocation(scrollWindowId, newColumnLeftOffset){
	var intervalTime = 30;
	var slideTotalTime = 210;
	var currentOffLeftOffset = $(scrollWindowId).scrollLeft();
	var slideDifference = currentOffLeftOffset - newColumnLeftOffset;
	var totalSteps = slideTotalTime / intervalTime;
	var slideStep = -(slideDifference / totalSteps);
	var slideTempScrollLeft = currentOffLeftOffset;
	var stepCount = 0;
	if(intervalHolder != undefined){ clearTimeout(intervalHolder); } var intervalHolder = setInterval(function(){ slideTempScrollLeft += slideStep; if(stepCount >= totalSteps){ clearInterval(intervalHolder); }else{ $(scrollWindowId).scrollLeft(slideTempScrollLeft); } stepCount += 1; }, intervalTime);
}

$().ready(function(){
	var resizeDelay = 200;
	setColumnHeight(50); var resizeTimeoutColumnHeight = ''; $(window).resize(function(){ clearTimeout(resizeTimeoutColumnHeight); resizeTimeoutColumnHeight = setTimeout(function(){ setColumnHeight(30); }, resizeDelay); });
	var resizeTimeoutComments = setTimeout(columizeComments, resizeDelay); $(window).resize(function(){ clearTimeout(resizeTimeoutComments); resizeTimeoutComments = setTimeout(columizeComments, resizeDelay); });
	assignScrollWheelToContent();
	paginateActions();
});
