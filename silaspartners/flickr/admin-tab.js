var lastPhoto = false;
function silas_showOptions(id) {
    if (lastPhoto) silas_hideOptions(lastPhoto)
    lastPhoto = id

    var div = document.getElementById('options-'+id)
    if (div) div.style.display='block';
    return false;
}
function silas_hideOptions(id) {
    var div = document.getElementById('options-'+id)
    if (div) div.style.display='none';
    
    var e = window.event;
	if (e) {
        e.cancelBubble = true;
    	if (e.stopPropagation) e.stopPropagation();
    }
    return false;
}
function silas_addPhoto(photoUrl, sourceUrl, width, height, title, size) {
	var h = silas_makePhotoHTML(photoUrl, sourceUrl, width, height, title, size);
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
	if (typeof top.tb_remove == 'function' && document.getElementById('closewindowcheck') && document.getElementById('closewindowcheck').checked) 
		top.tb_remove();

	return false;
}
function silas_makePhotoHTML(photoUrl, sourceUrl, width, height, title, size) { 
	return '<a href="'+photoUrl+'" class="tt-flickr'+(size ? (' tt-flickr-'+size) : '')+'">' +
		'<img src="'+sourceUrl+'" alt="'+title+'" width="'+width+'" height="'+height+'" border="0" />' +
		'</a> ';
}
function silas_addShortCode(attribs) {
	top.send_to_editor('[flickr'+(attribs ? (' '+attribs) : '')+']');
	if (typeof top.tb_remove == 'function') 
		top.tb_remove();
}