<?php
/*
This resource file defines the template tags used to create the HTML for your photos.

Copy this file into your themes directory to customize
*/

//
// This is the base class used to display photos. You can override the methods defined in this class if you want to use a JavaScript display library (ie Lightbox)
//
class TanTanFlickrDisplayBase {
	function headTags() { echo ''; /* include links to javascript, or stylesheet libraries here */}
	function footer() {echo ''; /* anything that might need to go into the footer*/ }
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
			'title="'.htmlentities($photo['title']) . strip_tags($photo['description'] ? ' - '.$photo['description'] : '').'">'.
			TanTanFlickrDisplay::image($photo, $size, $scale).
			'</a> ';
		return $html;
	}
	function href($photo, $album=null, $prefix='') {
		return $prefix.'photo/'.$photo['id'].'/'.($album ? ($album['pagename2'].'-') : '').$photo['pagename'];
	}
	function image($photo, $size, $scale=1) {
		return '<img src="'.$photo['sizes'][$size]['source'].'" width="'.($photo['sizes'][$size]['width']*$scale).'" '.
			'height="'.($photo['sizes'][$size]['height']*$scale).'" '. 
			'alt="'.htmlentities($photo['title']).'" />';
	}
	function js() {
		return 
			"function tantan_makePhotoHTML(photoUrl, sourceUrl, width, height, title, size) { \n".
			"	return '<a href=\"'+photoUrl+'\" class=\"tt-flickr'+(size ? (' tt-flickr-'+size) : '')+'\">' + \n".
			"		'<img src=\"'+sourceUrl+'\" alt=\"'+title+'\" width=\"'+width+'\" height=\"'+height+'\" border=\"0\" />' + \n".
			"		'</a> '; \n".
			"} \n";
	}
}
/*
Here are a couple examples of how to hook into other image display libraries.

Note that these examples requires that you download the appropriate JavaScript libraries. 
You'll probably also need to tweak some paths in order for it to work with your setup.

A bunch of these libraries use jQuery, so if you're on an old version of WordPress (2.1 or order), 
you may need to download and install jQuery.

jQuery: http://jquery.com/ (also included by default with WordPress 2.2+)

*/


// where you uploaded the library
define('TANTAN_DISPLAY_LIBRARY_PATH', '****PATH-TO-LIBRARY-HERE-****'); 

/*
	FancyBox: http://fancy.klade.lv/
*/
class TanTanFlickrDisplayFancyBox extends TanTanFlickrDisplayBase {
	function headTags() {
		wp_enqueue_script('jquery');
		wp_print_scripts();
		echo '<script>$ = jQuery;</script>'; // careful about conflicts with other libraries!
		echo '<script src="'.TANTAN_DISPLAY_LIBRARY_PATH.'/fancybox/jquery.fancybox.js" type="text/javascript"></script>';
		echo '<script type="text/javascript">jQuery(function($) { $("a.tt-flickr").fancybox(); });</script>';
	}
	function href($photo, $album=null, $prefix='') {
		return $photo['sizes']['Medium']['source'];
	}	
}

/*
	Facebox: http://famspam.com/facebox
*/
class TanTanFlickrDisplayFaceBox extends TanTanFlickrDisplayBase {
	function headTags() {
		wp_enqueue_script('jquery');
		wp_print_scripts();
		echo '<script>$ = jQuery;</script>'; // careful about conflicts with other libraries!
		echo '<link href="'.TANTAN_DISPLAY_LIBRARY_PATH.'/facebox/facebox.css" media="screen" rel="stylesheet" type="text/css"/>';
		echo '<script src="'.TANTAN_DISPLAY_LIBRARY_PATH.'/facebox/facebox.js" type="text/javascript"></script>';
		echo '<script type="text/javascript">jQuery(function($) { $("a.tt-flickr").facebox(); });</script>';
	}
	function href($photo, $album=null, $prefix='') {
		return $photo['sizes']['Medium']['source'];
	}	
}

/*
	jQuery lightBox: http://leandrovieira.com/projects/jquery/lightbox/
*/
class TanTanFlickrDisplayJQueryLightboxBox extends TanTanFlickrDisplayBase {
	function headTags() {
		wp_enqueue_script('jquery');
		wp_print_scripts();
		echo '<script>$ = jQuery;</script>'; // careful about conflicts with other libraries!
		echo '<link href="'.TANTAN_DISPLAY_LIBRARY_PATH.'/jquery-lightbox/css/jquery.lightbox.css" media="screen" rel="stylesheet" type="text/css"/>';
		echo '<script src="'.TANTAN_DISPLAY_LIBRARY_PATH.'/jquery-lightbox/js/jquery.lightbox.js" type="text/javascript"></script>';
		echo '<script type="text/javascript">jQuery(function($) { $("a.tt-flickr").lightBox(); });</script>';
	}
	function href($photo, $album=null, $prefix='') {
		return $photo['sizes']['Medium']['source'];
	}	
}

/*
	FancyZoom: http://www.cabel.name/2008/02/fancyzoom-10.html   (not a jQuery plugin)
*/
class TanTanFlickrDisplayFancyZoom extends TanTanFlickrDisplayBase {
	function headTags() {
		echo '<script src="'.TANTAN_DISPLAY_LIBRARY_PATH.'/fancyzoom/js-global/FancyZoom.js" type="text/javascript"></script>';
		echo '<script src="'.TANTAN_DISPLAY_LIBRARY_PATH.'/fancyzoom/js-global/FancyZoomHTML.js" type="text/javascript"></script>';
	}
	function footer() {
		echo '<script type="text/javascript">setupZoom();</script>';
	}
	function href($photo, $album=null, $prefix='') {
		return $photo['sizes']['Medium']['source'];
	}	
}
// comment out the line below, and replace it with one of these...
//class TanTanFlickrDisplay extends TanTanFlickrDisplayFancyBox {};
//class TanTanFlickrDisplay extends TanTanFlickrDisplayFaceBox {};
//class TanTanFlickrDisplay extends TanTanFlickrDisplayJQueryLightboxBox {};
//class TanTanFlickrDisplay extends TanTanFlickrDisplayFancyZoom {};

// this is the default
class TanTanFlickrDisplay extends TanTanFlickrDisplayBase {}; 

add_action('wp_head', create_function('', 'TanTanFlickrDisplay::headTags();'));
add_action('wp_footer', create_function('', 'TanTanFlickrDisplay::footer();'));
?>