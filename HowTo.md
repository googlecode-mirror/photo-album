# Code Snippets #

## Retrieve Recent Photos ##

Retrieve recent photos. Could be used as a photo stream or just display your most recent photos uploaded. Default parameters are shown in the getRecentPhotos() function call.

```
<?php
require_once(dirname(__FILE__).'/../../plugins/tantan-flickr/flickr/class-public.php');
$TanTanFlickrPlugin =& new TanTanFlickrPlugin();
$photos = $TanTanFlickrPlugin->getRecentPhotos($tags='', $offsetpage=0, $max=15, $everyone=false);
?>
```



## Get recent photos and randomly display some ##

This code block retrieves your 40 most recent photos, randomizes the order, and then displays 20 photos. Note that each photo links to "/photos/", and will probably have to be updated to reflect your own settings.

```
<?php
require_once(dirname(__FILE__).'/../../plugins/tantan-flickr/flickr/class-public.php');
$TanTanFlickrPlugin =& new TanTanFlickrPlugin();
$photos = $TanTanFlickrPlugin->getRecentPhotos('', 0, 40);
shuffle($photos);
?>
<?php foreach (array_slice($photos, 0, 20) as $photo):?>
<a href="/photos/photo/<?php echo $photo['id'];?>/<?php echo $photo['pagename'];?>"
class="photo"><img src="<?php echo $photo['sizes']['Square']['source'];?>"
width="<?php echo $photo['sizes']['Square']['width'];?>" height="<?php echo $photo['sizes']['Square']['height'];?>" /></a>
<?php endforeach;?>
```


## Test if you're in a Flickr gallery page ##

Use this snippet to see if your inside a Flickr gallery page from within another WordPress template.

```
global $TanTanFlickrPlugin;
if (is_object($TanTanFlickrPlugin)) {
    // ... do stuff ...
}

```