# Show Groups #

Add the following to the TOP of your wp-config.php, along with the other define statements:

```
define('TANTAN_FLICKR_DISPLAYGROUPS', true);
```

Once done, you'll be able to use the plugin to pull in and display Flickr groups you have joined. Your wp-config.php should look something like this.

```
<?php
define('TANTAN_FLICKR_DISPLAYGROUPS', true);

// ** MySQL settings ** //
define('DB_NAME', '...');
... etc ...
```

**Important** Make sure you only load photos from groups for which you have permission to re-display photos.