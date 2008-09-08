jQuery(document).ready(function($) {
	$(photos) // json'd object containing info for all photos
	   .each(function(i) { 
	       //console.log(i, this)
	       $('#file-link-'+i).click(function() {
	               
	               $('#flickr-photo-'+i).siblings().toggle();
	               tantan_toggleOptions(i, photos[i])
	               return false;
	       });
	   });
    $('input.cancel').click(function() {
        $('#upload-files li').show();
        $('#photo-options').hide();
    });
    $('input.send').click(function() {
        //
        // TODO: take user input and send to editor
        //
        tantan_insertPhoto(); // ?
    });
});
function tantan_toggleOptions(i, photo) {
    
    jQuery('#photo-title').val(photo['title'])
    jQuery('#photo-caption').val(photo['description'])
    jQuery('#photo-url').val(jQuery('#file-link-'+i).attr('href'))
    jQuery('#photo-options').toggle();
    //console.log(photo)
}
function tantan_insertPhoto() {
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
function tantan_addPhoto(photo, size) {
	if (!isNaN(parseInt(photo))) {
		photo = photos[photo];
	}
	var h = tantan_makePhotoHTML(photo, size);
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
		if (document.getElementById('closewindowcheck') && document.getElementById('closewindowcheck').checked) 
			top.tb_remove();
		else if (!document.getElementById('closewindowcheck')) 
			top.tb_remove();
	}

	return false;
}
function tantan_makePhotoHTML(photo, size) { 
	if (size == 'Video Player') {
		return '[flickr video='+photo['id']+']'
	} else {
	return '<a href="'+photo['targetURL']+'" class="tt-flickr'+(size ? (' tt-flickr-'+size) : '')+'">' +
		'<img src="'+photo['sizes'][size]['source']+'" alt="'+photo['title']+'" width="'+photo['sizes'][size]['width']+'" height="'+photo['sizes'][size]['height']+'" border="0" />' +
		'</a> ';
	}
}
function tantan_addShortCode(attribs) {
	top.send_to_editor('[flickr'+(attribs ? (' '+attribs) : '')+']');
	if (typeof top.tb_remove == 'function') 
		top.tb_remove();
}