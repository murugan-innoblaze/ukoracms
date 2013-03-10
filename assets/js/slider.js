var left_right_controls_height = 15; 
var top_bottom_padding = 0; 
var rightSlideMargin = 0;
var slideThisManyAtATime = 4;
var slideSpeed = 500;

function adJustSlider(leftPos){ 
	var totalSliderWidth = $('.slider_item_table').width(); 
	var totalViewPortWidth = $('.slider_outer_holder').width();
	setRightActive();
	setLeftActive();
	if(leftPos < totalViewPortWidth - totalSliderWidth){ leftPos = totalViewPortWidth - totalSliderWidth; setRightInactive(); } 
	if(leftPos >= 0){ leftPos = 0; setLeftInactive(); } 
	$('.slider_item_table').animate({'left' : leftPos + 'px'}, slideSpeed, 'swing'); 
}

function setRightActive(){ $('.right_arrow').animate({ 'opacity' : 1 }, slideSpeed, 'swing').css('cursor', 'pointer'); }
function setLeftActive(){ $('.left_arrow').animate({ 'opacity' : 1 }, slideSpeed, 'swing').css('cursor', 'pointer'); }
function setRightInactive(){ $('.right_arrow').animate({ 'opacity' : 0.0 }, slideSpeed, 'swing').css('cursor', 'default'); }
function setLeftInactive(){ $('.left_arrow').animate({ 'opacity' : 0.0 }, slideSpeed, 'swing').css('cursor', 'default'); }
function slideLeft(){ adJustSlider(getCurrentOffset() + getSlideStep()); }
function slideRight(){ adJustSlider(getCurrentOffset() - getSlideStep()); }
function getSlideStep(){ return ( $('.slider_item').width() - 0 + rightSlideMargin ) * slideThisManyAtATime - 0; }
function getCurrentOffset(){ return $('.slider_item_table').css('left').substr(0, $('.slider_item_table').css('left').length - 2) - 0; }

$().ready(function(){ 

	$('.left_arrow:visible').live('click', slideLeft); 
	$('.right_arrow:visible').live('click', slideRight); 

});