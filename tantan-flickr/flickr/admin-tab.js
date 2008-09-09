jQuery(document).ready(function($) {
	$(photos) // json'd object containing info for all photos
	   .each(function(i) { 
	       //console.log(i, this)
	       $('#file-link-'+i).click(function() {    
	               $('#flickr-photo-'+i).siblings().toggle();
	               tantan_toggleOptions(i)
	               return false;
	       });
	   });
	$('button.photo-url-dest').click(function(){
	    jQuery(this).siblings('input').val(this.url);
	});
    $('input.cancel').click(function() {
        $('#upload-files li').show();
        $('.photo-options').hide();
    });
    $('input.send').click(function() {
        //
        // TODO: take user input and send to editor
        //
        
        var photo = $(photos).get($('#photo-id').val())
        photo['title'] = $('#photo-title').val();
        photo['targetURL'] = $('#photo-url').val();
        //console.log(photo)
        
        tantan_addPhoto(photo, $('input[name=image-size]:checked').val(), {
        	"align": $('input[name=image-align]:checked').val()
        });
    });
});
function tantan_toggleOptions(i) {
	if (isNaN(i)) return;
	$ = jQuery;
	
    photo = photos[i];
	$('#photo-meta').html('<strong>'+photo['title']+'</strong>');
	$('#photo-id').val(i);
    $('#photo-title').val(photo['title']);
    $('#photo-caption').val(photo['description']);
    $('#photo-url').val(jQuery('#file-link-'+i).attr('href'));
    $('.photo-options').toggle();
    
    
	$('#photo-url-none').attr('url', '');
	$('#photo-url-flickr').attr('url', photo['flickrURL']);
	$('#photo-url-blog').attr('url', photo['blogURL']);
	$('.image-size .field *').hide();
	jQuery.each(photo['sizes'], function(key, value) {
		jQuery('input[name=image-size][value='+key+']').show().next().show();
	})
	$('input[name=image-size][value=Medium]:visible').attr('checked', 'checked');
	$('input[name=image-size][value=Video Player]:visible').attr('checked', 'checked');
	//console.log(photo)
}

///
/// OLD CODE
///
var lastPhoto = false;
function tantan_showOptions(id) {
    if (lastPhoto) tantan_hideOptions(lastPhoto)
    lastPhoto = id

    var div = document.getElementById('options-'+id)
    if (div) div.style.display='block';
    return false;
}
function tantan_hideOptions(id) {
    var div = document.getElementById('options-'+id)
    if (div) div.style.display='none';
    
    var e = window.event;
	if (e) {
        e.cancelBubble = true;
    	if (e.stopPropagation) e.stopPropagation();
    }
    return false;
}
// photo contains a json'd data array
function tantan_addPhoto(photo, size, opts) {
	if (!isNaN(parseInt(photo))) {
		photo = photos[photo];
	}
	var h = tantan_makePhotoHTML(photo, size, opts);

	if (typeof top.send_to_editor == 'function') {
		top.send_to_editor(h);
	} else {
	    var win = window.opener ? window.opener : window.dialogArguments;
		if ( !win ) win = top;
		tinyMCE = win.tinyMCE;
		if ( typeof tinyMCE != 'undefined' && tinyMCE.getInstanceById('content') ) {
			tinyMCE.selectedInstance.getWin().focus();
			tinyMCE.execCommand('mceInsertContent', false, h);
		} else if (win.edInsertContent) win.edInsertContent(win.edCanvas, h);
	}
	if (typeof top.tb_remove == 'function') {
		if (!jQuery('image-close-check').val()) 
			top.tb_remove();
	}

	return false;
}
function tantan_makePhotoHTML(photo, size, opts) { 
//console.log(photo, size, opts)
	if (size == 'Video Player') {
		return '[flickr video='+photo['id']+']'
	} else {
		var h = '';
		if (photo['targetURL']) h += '<a href="'+photo['targetURL']+'" class="tt-flickr'+(size ? (' tt-flickr-'+size) : '')+'">';
		h += '<img class="'+(opts['align'] ? ('align'+opts['align']) : '')+'" src="'+photo['sizes'][size]['source']+'" alt="'+photo['title']+'" width="'+photo['sizes'][size]['width']+'" height="'+photo['sizes'][size]['height']+'" />';
		if (photo['targetURL']) h += '</a> ';
		return h;
	}
}
function tantan_addShortCode(attribs) {
	top.send_to_editor('[flickr'+(attribs ? (' '+attribs) : '')+']');
	if (typeof top.tb_remove == 'function') 
		top.tb_remove();
}