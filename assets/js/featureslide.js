
function setBackgroundLayerWidth(){
	if($(window).width() > $('body').width()){
		$('.background_layer').css('width', $(window).width() + 'px'); 
	}else{
		$('.background_layer').css('width', $('body').width() + 'px'); 
	}
}

var featureTimeoutvar = ''; 
var id = 1; 
var max = 3; 
var slide_timeout = 6000; 
var fadeSpeed = 500;

function gotoNextFeature(){ 
	$('.background_layer, .main_tile').fadeOut(fadeSpeed);
	id += 1;
	if(id > max){ id = 1; }
	$('#background_tile_' + id + ', #display_tile_' + id).fadeIn(fadeSpeed);
	featureTimeoutvar = setTimeout(gotoNextFeature, slide_timeout);
	setHighlight(id);
}

function gotoPrev(){
	$('.background_layer, .main_tile').fadeOut(fadeSpeed);
	id -= 1;
	if(id < 1){ id = max; }
	$('#background_tile_' + id + ', #display_tile_' + id).fadeIn(fadeSpeed);
	setHighlight(id);
}

function gotoNext(){ 
	$('.background_layer, .main_tile').fadeOut(fadeSpeed); 
	id += 1; 
	if(id > max){ id = 1; } 
	$('#background_tile_' + id + ', #display_tile_' + id).fadeIn(fadeSpeed);
	setHighlight(id);
}

function gotoId(setid){
	clearTimeout(featureTimeoutvar);
	id = setid;
	$('.background_layer, .main_tile').hide();
	$('#background_tile_' + id + ', #display_tile_' + id).show();
	setHighlight(id);
}

function setHighlight(id){
	var pos = $('#slide_button_' + id).position();
	$('#viewer_highlight').css({
		left : (pos.left - ($('#viewer_highlight').width() / 2) + 80) + 'px',
		top : (pos.top - ($('#viewer_highlight').height() / 2) + 110) + 'px'
	});
	$('#display_column_list li').animate({ opacity : '0.7' }, 100);
	setTimeout(function(){ $('#slide_button_' + id).animate({ opacity : '1' }, 100); }, 100);
}

$().ready(function(){ 
	
	$('.background_layer, .main_tile').hide(); 
	$('.first_load').fadeIn(fadeSpeed); 
	
	featureTimeoutvar = setTimeout(gotoNextFeature, slide_timeout); 
	
	$('#inner_window').hover(function(){ 
		clearTimeout(featureTimeoutvar); 
	}, function(){ 
		featureTimeoutvar = setTimeout(gotoNextFeature, slide_timeout); 
	});
	
	$('li', '#display_column_list').hover(function(){
		gotoId($(this).attr('id').substr(13));
	});
	
	$('#left_arrow').click(gotoPrev); 
	
	$('#right_arrow').click(gotoNext);
	
	setBackgroundLayerWidth(); 
	
	$(window).resize(setBackgroundLayerWidth);
	
});
