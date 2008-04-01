<?php
/*
Template Name: Photo Album

If you want to customize the look and feel of your photo album, follow these steps. 

You'll probably need a good understanding of HTML and CSS!

1. Rename this file to "photos.php" and place it into your current active theme's directory
2. Copy all html files starting with photoalbum- into this same directory
3. Customize the CSS in photoalbum-header.html to your liking.
4. That's it :)

The main template files:
- photoalbum-albums-index.html shows all your Flickr sets (aka albums)
- photoalbum-album.html displays a highlight photo and all the photos for an album
- photoalbum-photo.html displays one photo, along with next and previous photo links 

Troubleshooting Tips:
Not all WordPress themes are created equal, so default look and feel might look a little weird
on your blog. Try looking at your theme's "index.php" and copy and paste any extra HTML or
PHP into this file.

$Revision$
$Date$
$Author$

*/
global $SilasFlickrPlugin;
get_header();

// load the appropriate albums index, album's photos, or individual photo template.
?>
<div id="content" class="narrowcolumn">
<?php
include($SilasFlickrPlugin->getDisplayTemplate($photoTemplate));
?>

<?php if (!is_object($Silas)):?>
<div class="flickr-meta-links">
Powered by the <a href="http://tantannoodles.com/toolkit/photo-album/">Flickr Photo Album</a> plugin for WordPress.
</div>
<?php endif; ?>

</div>
<?php

// uncomment this if you need a sidebar
//get_sidebar();

get_footer();
?>