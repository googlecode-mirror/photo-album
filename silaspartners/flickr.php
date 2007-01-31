<?php
/*
Plugin Name: Flickr Photo Gallery 
Plugin URI: http://www.tantannoodles.com/toolkit/photo-album/
Description: This plugin will retrieve your Flickr photos and allow you to easily add your photos to your posts. <a href="options-general.php?page=silaspartners/flickr.php">Configure...</a>
Author: Silas Partners (Joe Tan)
Version: 0.86
Author URI: http://www.silaspartners.com/

Copyright (C) 2006  Silas Partners

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

changelog:
- sidebar widget photos respect lightbox setting
- updated templates

0.85:
- users must now use their own API key. added supporting screens
- template handling is much more intelligent (don't need to copy all photoalbum-*.html templates to the same directory for customizations

0.86:
- compatibility with WordPress 2.1

*/

class SilasFlickrPlugin {

    var $config = array();
    
    function admin() {
    
        if (!is_writable(dirname(__FILE__).'/flickr/flickr-cache/')) {
            echo "<div class='wrap'>
            <h2>Permissions Error</h2>
            <p>This plugin requires that the directory <strong>".dirname(__FILE__)."/flickr/flickr-cache/</strong> be writable by the web server.</p> 
            <p>Please contact your server administrator to ensure the proper permissions are set for this directory. </p>
            </div>
            ";
            return;
        } elseif (!get_settings('permalink_structure')) {
            $error = "In order to view your photo album, your <a href='options-permalink.php'>WordPress permalinks</a> need to be set to something other than <em>Default</em>.";
        } elseif (!function_exists('curl_init')) {
            $error = "You do not have the required libraries to use this plugin. The PHP library <a href='http://us2.php.net/curl'>libcurl</a> needs to be installed on your server.";
        } elseif (!function_exists('xml_parser_create')) {
            $error = "You do not have the required libraries to use this plugin. The PHP library <a href='http://us2.php.net/xml'>xml</a> needs to be installed on your server.";
        }

        if ($_POST['action'] == 'savekey') {
            update_option('silas_flickr_apikey', $_POST['flickr_apikey']);
            update_option('silas_flickr_sharedsecret', $_POST['flickr_sharedsecret']);
            $message = "Saved API Key.";
        } elseif ($_POST['action'] == 'resetkey') {
            update_option('silas_flickr_apikey', false);
            update_option('silas_flickr_sharedsecret', false);
            $message = "API Key reset";
        }

        $flickr_apikey = get_option('silas_flickr_apikey');
        $flickr_sharedsecret = get_option('silas_flickr_sharedsecret');

        if ($flickr_apikey && $flickr_sharedsecret) {
            
        require_once(dirname(__FILE__).'/flickr/lib.flickr.php');
        $flickr = new SilasFlickr();

        if ($flickr->cache == 'db') {
            global $wpdb;
            $wpdb->query("
                CREATE TABLE IF NOT EXISTS `$flickr->cache_table` (
                    `request` CHAR( 35 ) NOT NULL ,
                    `response` TEXT NOT NULL ,
                    `expiration` DATETIME NOT NULL ,
                    INDEX ( `request` )
                ) TYPE = MYISAM");
        }
        
        if ($_POST['action'] == 'save') {
            $token = $flickr->auth_getToken($_POST['frob']);
            if ($token) {
                update_option('silas_flickr_token', $token);
            } else {
                $error = $flickr->getErrorMsg();
            }
        } elseif ($_POST['action'] == 'logout') {
            update_option('silas_flickr_token', '');
            $flickr->clearCache();
        } elseif ($_POST['action'] == 'savebase') {
            $url = parse_url(get_bloginfo('siteurl'));
            $baseurl = $url['path'] . '/' . $_POST['baseurl'];
            if (!ereg('.*/$', $baseurl)) $baseurl .= '/';

            if (strlen($_POST['baseurl']) <= 0) {
                $baseurl = false;
            }
            update_option('silas_flickr_baseurl_pre', $url['path'] . '/');
            update_option('silas_flickr_baseurl', $baseurl);
            
            update_option('silas_flickr_hideprivate', $_POST['hideprivate']);
            update_option('silas_flickr_showbadge', $_POST['showbadge']);
            update_option('silas_flickr_linkoptions', $_POST['linkoptions']);
        }
        
        
        $auth_token  = get_option('silas_flickr_token');
        $baseurl     = get_option('silas_flickr_baseurl');
        $baseurl_pre = get_option('silas_flickr_baseurl_pre');
        $hideprivate = get_option('silas_flickr_hideprivate');
        $showbadge   = get_option('silas_flickr_showbadge');
        $linkoptions = get_option('silas_flickr_linkoptions');
        $hideAlbums  = get_option('silas_flickr_hidealbums');
        $hideGroups  = get_option('silas_flickr_hidegroups');
        $groupOrder  = get_option('silas_flickr_grouporder');
        $albumOrder  = get_option('silas_flickr_albumorder');
        

        
        $flickrAuth = false;
    
        if (!$auth_token) {
            $frob = $flickr->getFrob();
            $error = $flickr->getErrorMsg();
            
            $flickrAuth = false;
        } else {
            $flickr->setToken($auth_token);
            $flickr->setOption(array(
                'hidePrivatePhotos' => get_option('silas_flickr_hideprivate'),
            ));
            $user = $flickr->auth_checkToken();
            if (!$user) { // get a new frob and try to re-authenticate
                $error = $flickr->getErrorMsg();
                update_option('silas_flickr_token', '');
                $flickr->setToken('');
                $frob = $flickr->getFrob();
            } else {
                $flickrAuth = true;
                $flickr->setUser($user);
                update_option('silas_flickr_user', $user);
            }
        }
        
        } // apikey check
        /*
            
        */
        if ($flickrAuth) { // passed authentication
        
        if ($_POST['action'] == 'clearcache') {
            if ($_POST['album'] == 'all') {
                if ($flickr->clearCache()) {
                    $message = "Successfully cleared the cache.";
                } else {
                    $error = "Cache clear failed. Try manually deleting the 'flickr-cache' directory.";
                }
            } else {
                $flickr->startClearCache();
                $albums = $flickr->getAlbums();
                $photos = $flickr->getPhotos($_POST['album']);
                $flickr->doneClearCache();
                $message = "Refreshed " . count($photos) . " photos in ".$albums[$_POST['album']]['title'].".";
            }
        } elseif ($_POST['action'] == 'savealbumsettings') {
            if (!is_array($_POST['hideAlbum'])) $_POST['hideAlbum'] = array();
            update_option('silas_flickr_hidealbums', $_POST['hideAlbum']);
            $hideAlbums = $_POST['hideAlbum'];
            
            if (!is_array($_POST['albumOrder'])) $_POST['albumOrder'] = array();
            asort($_POST['albumOrder']);
            update_option('silas_flickr_albumorder', $_POST['albumOrder']);
            $albumOrder = $_POST['albumOrder'];
            

            $message .= "Saved album settings. ";
            if (is_array($_POST['clearAlbum'])) {
                $flickr->startClearCache();
                foreach ($_POST['clearAlbum'] as $album_id) {
                    $photos = $flickr->getPhotos($album_id);
                }
                $flickr->doneClearCache();
                $message .= "Cleared cache for selected albums. ";

            }
        } elseif ($_POST['action'] == 'savegroupsettings') {
            if (!is_array($_POST['hideGroup'])) $_POST['hideGroup'] = array();
            update_option('silas_flickr_hidegroups', $_POST['hideGroup']);
            $hideGroups = $_POST['hideGroup'];
            
            if (!is_array($_POST['groupOrder'])) $_POST['groupOrder'] = array();
            asort($_POST['groupOrder']);
            update_option('silas_flickr_grouporder', $_POST['groupOrder']);
            $groupOrder = $_POST['groupOrder'];
            
            $message .= "Saved group settings. ";
            if (is_array($_POST['clearGroup'])) {
                $flickr->startClearCache();
                foreach ($_POST['clearGroup'] as $group_id) {
                    $photos = $flickr->getPhotosByGroup($group_id);
                }
                $flickr->doneClearCache();
                $message .= "Cleared cache for selected groups. ";
            }
        } 
        
        } // flickrAuth
        
        if (!is_array($hideAlbums)) $hideAlbums = array();
        if (!is_array($hideGroups)) $hideGroups = array();

        include(dirname(__FILE__).'/flickr/admin-options.html');
    }
    function uploading_iframe($src) {
        return '../wp-content/plugins/silaspartners/flickr/'.$src;
    }
    
    function getRecentPhotos($tags='', $offsetpage=0, $max=15, $everyone=false, $usecache=true) {
        $auth_token = get_option('silas_flickr_token');
        $baseurl = get_option('silas_flickr_baseurl');
        $linkoptions = get_option('silas_flickr_linkoptions');
        if ($auth_token) {
            require_once(dirname(__FILE__).'/flickr/lib.flickr.php');
            $flickr = new SilasFlickr();
            $flickr->setToken($auth_token);
            $user = $flickr->auth_checkToken();
            $nsid = $user['user']['nsid'];
            //$usecache = false;
            if (!$usecache) $flickr->startClearCache(); // blah, buggy as hell
            $flickr->_silas_cacheExpire = 300; // cache just 5 mins
            //$flickr->_silas_cacheExpire = 3600; // cache one hour
            if (!$tags && $everyone) {
                $photos = $flickr->getRecent(NULL, $max, $offsetpage);
            } else {
                $photos = $flickr->search(array(
                    'tags' => ($tags ? $tags : ''),
                    'user_id' => ($everyone ? '' : $nsid),
                    'per_page' => $max,
                    'page' => $offsetpage,
                ));
            }
            if (!$usecache) $flickr->doneClearCache();
            $this->_silas_cacheExpire = -1;
            if ($everyone || !$baseurl || $linkoptions) {
                foreach ($photos as $k => $photo) {
                    $photos[$k]['info'] = $flickr->photos_getInfo($photo['id']);
                }
            }
            return $photos;
        } else {
            return array();
        }
    }

    
    // redirect template to photos template
    function template() {
    global $Silas, $wp_query;
        $current = $wp_query->get_queried_object();
        if ($current->post_title) {
            $photoAlbumTitle = $current->post_title;
        } else {
            $photoAlbumTitle = 'Photo Gallery';
        }
    
        if (isset($_SERVER['_SILAS_FLICKR_REQUEST_URI'])) {
            $auth_token = get_option('silas_flickr_token');
            
            $photoTemplate = 'error.html';
            if ($auth_token) {
                require_once(dirname(__FILE__).'/flickr/lib.flickr.php');
                $flickr = new SilasFlickr();
                $flickr->setToken($auth_token);
                $flickr->setOption(array(
                    'hidePrivatePhotos' => get_option('silas_flickr_hideprivate'),
                ));
                
                $parts = explode('/', substr($_SERVER['_SILAS_FLICKR_REQUEST_URI'], strlen($_SERVER['REQUEST_URI'])));
                $request = array();
                $title = '';
                $i = 0;
                if (isset($_POST['refreshCache']) && $_POST['refreshCache']) {
                    $flickr->startClearCache();
                }
                while ($i < count($parts)) { // figgure out the album and/or photo to show
                    if ((($parts[$i] == 'tags') ||
                         ($parts[$i] == 'album') ||
                         ($parts[$i] == 'group') ||
                         ($parts[$i] == 'photo'))
                        && !ereg(".html$", $parts[$i])) $request[$parts[$i]] = $parts[$i+1];
                    $i += 1;
                }
                if ($request['photo']) {
                    if ($request['album']) { // within context of album
                        $album = $flickr->getAlbum($request['album']);
                        $context = $flickr->getContext($request['photo'], $request['album']);
                    } elseif ($request['group']) { // within context of group
                        $group = $flickr->getGroup($request['group']);
                        $context = $flickr->getContextByGroup($request['photo'], $request['group']);
                    } else { // just an individual photo
                        $context = $flickr->getContext($request['photo']);
                    }
                    $photo = $flickr->getPhoto($request['photo']);
                    $sizes = $flickr->getPhotoSizes($request['photo']);
                    $comments = $flickr->getComments($request['photo']);
                    
                    $user = $flickr->auth_checkToken();
                    $nsid = $user['user']['nsid'];
                    if ($photo['owner']['nsid'] != $nsid) {
                        $owner = $flickr->people_getInfo($photo['owner']['nsid']);
                    }
                    
                    $photoTemplate = 'photoalbum-photo.html';
                } elseif ($request['album']) {
                    $album = $flickr->getAlbum($request['album']);
                    if (isset($request['tags'])) {
                            $message = "Sorry, this feature is not supported";
                            $photoTemplate = 'error.html';
                        if ($request['tags']) {
                        } else { // return popular tags for an album
                            $message = "Sorry, this feature is not supported";
                            $photoTemplate = 'error.html';
                        }
                    } else {
                        $photos = $flickr->getPhotos($request['album']);
                        $photoTemplate = 'photoalbum-album.html';
                    }
                } elseif ($request['group']) {
                    $group = $flickr->getGroup($request['group']);
                    if (isset($request['tags'])) {
                        if ($request['tags']) {
                            $photos = $flickr->getPhotosByGroup($request['group'], $request['tags']);
                            $photoTemplate = 'photoalbum-tags-group.html';
                        } else { // return popular tags for a group
                            $message = "Sorry, this feature is not supported";
                            $photoTemplate = 'error.html';
                        }
                    } else {
                        $photos = $flickr->getPhotosByGroup($request['group']);
                        $photoTemplate = 'photoalbum-group.html';
                    }
                } elseif (isset($request['tags'])) {
                    if ($request['tags']) {
                        $photos = $flickr->getPhotosByTags($request['tags']);
                        
                        //$related = $flickr->getRelatedTags($request['tags']);
                        $photoTemplate = 'photoalbum-tags.html';
                    } else {
                        $tags = $flickr->getTags();
                        $photoTemplate = 'photoalbum-tagcloud.html';
                    }
                } else {
                    $title = $photoAlbumTitle;
                    $albums = $flickr->manualSort($flickr->getAlbums(), get_option('silas_flickr_albumorder'));
                    $groups = $flickr->manualSort($flickr->getGroups(), get_option('silas_flickr_grouporder'));
                    $hideAlbums = get_option('silas_flickr_hidealbums');
                    $hideGroups = get_option('silas_flickr_hidegroups');
                    // remove albums marked as hidden
                    if (is_array($hideAlbums)) foreach ($albums as $k=>$a) if (in_array($a['id'], $hideAlbums)) unset($albums[$k]);
                    if (is_array($hideGroups)) foreach ($groups as $k=>$g) if (in_array($g['id'], $hideGroups)) unset($groups[$k]);
                    

                    $tags = $flickr->getTags(25);
                    $photoTemplate = 'photoalbum-albums-index.html';
                }
                add_action('wp_head', array(&$this, 'header'));
                add_action('wp_footer', array(&$this, 'footer'));
                
                $errorMessages = $flickr->getErrorMsgs();
                if (is_object($Silas)) {
                    if ($request['photo']) {
                        if ($album) {
                            $Silas->addBreadCrumb($photoAlbumTitle, '../../../../');
                            $Silas->addBreadCrumb($album['title'], '../../'.$album['pagename']);
                        } else {
                            $Silas->addBreadCrumb($photoAlbumTitle, '../../');
                        }
                        $Silas->setPageTitle($photo['title']);
                    } elseif ($request['album']) {
                        $Silas->addBreadCrumb($photoAlbumTitle, '../../');
                        $Silas->setPageTitle($album['title']);
                    } else {
                        $Silas->setPageTitle($photoAlbumTitle);
                    }
                } else {
                    if ($request['photo']) {
                        $this->setPageTitle($photo['title']);
                    } elseif ($request['album']) {
                        $this->setPageTitle($album['title']);
                    } else {
                        $this->setPageTitle($photoAlbumTitle);
                    }
                    add_filter('wp_title', array(&$this, 'wp_title'));
                }
            } else {
                $message = "The photo album has not been configured.";
            }
            if (file_exists(TEMPLATEPATH . '/photos.php')) {
                include (TEMPLATEPATH . '/photos.php');
            } else {
                include(dirname(__FILE__).'/flickr/view.php');
            }
            if (count($errorMessages) > 0) {
                echo "<!-- \n". $errorMessages . "\n-->\n";
            }
            
            exit;
        }
    }
    function setPageTitle($title) {
        $this->config['title'] = $title;
    }
    function wp_title($title) {
        return $this->config['title'] ? $this->config['title'] : $title;
    }
    
    function header() {
        include($this->getDisplayTemplate('photoalbum-header.html'));
    }
    function footer() {
        global $userdata;
        if (isset($userdata->wp_capabilities['administrator']) && $userdata->wp_capabilities['administrator']) {
            $showClearCache = true;
        }
        include($this->getDisplayTemplate('photoalbum-footer.html'));
    }
    function getDisplayTemplate($file) {
        if (file_exists(TEMPLATEPATH . '/'.$file)) {
            return (TEMPLATEPATH . '/'.$file);
        } else {
            return(dirname(__FILE__).'/flickr/'.$file);
        }
    }
    
    // is the request coming in for photos
    function init() {
        $baseurl = get_option('silas_flickr_baseurl');
        if ($baseurl && (strpos($_SERVER['REQUEST_URI'], $baseurl) === 0)) {
            $_SERVER['_SILAS_FLICKR_REQUEST_URI'] = $_SERVER['REQUEST_URI'];
            $_SERVER['REQUEST_URI'] = $baseurl;

            status_header(200); // ugly, just force a 200 status code
            add_action('template_redirect', array(&$this, 'template'));
        }
    }
    
    function addhooks() {
        add_options_page('Photo Album', 'Photo Album', 10, __FILE__, array(&$this, 'admin'));
        if (!ereg('^2.1', get_bloginfo('version'))) {
            add_filter('uploading_iframe_src', array(&$this, 'uploading_iframe'));
        }
    }
    
    function addPhotosTab() {
        add_filter('wp_upload_tabs', array(&$this, 'wp_upload_tabs'));
        add_action('upload_files_silas_flickr', array(&$this, 'upload_files_silas_flickr'));
    }
    function wp_upload_tabs ($array) {
        /*
         0 => tab display name, 
        1 => required cap, 
        2 => function that produces tab content, 
        3 => total number objects OR array(total, objects per page), 
        4 => add_query_args
	*/
	    $args = array();
        if ($_REQUEST['tags']) $args['tags'] = $_REQUEST['tags'];
        if ($_REQUEST['everyone']) $args['everyone'] = 1;
        $tab = array(
            'silas_flickr' => array('Photos', 'upload_files', array(&$this, 'photosTab'), array(100, 10), $args)
            );
        return array_merge($array, $tab);
    }
    // gets called before tabs are rendered
    function upload_files_silas_flickr() {
        //echo 'upload_files_silas_flickr';
    }
    function photosTab() {
        $perpage = 20;
        $tags = $_REQUEST['tags'];
        //$offsetpage = (int) ($_GET['start'] / $perpage) + 1;
        $offsetpage = (int) $_GET['paged'];
        $everyone = isset($_REQUEST['everyone']) && $_REQUEST['everyone'];
        $usecache = ! (isset($_REQUEST['refresh']) && $_REQUEST['refresh']);

        $photos = $this->getRecentPhotos($tags, $offsetpage, $perpage, $everyone, $usecache);

        
        include(dirname(__FILE__).'/flickr/admin-photos-tab.html');
    }
    
    
    function load_widget() {
        require_once(dirname(__FILE__).'/flickr/widget.php');
    }
    
    function activate() {
    }
    
    // cleanup after yourself
    function deactivate() {
        require_once(dirname(__FILE__).'/flickr/lib.flickr.php');
        $flickr = new SilasFlickr();
        if (is_writable(dirname(__FILE__).'/flickr/flickr-cache/')) {
            $flickr->clearCache();
        }
        if ($flickr->cache == 'db') {
            global $wpdb;
            $wpdb->query("DELETE FROM $flickr->cache_table;");
        }
    }

    function SilasFlickrPlugin() {
        add_action('admin_menu', array(&$this, 'addhooks'));
        add_action('init', array(&$this, 'init'));
        if (get_option('silas_flickr_showbadge')) {
            add_action('plugins_loaded', array(&$this, 'load_widget'));
        }
        //add_action('activate_silaspartners/flickr.php', array(&$this, 'activate'));
        add_action('deactivate_silaspartners/flickr.php', array(&$this, 'deactivate'));
        add_action('load-upload.php', array(&$this, 'addPhotosTab'));
    }

}
$SilasFlickrPlugin =& new SilasFlickrPlugin();
$SilasFlickrWidget = false;
?>