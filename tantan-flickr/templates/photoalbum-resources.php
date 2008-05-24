<?php
/*
Copy this file into your current active theme's directory to customize this template

This template resource file defines the template tags used to create the HTML for your photos.

Note that the popup overlay display methods requires that you download the appropriate JavaScript libraries. 
You'll probably also need to tweak some paths in order for it to work with your setup.

A bunch of these libraries use jQuery, so if you're on an old version of WordPress (2.1 or order), 
you may need to download and install jQuery.

jQuery: http://jquery.com/ (also included by default with WordPress 2.2+)
*/

// where you uploaded the library

if (!defined('TANTAN_DISPLAY_LIBRARY'))      define('TANTAN_DISPLAY_LIBRARY', 'fancyzoom');
if (!defined('TANTAN_DISPLAY_LIBRARY_PATH')) define('TANTAN_DISPLAY_LIBRARY_PATH', '/tpl'); 
if (!defined('TANTAN_DISPLAY_POPUP_SIZE'))   define('TANTAN_DISPLAY_POPUP_SIZE', 'Medium');


//
// This is the base class used to display photos. You can override the methods defined in this class if you want to use
// a JavaScript display library (ie Lightbox). Some examples given below.
//
class TanTanFlickrDisplayBase {
	function headTags() { echo ''; /* include links to javascript, or stylesheet libraries here */}
	function footer() {echo ''; /* anything that might need to go into the footer*/ }
	
	// draw out a photo thumbnail, with <a> and <img> tags
	function photo ($photo, $options=null) {
		if (!is_array($options)) $options = array();
		$defaults = array(
			'size' => 'Square',
			'scale' => 1,
			'album' => null,
			'context' => null,
			'prefix' => '',
			);
		$options = array_merge($defaults, $options);
		extract($options);

		if (($context == 'gallery-index') && $album) {
			$prefix = 'album/'.$album['id'].'/';
		}
		$html = '<a class="tt-flickr tt-flickr-'.$size.'" href="'.TanTanFlickrDisplay::href($photo, $album, $prefix).'" '.
			'id="photo-'.$photo['id'].'" '.
			'title="'.htmlentities($photo['title'], ENT_COMPAT, 'UTF-8') . strip_tags($photo['description'] ? ' - '.$photo['description'] : '').'">'.
			TanTanFlickrDisplay::image($photo, $size, $scale).
			'</a> ';
		return $html;
	}
	
	// return the link to where the photo should go
	function href($photo, $album=null, $prefix='') {
		return $prefix.'photo/'.$photo['id'].'/'.($album ? ($album['pagename2'].'-') : '').$photo['pagename'];
	}
	
	// draw out an image tag
	function image($photo, $size, $scale=1) {
		return '<img src="'.$photo['sizes'][$size]['source'].'" width="'.($photo['sizes'][$size]['width']*$scale).'" '.
			'height="'.($photo['sizes'][$size]['height']*$scale).'" '. 
			'alt="'.htmlentities($photo['title'], ENT_COMPAT, 'UTF-8').'" />';
	}
	
	// this prints out the JavaScript function used to insert a photo into blog posts
	function js() {
		return "function tantan_makePhotoHTML(photo, size) { \n".
			//photoSourceUrl, photoPageUrl, thumbnailSourceUrl, width, height, title
				"return '<a href=\"'+photo['targetURL']+'\" class=\"tt-flickr'+(size ? (' tt-flickr-'+size) : '')+'\">' + \n".
				"	'<img src=\"'+photo['sizes'][size]['source']+'\" alt=\"'+photo['title']+'\" width=\"'+photo['sizes'][size]['width']+'\" height=\"'+photo['sizes'][size]['height']+'\" border=\"0\" />' + \n".
				"	'</a> '; \n".
			"} \n";
	}
}


/*
	Base class for common functions used by popup overlay libraries
*/
class TanTanFlickrPopUpOverlay extends TanTanFlickrDisplayBase {
	function href($photo, $album=null, $prefix='') {
		return $photo['sizes'][TANTAN_DISPLAY_POPUP_SIZE]['source'];
	}
	function js() {
		return 
			"function tantan_makePhotoHTML(photo, size) { \n".
			"var imgTag = '<img src=\"'+photo['sizes'][size]['source']+'\" alt=\"'+photo['title']+'\" width=\"'+photo['sizes'][size]['width']+'\" height=\"'+photo['sizes'][size]['height']+'\" border=\"0\" />' \n".
			"if (photo['photos']) { \n".
				"return '<a href=\"'+photo['targetURL']+'\" class=\"tt-flickr'+(size ? (' tt-flickr-'+size) : '')+'\">' + \n".
				"imgTag + \n".
				"'</a>'\n".
			"} else if (parseInt(photo['sizes'][size]['width']) < parseInt(photo['sizes']['".TANTAN_DISPLAY_POPUP_SIZE."']['width'])) { \n".
			"	return '<a href=\"'+photo['sizes']['".TANTAN_DISPLAY_POPUP_SIZE."']['source']+'\" class=\"tt-flickr tt-flickr'+(size ? (' tt-flickr-'+size) : '')+'\">' + \n".
			"	imgTag + \n".
			"		'</a> '; \n".
			"} else { return imgTag } \n".
			"} \n";
	}	
}
/*
	FancyBox: http://fancy.klade.lv/
*/
class TanTanFlickrDisplayFancyBox extends TanTanFlickrPopUpOverlay {
	function headTags() {
		wp_enqueue_script('jquery');
		wp_print_scripts();
		echo '<script>$ = jQuery;</script>'; // careful about conflicts with other libraries!
		echo '<script src="'.TANTAN_DISPLAY_LIBRARY_PATH.'/fancybox/jquery.fancybox.js" type="text/javascript"></script>';
		echo '<script type="text/javascript">jQuery(function($) { $("a.tt-flickr").fancybox(); });</script>';
	}
}

/*
	Facebox: http://famspam.com/facebox
*/
class TanTanFlickrDisplayFaceBox extends TanTanFlickrPopUpOverlay {
	function headTags() {
		wp_enqueue_script('jquery');
		wp_print_scripts();
		echo '<script>$ = jQuery;</script>'; // careful about conflicts with other libraries!
		echo '<link href="'.TANTAN_DISPLAY_LIBRARY_PATH.'/facebox/facebox.css" media="screen" rel="stylesheet" type="text/css"/>';
		echo '<script src="'.TANTAN_DISPLAY_LIBRARY_PATH.'/facebox/facebox.js" type="text/javascript"></script>';
		echo '<script type="text/javascript">jQuery(function($) { $("a.tt-flickr").facebox(); });</script>';
	}
}

/*
	jQuery lightBox: http://leandrovieira.com/projects/jquery/lightbox/
*/
class TanTanFlickrDisplayJQueryLightboxBox extends TanTanFlickrPopUpOverlay {
	function headTags() {
		wp_enqueue_script('jquery');
		wp_print_scripts();
		echo '<script>$ = jQuery;</script>'; // careful about conflicts with other libraries!
		echo '<link href="'.TANTAN_DISPLAY_LIBRARY_PATH.'/jquery-lightbox/css/jquery.lightbox.css" media="screen" rel="stylesheet" type="text/css"/>';
		echo '<script src="'.TANTAN_DISPLAY_LIBRARY_PATH.'/jquery-lightbox/js/jquery.lightbox.js" type="text/javascript"></script>';
		echo '<script type="text/javascript">jQuery(function($) { $("a.tt-flickr").lightBox(); });</script>';
	}
}

/*
	FancyZoom: http://www.cabel.name/2008/02/fancyzoom-10.html   (not a jQuery plugin)
*/
class TanTanFlickrDisplayFancyZoom extends TanTanFlickrPopUpOverlay {
	function headTags() {
		echo '<script src="'.TANTAN_DISPLAY_LIBRARY_PATH.'/fancyzoom/js-global/FancyZoom.js" type="text/javascript"></script>';
		echo '<script src="'.TANTAN_DISPLAY_LIBRARY_PATH.'/fancyzoom/js-global/FancyZoomHTML.js" type="text/javascript"></script>';
	}
	function footer() {
		echo '<script type="text/javascript">setupZoom();</script>';
	}
}
// comment out the line below, and replace it with one of these...
$fancybox  = "class TanTanFlickrDisplay extends TanTanFlickrDisplayFancyBox {};";
$facebox   = "class TanTanFlickrDisplay extends TanTanFlickrDisplayFaceBox {};";
$lightbox  = "class TanTanFlickrDisplay extends TanTanFlickrDisplayJQueryLightboxBox {};";
$fancyzoom = "class TanTanFlickrDisplay extends TanTanFlickrDisplayFancyZoom {};";

$default   = "class TanTanFlickrDisplay extends TanTanFlickrDisplayBase {}; ";
switch (TANTAN_DISPLAY_LIBRARY) {
	case 'fancybox':  eval($fancybox); break;
	case 'facebox':   eval($facebox); break;
	case 'lightbox':  eval($lightbox); break;
	case 'fancyzoom': eval($fancyzoom); break;
	default: eval($default);
}

add_action('wp_head', create_function('', 'TanTanFlickrDisplay::headTags();'));
add_action('wp_footer', create_function('', 'TanTanFlickrDisplay::footer();'));
?>