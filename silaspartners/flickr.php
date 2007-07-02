<?php
/*
Plugin Name: Flickr Photo Gallery 
Plugin URI: http://www.tantannoodles.com/toolkit/photo-album/
Description: This plugin will retrieve your Flickr photos and allow you to easily add your photos to your posts. <a href="options-general.php?page=silaspartners/flickr/class-admin.php">Configure...</a>
Author: Silas Partners (Joe Tan)
Version: 0.92
Author URI: http://www.silaspartners.com/

Copyright (C) 2007  Silas Partners

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA

Change Log: http://code.google.com/p/photo-album/wiki/ChangeLog

$Revision$
$Date$
*/

if (ereg('/wp-admin/', $_SERVER['REQUEST_URI'])) { // just load in admin
    require_once(dirname(__FILE__).'/flickr/class-admin.php');
    $SilasFlickrPluginAdmin =& new SilasFlickrPluginAdmin();
    
} else {
    $baseurl = get_option('silas_flickr_baseurl');
    if ($baseurl) {
        if (strpos($_SERVER['REQUEST_URI'], $baseurl) === 0) {
            $_SERVER['_SILAS_FLICKR_REQUEST_URI'] = $_SERVER['REQUEST_URI'];
            $_SERVER['REQUEST_URI'] = $baseurl;
        
            require_once(dirname(__FILE__).'/flickr/class-public.php');
            $SilasFlickrPlugin =& new SilasFlickrPlugin();
        
            status_header(200); // ugly, just force a 200 status code
            add_filter('request', array(&$SilasFlickrPlugin, 'request'));
            add_action('parse_query', array(&$SilasFlickrPlugin, 'parse_query'));
            add_action('template_redirect', array(&$SilasFlickrPlugin, 'template'));
        } elseif (strpos($_SERVER['REQUEST_URI'].'/', $baseurl) === 0) {
            header('location: http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'/');
            exit;
        }
    }
}
if (get_option('silas_flickr_showbadge')) { // load sidebar widget
    add_action('plugins_loaded', create_function('', 'require_once(dirname(__FILE__)."/flickr/widget.php"); $GLOBALS[SilasFlickrWidget] =& new SilasFlickrWidget();'));
}

?>