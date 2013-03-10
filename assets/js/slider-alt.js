var alt_left_right_controls_height = 15; 
var alt_top_bottom_padding = 0; 
var alt_rightSlideMargin = 0;
var alt_slideThisManyAtATime = 4;
var alt_slideSpeed = 500;

function alt_adJustSlider(alt_leftPos){ 
	var alt_totalSliderWidth = $('.alt_slider_item_table').width(); 
	var alt_totalViewPortWidth = $('.alt_slider_outer_holder').width();
	alt_setRightActive();
	alt_setLeftActive();
	if(alt_leftPos < alt_totalViewPortWidth - alt_totalSliderWidth){ alt_leftPos = alt_totalViewPortWidth - alt_totalSliderWidth; alt_setRightInactive(); } 
	if(alt_leftPos >= 0){ alt_leftPos = 0; alt_setLeftInactive(); } 
	$('.alt_slider_item_table').animate({'left' : alt_leftPos + 'px'}, alt_slideSpeed, 'swing'); 
}

function alt_setRightActive(){ $('.alt_right_arrow').animate({ 'opacity' : 1 }, alt_slideSpeed, 'swing').css('cursor', 'pointer'); }
function alt_setLeftActive(){ $('.alt_left_arrow').animate({ 'opacity' : 1 }, alt_slideSpeed, 'swing').css('cursor', 'pointer'); }
function alt_setRightInactive(){ $('.alt_right_arrow').animate({ 'opacity' : 0.0 }, alt_slideSpeed, 'swing').css('cursor', 'default'); }
function alt_setLeftInactive(){ $('.alt_left_arrow').animate({ 'opacity' : 0.0 }, alt_slideSpeed, 'swing').css('cursor', 'default'); }
function alt_slideLeft(){ alt_adJustSlider(alt_getCurrentOffset() + alt_getSlideStep()); }
function alt_slideRight(){ alt_adJustSlider(alt_getCurrentOffset() - alt_getSlideStep()); }
function alt_getSlideStep(){ return ( $('.slider_item').width() - 0 + alt_rightSlideMargin ) * alt_slideThisManyAtATime - 0; }
function alt_getCurrentOffset(){ return $('.alt_slider_item_table').css('left').substr(0, $('.alt_slider_item_table').css('left').length - 2) - 0; }

$().ready(function(){ 

	$('.alt_left_arrow:visible').live('click', alt_slideLeft); 
	$('.alt_right_arrow:visible').live('click', alt_slideRight); 

});