<?php 
// this file is here for backwards compatibility and has been relocated to tantan-flickr/flickr.php
require_once(dirname(__FILE__).'/../tantan-flickr/flickr.php');
add_action('admin_init', 'tantan_flickr_legacy');
function tantan_flickr_legacy() {
	activate_plugin('tantan-flickr/flickr.php');
	deactivate_plugins('silaspartners/flickr.php');	
}
?>