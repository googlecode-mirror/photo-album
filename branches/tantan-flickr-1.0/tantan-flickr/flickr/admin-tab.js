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
		var url = jQuery(this).attr('url');
		if (url == 'none') url = '';
	    jQuery('#photo-url').val(url);
	});
    $('input.cancel').click(function() {
        $('#upload-files li').show();
        $('.photo-options').hide();
    });
    
    // photo insert
    $('.photo input.send').click(function() {
        var photo = $(photos).get($('#photo-id').val());
        photo['title'] = $('#photo-title').val();
        photo['targetURL'] = $('#photo-url').val();
        
        tantan_addPhoto(photo, $('.photo input[name=image-size]:checked').val(), {
        	"align": $('.photo input[name=image-align]:checked').val()
        });
        return false;
    });
    
    // album insert
    $('.album input.send').click(function() {
        var photo = $(photos).get($('#photo-id').val());
        var num = $('.album input[name=album-insert-photos]:checked').val();
        var size = $('.album input[name=album-image-size]:checked').val();
        if (num == 'cover') {
            tantan_addPhoto(photo, size, {});
        } else {
            tantan_addShortCode('album='+photo['id']+' num='+num+' size='+size);
        }
        return false;
    });
    
});

// setup insert options
function tantan_toggleOptions(i) {
	if (isNaN(i)) return;
	$ = jQuery;
	
    photo = photos[i];
	$('#photo-meta').html('<strong>'+photo['title']+'</strong><br />'+(photo['photos'] ? (photo['photos']+' photos'): ''));
	$('#photo-id').val(i);
    $('#photo-title').val(photo['title']);
    $('#photo-caption').val(photo['description']);
    $('#photo-url').val(jQuery('#file-link-'+i).attr('href'));
    $('.photo-options').toggle();
    
    
	$('#photo-url-none').attr('url', 'none');
	$('#photo-url-flickr').attr('url', photo['flickrURL']);
	$('#photo-url-blog').attr('url', photo['blogURL']);
	$('.photo .image-size .field *').hide();
	jQuery.each(photo['sizes'], function(key, value) {
		jQuery('.photo input[name=image-size][value='+key+']').show().next().show();
	})
	$('input[name=image-size][value=Square]:visible').attr('checked', 'checked');
	$('input[name=image-size][value=Medium]:visible').attr('checked', 'checked');
	$('input[name=image-size][value=Video Player]:visible').attr('checked', 'checked');
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
		if (jQuery('#image-close-check:checked').val())  {
			top.tb_remove();
		} else {
			jQuery('input.cancel').click();
		}
	}

	return false;
}
function tantan_makePhotoHTML(photo, size, opts) { 
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
	if (typeof top.tb_remove == 'function') {
		if (jQuery('#image-close-check:checked').val())  {
			top.tb_remove();
		} else {
			jQuery('input.cancel').click();
		}
	}
}

function tantan_showOptions(id) {}
function tantan_hideOptions(id) {}
