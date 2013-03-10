var startingOpacity = 0.5;
var magnificationFactor = 1.1;
var transitionSpeed = 200;
var originalDimensions = {};
var theAnimateTimeoutVar = '';
function setMosaicTileOpacity(){ $('table td', '#mosaic_element').css('opacity', startingOpacity); }
function setOriginalDimensions(){ $('table td', '#mosaic_element').each(function(){ originalDimensions[$(this).attr('id')] = {}; originalDimensions[$(this).attr('id')].width = $(this).width(); originalDimensions[$(this).attr('id')].height = $(this).height(); }); }
function returnToOriginalDimensions(){ $('table td', '#mosaic_element').each(function(){ $(this).animate({ width : originalDimensions[$(this).attr('id')].width + 'px', height : originalDimensions[$(this).attr('id')].height + 'px', opacity : startingOpacity}, transitionSpeed); }); }
$().ready(function(){ setMosaicTileOpacity(); setOriginalDimensions(); returnToOriginalDimensions();
	$('table td', '#mosaic_element').hover(function(){ clearTimeout(theAnimateTimeoutVar); var thisWidth = $(this).width(); var thisHeight = $(this).height(); var object = $(this); theAnimateTimeoutVar = setTimeout(function(){ object.animate({ opacity: 1, height : magnificationFactor * thisHeight + 'px', width : magnificationFactor * thisWidth + 'px' }, transitionSpeed); }, transitionSpeed); }, function(){ returnToOriginalDimensions(); }); });