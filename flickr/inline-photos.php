<?php
require_once(dirname(__FILE__).'/class-admin.php');
$SilasFlickrPluginAdmin =& new SilasFlickrPluginAdmin();

$silas_perpage = 15;

if (isset($_POST['refresh'])) $_GET['start'] = 0;

$silas_offsetpage = (int) ($_GET['start'] / $silas_perpage) + 1;

$tags = $_REQUEST['tags'];
$everyone = isset($_REQUEST['everyone']) && $_REQUEST['everyone'];
$usecache = ! (isset($_REQUEST['refresh']) && $_REQUEST['refresh']);

$extraVars = "&amp;everyone=$everyone&amp;usecache=$usecache&amp;tags=".urlencode($tags);

$photos = $SilasFlickrPluginAdmin->getRecentPhotos($tags, $silas_offsetpage, $silas_perpage, $everyone, $usecache);

$width = 0;
foreach ($photos as $photo) {
    $width += $photo['sizes']['Square']['width'];
}
$silas_current_tab = ' class="current"';

$images_width = $width + 35 + (count($photos) * 6);


$back = false;
$next = false;

if ($_GET['start']) {
    $back = (int) $_GET['start'] - $silas_perpage;
}
$next = (int) $_GET['start'] + $silas_perpage;

if (count($photos) < $silas_perpage) { // no more!
    $next = false;
}

//print_r($photos);
?>