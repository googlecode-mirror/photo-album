# Table of Contents #

  * [Installation](#Installation.md)
  * [Custom Photo Comments](#Custom_Photo_Comments.md)
  * [Popup Overlay Support](#Popup_Overlay_Support.md)
  * [Look and Feel Customization](#Look_and_Feel_Customization.md)

## Installation ##

  1. [Download](http://tantannoodles.com/toolkit/photo-album/) and unpack / unzip the archive
  1. Copy the entire "tantan-flickr" directory to your Wordpress plugins directory.
  1. Go into your Wordpress admin, click on the "Plugins" tab, and then activate "Flickr Photo Gallery".
  1. Click on the "Options" (or "Settings") tab, and click the "Photo Album" subtab to bring up the options screen.
  1. Follow the onscreen prompts to link your photo album to your Flickr account.
  1. Once your photo album is linked to your Flickr account, enter a URL where you want your photo album to appear.
  1. You're done! To view your photo album, just go to the URL you entered in the previous step. To insert a photo, just click on the Flickr icon  in your "Add media" bar when editing posts. Click on a photo, select a size, and a HTML snippet for that photo will appear in the post's textarea. **Cool!** If you're using WordPress 2.3 or older, then you should a new "Photos" tab next to your uploading tabs.


### Super Cache Compatibility ###

If you have the Super Cache plugin enabled, add the following code to your `.htaccess`, directly after the `WPSuperCache` section, but before the `WordPress` section. Replace `/photos/` on the first line with the path to your photo album.

```
<IfModule mod_rewrite.c>
RewriteCond %{REQUEST_URI} ^/photos/.*
RewriteCond %{DOCUMENT_ROOT}/wp-content/cache/supercache/%{HTTP_HOST}/$1/index.html -f
RewriteRule ^(.*) /wp-content/cache/supercache/%{HTTP_HOST}/$1/index.html [L]
</IfModule>
```


---


## Custom Photo Comments ##

The plugin supports a couple third party commenting systems, replacing the comments left by Flickr users. These are free web services that allow your visitors to leave comments on your photos, _without_ them having to login and register at Flickr. If you want to add your own commenting system, look inside the `photoalbum-comments.html` template file.

To use a third party commenting system, instead of default Flickr comments, add the following to the TOP of your `wp-config.php`:


For _[JS-Kit](http://www.js-kit.com)_, add this code: (see the [JS-Kit documentation](http://js-kit.com/comments/custom.html) for more options)

```
define('TANTAN_FLICKR_COMMENTS', 'js-kit');
```



For _[Disqus](http://www.disqus.com)_, add this code:

```
define('TANTAN_FLICKR_COMMENTS', 'disqus');
define('DISQUS_SHORT_NAME', 'XXXX'); 
```

Note: if you already are using the Disqus commenting system for your normal blog posts, then you don't need the second `DISQUS_SHORT_NAME` line. If you just want to use Disqus for your photo comments (and not your normal blog posts), then replace `XXXX` with the short name of your site. The short name is [listed on this admin screen](http://disqus.com/home/).


In the end, your wp-config.php should look something like this:

```
<?php
define('TANTAN_FLICKR_COMMENTS', 'disqus');

// ** MySQL settings ** //
define('DB_NAME', '...');
... etc ...
```


---


## Popup Overlay Support ##

The plugin comes bundled with 4 different display libraries, hosted on TanTanNoodles: Lightbox, Facebox, FancyZoom, and Fancybox. [Here's a quick demo of these display libraries](http://tantannoodles.com/flickr-demo/). These libraries are hosted for your convenience, you are **encouraged** to download these libraries and install them on your own server.

If you already have a display library installed on your site and would like to use that instead of a bundled library, then you will need to modify [this resource file](http://code.google.com/p/photo-album/source/browse/trunk/tantan-flickr/templates/photoalbum-resources.php) (found in the templates directory). Follow the examples in that file (starting at around line 134) to tweak the PHP to fit your site.

To use one of the hosted bundled display libraries, add the following to the TOP of your wp-config.php file:

For _[Thickbox](http://jquery.com/demo/thickbox/)_, add this code: (included by default with WP 2.5+)

```
define('TANTAN_DISPLAY_LIBRARY', 'thickbox');
```

For _[Light Box](http://leandrovieira.com/projects/jquery/lightbox/)_, add this code:

```
define('TANTAN_DISPLAY_LIBRARY', 'lightbox');
```

For _[Fancy Box](http://fancy.klade.lv/)_, add this code:

```
define('TANTAN_DISPLAY_LIBRARY', 'fancybox');

```

For _[Face Box](http://famspam.com/facebox)_, add this code:

```
define('TANTAN_DISPLAY_LIBRARY', 'facebox');
```

For _[Fancy Zoom](http://www.cabel.name/2008/02/fancyzoom-10.html)_, add this code:

```
define('TANTAN_DISPLAY_LIBRARY', 'fancyzoom');
```


In the end, your wp-config.php should look something like this:

```
<?php
define('TANTAN_DISPLAY_LIBRARY', 'facebox');

// ** MySQL settings ** //
define('DB_NAME', '...');
... etc ...
```

**Notes:**
  1. Depending on your blogs theme, you may need to use a little bit of JavaScript / CSS mojo and hack some code in your theme.
  1. Here's a page with [a list of all the various libraries](http://planetozh.com/projects/lightbox-clones/) you might want to use if you want to use your own.
  1. Take a peek inside the file photoalbum-resources.php to see how to hook up the plugin with a custom display library.

Different sizes for the popup image can be set by adding this to your `wp-config.php`.

```
define('TANTAN_DISPLAY_POPUP_SIZE', '--SIZE--');
```

Replace `--SIZE--` with either: Square, Thumbnail, Small, Medium, Large, Original.



---


## Look and Feel Customization ##

Not all WordPress themes are created equal. The first thing you'll want to do is make sure the `photoalbum-index.php` template file closely matches your own theme's `index.php` or `page.php`. In particular, make sure any DIV tags in your own theme's files are reflected in the `photoalbum-index.php`.

  1. All the templates are located inside a "templates" folder inside the tantan-flickr directory.
  1. If you just want to customize a particular template, just copy that template into your current theme directory. This ensures that when the plugin get's updated, your changes will not be lost.
  1. To completely customize the look and feel, copy all these template files (they all start with photoalbum-) into your current theme directory and modify as necessary.
  1. Modify the HTML and CSS in the template files to fit your own site. All the CSS are referenced from the template file photoalbum-header.html
  1. That's it!