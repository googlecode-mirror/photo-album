<?php
/*
This resource file defines some template tags used to draw out links to photos.
*/
class SilasFlickrDisplayBase {
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
		$html = '<a href="'.SilasFlickrDisplay::href($photo, $album, $prefix).'" '.
			'id="photo-'.$photo['id'].'" '.
			'title="'.htmlentities($photo['title']) . strip_tags($photo['description'] ? ' - '.$photo['description'] : '').'">'.
			SilasFlickrDisplay::image($photo, $size, $scale).
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
}
/*
Here is a sample of how to hook into other image display libraries such as FancyBox LightBox.

Note that this example requires that you include the jQuery library, and have the FancyBox library available.

jQuery: http://jquery.com/ (also included by default with WordPress)
FancyBox: http://fancy.klade.lv/

*/
/*** 
class SilasFlickrDisplayFancyBox extends SilasFlickrDisplayBase {
	function headTags() {
		wp_enqueue_script('jquery');
		wp_print_scripts();
		echo '<script>$ = jQuery;</script>'; // careful about conflicts with other libraries!
		echo '<script src="***** PATH TO FANCY BOX LIBRARY HERE ***** /jquery.fancybox.js" type="text/javascript"></script>';
		echo '<script type="text/javascript">jQuery(function($) { $("div.photos a").fancybox(); });</script>';
	}
	function href($photo, $album=null, $prefix='') {
		return $photo['sizes']['Medium']['source'];
	}
	
}
// comment out the line below, and replace it with this...
// class class SilasFlickrDisplay extends SilasFlickrDisplayFancyBox {};
*/
class SilasFlickrDisplay extends SilasFlickrDisplayBase {};

add_action('wp_head', create_function('', 'SilasFlickrDisplay::headTags();'));
add_action('wp_footer', create_function('', 'SilasFlickrDisplay::footer();'));
?>