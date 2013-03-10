var featureTimeoutvar = ''; var id = 1; var max = 1; var slide_timeout = 3000; var fadeSpeed = 300;
function getSlidesCount(){ var count = 0; $('.the_slides', '#slides_holder_table').each(function(){ count++; }); return count; }
function getSlidesWidth(){ return $('.the_slides:first', '#slides_holder_table').width(); }
function gotoNextFeature(){ id += 1; if(id > max){ id = 1; } if(id == 1){ gotoFirstFromLast(); }else{ var leftPos = -((id - 1) * getSlidesWidth()); $('#slides_holder_table').animate({ 'left' : leftPos + 'px'}, fadeSpeed); featureTimeoutvar = setTimeout(gotoNextFeature, slide_timeout); } setSliderStatus(); }
function gotoPrev(){ id -= 1; if(id < 1){ id = max; var reset_to_first = true; } if(id > max){ id = 1; } if(reset_to_first != undefined && reset_to_first == true){ gotoLastFromFirst(); }else{ $('#slides_holder_table').animate({ 'left' : -((id - 1) * getSlidesWidth()) + 'px'}, fadeSpeed); } setSliderStatus(); }
function gotoNext(){ id += 1; if(id > max){ id = 1; } if(id > max){ id = 1; } if(id == 1){ gotoFirstFromLast(); }else{ $('#slides_holder_table').animate({ 'left' : -((id - 1) * getSlidesWidth()) + 'px'}, fadeSpeed); } setSliderStatus(); }
function gotoSlide(){ $('#slides_holder_table').animate({ 'left' : -((id - 1) * getSlidesWidth()) + 'px'}, fadeSpeed); setSliderStatus(); }
function gotoFirstFromLast(){ $('#slides_holder_table').animate({'left' : -(max * getSlidesWidth()) + 'px'}, fadeSpeed); setTimeout(function(){ $('#slides_holder_table').css({'left' : '1024px'}).animate({'left' : '0'}, fadeSpeed); }, fadeSpeed + 100); }
function gotoLastFromFirst(){ $('#slides_holder_table').animate({'left' : getSlidesWidth() + 'px'}, fadeSpeed); setTimeout(function(){ $('#slides_holder_table').css({'left' : -(max * getSlidesWidth()) + 'px'}).animate({'left' : -((max - 1) * getSlidesWidth()) + 'px'}, fadeSpeed); }, fadeSpeed + 100); }
function setSliderStatus(){ $('.active', '#slider_bottom').removeClass('active'); $('#theicon_' + id, '#slider_bottom').addClass('active'); }
$().ready(function(){ max = getSlidesCount(); featureTimeoutvar = setTimeout(gotoNextFeature, slide_timeout); $('#slider').hover(function(){ clearTimeout(featureTimeoutvar); }, function(){ featureTimeoutvar = setTimeout(gotoNextFeature, slide_timeout); }); $('#slider_left_arrow').click(gotoPrev); $('#slider_right_arrow').click(gotoNext); $('.icon', '#slider_bottom').click(function(){ id = $(this).attr('id').substr(8); gotoSlide(); }); });