<?php
require_once(dirname(__FILE__).'/class-public.php');

class SilasFlickrPluginAdmin extends SilasFlickrPlugin {

    var $config = array();
    
    function SilasFlickrPluginAdmin() {
        //parent::SilasFlickrPlugin();
        add_action('admin_menu', array(&$this, 'addhooks'));
        add_action('activate_silaspartners/flickr.php', array(&$this, 'activate'));
        add_action('deactivate_silaspartners/flickr.php', array(&$this, 'deactivate'));
        add_action('load-upload.php', array(&$this, 'addPhotosTab'));
        if ($_GET['tantanActivate'] == 'photo-album') {
            $this->showConfigNotice();
        }
    }
    function activate() {
        wp_redirect('plugins.php?tantanActivate=photo-album');
        exit;
    }
    function showConfigNotice() {
        add_action('admin_notices', create_function('', 'echo \'<div id="message" class="updated fade"><p>The Flickr Photo Album plugin has been <strong>activated</strong>. <a href="options-general.php?page=silaspartners/flickr/class-admin.php">Configure the plugin &gt;</a></p></div>\';'));
    }

    function admin() {
    
        if (!is_writable(dirname(__FILE__).'/flickr-cache/')) {
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
            
        require_once(dirname(__FILE__).'/lib.flickr.php');
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

            if ($_POST['synidcateoff'] || strlen($_POST['baseurl']) <= 0) {
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
        
        }
        include(dirname(__FILE__).'/admin-options.html');
        
    }
    function uploading_iframe($src) {
        return '../wp-content/plugins/silaspartners/flickr/'.$src;
    }
    
    function addhooks() {
        add_options_page('Photo Album', 'Photo Album', 10, __FILE__, array(&$this, 'admin'));
        if (version_compare(get_bloginfo('version'), '2.1', '<')) {
            add_filter('uploading_iframe_src', array(&$this, 'uploading_iframe'));
        }
        $this->version_check();
    }
    function version_check() {
        global $TanTanVersionCheck;
        if (is_object($TanTanVersionCheck)) {
            $data = get_plugin_data(dirname(__FILE__).'/../flickr.php');
            $TanTanVersionCheck->versionCheck(200, $data['Version']);
        }
    }
    function addPhotosTab() {
        add_filter('wp_upload_tabs', array(&$this, 'wp_upload_tabs'));
        add_action('admin_print_scripts', array(&$this, 'upload_tabs_scripts'));
        //add_action('upload_files_silas_flickr', array(&$this, 'upload_files_silas_flickr'));
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
            'silas_flickr' => array('Photos (Flickr)', 'upload_files', array(&$this, 'photosTab'), array(100, 10), $args),
            'silas_flickr_album' => array('Albums (Flickr)', 'upload_files', array(&$this, 'albumsTab'), 0, $args)
            );
        return array_merge($array, $tab);
    }
    function upload_tabs_scripts() {
        include(dirname(__FILE__).'/admin-tab-head.html');
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

        
        include(dirname(__FILE__).'/admin-photos-tab.html');
    }
    function albumsTab() {
        $usecache = ! (isset($_REQUEST['refresh']) && $_REQUEST['refresh']);
        $albums = $this->getRecentAlbums($usecache);
        include(dirname(__FILE__).'/admin-albums-tab.html');
    }
    
    
   
    // cleanup after yourself
    function deactivate() {
        require_once(dirname(__FILE__).'/lib.flickr.php');
        $flickr = new SilasFlickr();
        if (is_writable(dirname(__FILE__).'/flickr-cache/')) {
            $flickr->clearCache();
        }
        if ($flickr->cache == 'db') {
            global $wpdb;
            $wpdb->query("DELETE FROM $flickr->cache_table;");
        }
    }


}
?>