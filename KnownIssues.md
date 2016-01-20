# Important Details #

  * Your PHP setup will need to have the libcurl and XML libraries installed in order for this plugin to work.
  * This plugin may not work well if your WordPress blog is installed on a Microsoft IIS web server.
  * WordPress permalinks need to be enabled and set to something other than “Default”.
  * Please check the Flickr group if you are having problems with this plugin. Chances are somebody else encountered it before and a solution was posted to the forum.

# Common Issues #

## API Key Issues ##

Make sure your API key authentication type is set to **Desktop Application**. This is important.


---


## Fatal error: Call to undefined function: curl\_init() ##

You need to have the CURL PHP library installed. [See here for setup](http://us3.php.net/manual/en/curl.setup.php) instructions.


---


## Oops! Flickr can't find a valid callback URL. ##

This probably means your server can't communicate with the outside world, and more importantly, the Flickr.com servers. To verify that your server is able to make calls to Flickr.com, install [this PHP script](http://photo-album.googlecode.com/svn/branches/tests/network.php) in the webroot on your server, and open it in your browser. If it works, then you should see an "OK" message. Otherwise, you'll see an error, which means your server can't communicate with the Flickr.com servers.


---


## Infinite Redirects ##

You'll need to set your WordPress permalinks to something other than the "Default" settings. [See here for more information](http://codex.wordpress.org/Using_Permalinks)


---


## The look and feel of the photo page doesn't match my site ##

If the photo pages looks slightly off (eg missing sidebars, footers are in weird places), then you'll need to tweak the plugin template files to match your theme. [See here for more details](http://code.google.com/p/photo-album/wiki/Documentation#Look_and_Feel_Customization).