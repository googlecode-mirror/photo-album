<?php
$tmpPath = '/../../../..';
if (!file_exists(dirname(__FILE__).$tmpPath.'/wp-config.php')) {
$tmpPath = '/../../../../..';
    if (!file_exists(dirname(__FILE__).$tmpPath.'/wp-config.php')) {
        echo "Error: wp-config.php not found";
        exit;
    }
}
if (!isset($_GET['view']) ) {
    exit;
}
echo dirname(__FILE__).$tmpPath.'/wp-config.php';
require_once(dirname(__FILE__).$tmpPath.'/wp-config.php');
require_once(dirname(__FILE__).$tmpPath.'/wp-admin/admin-functions.php');
require_once(dirname(__FILE__).$tmpPath.'/wp-admin/admin-db.php');

get_currentuserinfo();

if ( !current_user_can('manage_categories') )
	die('-1');

function get_out_now() { exit; }

add_action('shutdown', 'get_out_now', -1);


require_once(dirname(__FILE__).'/lib.flickr.php');
$flickr = new SilasFlickr();
$auth_token  = get_option('silas_flickr_token');
$hideAlbums  = get_option('silas_flickr_hidealbums');
$hideGroups  = get_option('silas_flickr_hidegroups');
$groupOrder  = get_option('silas_flickr_grouporder');
$albumOrder  = get_option('silas_flickr_albumorder');
        
$flickr->setToken($auth_token);
$flickr->setOption(array(
    'hidePrivatePhotos' => get_option('silas_flickr_hideprivate'),
));
$user = $flickr->auth_checkToken();
$flickr->setUser($user);

if ($_GET['view'] == 'albums') { // load albums
    if (!is_array($hideAlbums)) $hideAlbums = array();
    include(dirname(__FILE__).'/admin-options-albums.html');
} elseif ($_GET['view'] == 'groups') { // load groups
    if (!is_array($hideGroups)) $hideGroups = array();
    include(dirname(__FILE__).'/admin-options-groups.html');
}
?>