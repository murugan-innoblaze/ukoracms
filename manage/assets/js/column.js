function fixColumnHeights(){
	var leftColumnHeight = $('#left_column').height();
	var leftInnerColumnHeight = $('#left_column_inner').height();
	var leftInnerColumnPadding = 14;
	$('#left_column_inner').css('height', leftColumnHeight - leftInnerColumnPadding + 'px');
}
$().ready(function(){
	fixColumnHeights();
	$(window).resize(fixColumnHeights);
});