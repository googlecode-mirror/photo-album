<?php
/*
Plugin Name: Flickr Sidebar widget
Description: Adds a sidebar widget to display your recent Flickr photos
Author: Silas Partners (Joe Tan)
Version: 0.1
Author URI: http://www.silaspartners.com/
*/

class SilasFlickrWidget {
    function SilasFlickrWidget () {
        if (function_exists('register_sidebar_widget')) {
            register_sidebar_widget('Flickr Sidebar', array(&$this, 'display'));
            register_widget_control('Flickr Sidebar', array(&$this, 'control'));
        }
        $options = get_option('silas_flickr_widget');
        if ($options['animate']) {
            add_action('wp_head', array(&$this, 'animationHeader'));
            add_action('wp_footer', array(&$this, 'animationFooter'));
        }
    }
    
    function control() {
        require_once(dirname(__FILE__).'/lib.flickr.php');
        $flickr = new SilasFlickr();
        $auth_token  = get_option('silas_flickr_token');
        $flickr->setToken($auth_token);
        $user = $flickr->auth_checkToken();

        
		$options = $newoptions = get_option('silas_flickr_widget');
		if ( $_POST['silas-flickr-submit'] ) {
			$newoptions['title'] = strip_tags(stripslashes($_POST['silas-flickr-title']));
			$newoptions['tags'] = strip_tags(stripslashes($_POST['silas-flickr-tags']));
			$newoptions['count'] = (int) $_POST['silas-flickr-count'];
			$newoptions['randomize'] = $_POST['silas-flickr-randomize'] ? true : false;
			$newoptions['animate'] = $_POST['silas-flickr-animate'] ? true : false;
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('silas_flickr_widget', $options);
		}

        include(dirname(__FILE__).'/widget-options.html');
    }
    
    function animationHeader() {
        global $SilasFlickrPlugin;
        if (!$SilasFlickrPlugin->config['useLightbox']) { // see if animation libraries are already loaded
            if (file_exists(TEMPLATEPATH . '/photoalbum-lightbox-header.html')) {
                include (TEMPLATEPATH . '/photoalbum-lightbox-header.html');
            } else {
                include(dirname(__FILE__).'/photoalbum-lightbox-header.html');
            }
        }
        if (file_exists(TEMPLATEPATH . '/widget-header.html')) {
            include (TEMPLATEPATH . '/widget-header.html');
        } else {
            include(dirname(__FILE__).'/widget-header.html');
        }
    }
    function animationFooter() {
        
    }
    
    function display($args) {
        global $SilasFlickrPlugin;
		
        extract($args);
        $defaults = array('count' => 10);
        $options = (array) get_option('silas_flickr_widget');
        $altPhotos = array();
        foreach ( $defaults as $key => $value )
			if ( !isset($options[$key]) )
				$options[$key] = $defaults[$key];
		if ($options['randomize']) {
		    $count = $options['count'] * 2;
		} else {
		    $count = $options['count'];
		}
		$photos = $SilasFlickrPlugin->getRecentPhotos($options['tags'], 0, $count);
		if ($options['randomize'] || $options['animate']) {
		    if ($options['randomize']) shuffle($photos);
		    if (count($photos) < $options['count']) {
		        $altPhotos = $photos;
		    } else {
    		    $altPhotos = array_slice($photos, $options['count']);
    		    $photos = array_slice($photos, 0, $options['count']);
		    }
		}
		echo $before_widget;
		if ($options['animate']) {
		    include(dirname(__FILE__).'/widget-display-animate.html');
		} else {
            include(dirname(__FILE__).'/widget-display.html');
		}
        echo $after_widget;
    }
    
}
$GLOBALS['SilasFlickrWidget'] =& new SilasFlickrWidget();
?>
