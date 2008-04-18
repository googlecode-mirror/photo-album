<?php
/*
$Revision$
$Date$
$Author$
*/
class SilasFlickrPlugin {

    var $config = array();
    
    function SilasFlickrPlugin() {
    }

    function getRecentPhotos($tags='', $offsetpage=0, $max=15, $everyone=false, $usecache=true) {
        $auth_token = get_option('silas_flickr_token');
        $baseurl = get_option('silas_flickr_baseurl');
        $linkoptions = get_option('silas_flickr_linkoptions');
        if ($auth_token) {
            require_once(dirname(__FILE__).'/lib.flickr.php');
            $flickr = new SilasFlickr();
            $flickr->setToken($auth_token);
            $flickr->setOption(array(
                'hidePrivatePhotos' => get_option('silas_flickr_hideprivate'),
            ));
            $user = $flickr->auth_checkToken();
            $nsid = $user['user']['nsid'];
			if (!$usecache) $flickr->clearCacheStale('search');
            if (!$tags && $everyone) {
                $photos = $flickr->getRecent(NULL, $max, $offsetpage);
            } else {
                $photos = $flickr->search(array(
                    'tags' => ($tags ? $tags : ''),
                    'user_id' => ($everyone ? '' : $nsid),
					'license' => ($everyone ? SILAS_FLICKR_PUBLIC_LICENSE : ''),
                    'per_page' => $max,
                    'page' => $offsetpage,
                ));
            }
            //if (!$usecache) $flickr->doneClearCache();
            //$this->_silas_cacheExpire = -1;
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
    function getRecentAlbums($usecache=true) {
        $auth_token = get_option('silas_flickr_token');
        $baseurl = get_option('silas_flickr_baseurl');
        if ($auth_token) {
            require_once(dirname(__FILE__).'/lib.flickr.php');
            $flickr = new SilasFlickr();
            $flickr->setToken($auth_token);
            $flickr->setOption(array(
                'hidePrivatePhotos' => get_option('silas_flickr_hideprivate'),
            ));
            $user = $flickr->auth_checkToken();
            $nsid = $user['user']['nsid'];
            //$usecache = false;
            //if (!$usecache) $flickr->startClearCache(); // blah, buggy as hell
            //$flickr->_silas_cacheExpire = 300; // cache just 5 mins
            //$flickr->_silas_cacheExpire = 3600; // cache one hour
            $albums = $flickr->manualSort($flickr->getAlbums(), get_option('silas_flickr_albumorder'));
            foreach ($albums as $key => $album) {
                $albums[$key]['sizes'] = $flickr->getPhotoSizes($album['primary']);
            }
            //if (!$usecache) $flickr->doneClearCache();
            //$this->_silas_cacheExpire = -1;
            return $albums;
        } else {
            return array();
        }
    }

    function getShortCodeHTML($attribs=false, $content=false) {
    	global $post;
    	extract(shortcode_atts(array(
    		'album' => null,
    		'tag'     => null,
    		'num'     => 5,
    		'size'    => 'Square',
    		'scale'   => 1,
    	), $attribs));
		$error = '';
		if (!in_array($size, array('Square', 'Thumbnail', 'Small', 'Medium', 'Large', 'Original'))) { 
			$error = "Unknown size: $size.";
			$size = 'Square'; 
		}
    	$key = "flickr-$album-$tag-$max-$size";
    	if ($html = get_post_meta($post->ID, $key, true)) {
    	    return $html;
    	} else {
    	    $html = '';
    	}
    	// grab the flickr photos
    	$photos = array();
    	
    	$auth_token = get_option('silas_flickr_token');
        $baseurl = get_option('silas_flickr_baseurl');
        $linkoptions = get_option('silas_flickr_linkoptions');
        $albumData = array();
        $photos = array();
        if ($auth_token) {
            require_once(dirname(__FILE__).'/lib.flickr.php');
            $flickr = new SilasFlickr();
            $flickr->setToken($auth_token);
            $flickr->setOption(array(
                'hidePrivatePhotos' => get_option('silas_flickr_hideprivate'),
            ));
            $user = $flickr->auth_checkToken();
            $nsid = $user['user']['nsid'];
        
            if ($album) {
                $albumData = $flickr->getAlbum($album);
                $photos = $flickr->getPhotos($album);
            } elseif ($tag) {
                $photos = $flickr->getPhotosByTags($tag);
            }
        } else {
            $html .= '<p class="error">Error: Flickr plugin is not setup!</p>';
        }

    	if (count($photos)) {
            if (file_exists(TEMPLATEPATH  . '/photoalbum-resources.php')) {
    			require_once(TEMPLATEPATH . '/photoalbum-resources.php');
    		} else {
    			require_once(dirname(__FILE__) . '/photoalbum-resources.php');
    		}
			$prefix = get_bloginfo('siteurl').SILAS_FLICKR_BASEURL;
            foreach (array_slice($photos, 0, $num) as $photo) {
                $html .= SilasFlickrDisplay::photo($photo, array(
                    'size' => $size,
                    'album' => $albumData,
                    'scale' => $scale,
					'prefix' => $prefix
                ));
            }
    	} // if count photos
    	$html = '<div class="flickr-photos">'.($error ? ('<p class="error">'.$error.'</p>') : '').$html.'</div>';
    	if (!update_post_meta($post->ID, $key, $html)) add_post_meta($post->ID, $key, $html);
        return $html;
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
    
        if (!isset($_SERVER['_SILAS_FLICKR_REQUEST_URI'])) {
            return;
        }
        $auth_token = get_option('silas_flickr_token');
        
        $photoTemplate = 'error.html';
        if ($auth_token) {
            require_once(dirname(__FILE__).'/lib.flickr.php');
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
					if ($photo['visibility']['ispublic'] <= 0) {
						$photo = array(); // sorry, no private photos
					}
                    add_action('wp_head', array(&$this, 'meta_noindex'));
                } else { // just an individual photo
                    $context = $flickr->getContext($request['photo']);
                }
                $photo = $flickr->getPhoto($request['photo']);
                if($flickr->getOption('hidePrivatePhotos') && ($photo['visibility']['ispublic'] <= 0)) {
                    $photo = array();
                } else {
                    $sizes = $flickr->getPhotoSizes($request['photo']);
                    $comments = $flickr->getComments($request['photo']);
                }
                
                $user = $flickr->auth_checkToken();
                $nsid = $user['user']['nsid'];
				if ($request['group'] && !SILAS_FLICKR_DISPLAYGROUPS) {
					$message = "Sorry, this feature is not enabled.";
                    $photoTemplate = 'error.html';
				} elseif ($photo['owner']['nsid'] != $nsid) {
 					if (((int) $photo['license'] > 0) && $photo['usage']['canblog']) {
                    	$owner = $flickr->people_getInfo($photo['owner']['nsid']);
		                $photoTemplate = 'photoalbum-photo.html';
					} else {
						$message = "This photo is not available. ";
						if ($photo['urls']['url'][0]['_content']) $message .= '<a href="'.$photo['urls']['url'][0]['_content'].'">View this photo at Flickr</a>';
						
						$photoTemplate = 'error.html';
					}
                } else {
	                $photoTemplate = 'photoalbum-photo.html';
				}
                
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
				if (!SILAS_FLICKR_DISPLAYGROUPS) {
                    $message = "Sorry, this feature is not enabled.";
                    $photoTemplate = 'error.html';
				} else {
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
				}
                add_action('wp_head', array(&$this, 'meta_noindex'));
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
                $hideAlbums = get_option('silas_flickr_hidealbums');
                // remove albums marked as hidden
                if (is_array($hideAlbums)) foreach ($albums as $k=>$a) if (in_array($a['id'], $hideAlbums)) unset($albums[$k]);

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
        if (file_exists(TEMPLATEPATH  . '/photoalbum-resources.php')) {
			require_once(TEMPLATEPATH . '/photoalbum-resources.php');
		} else {
			require_once(dirname(__FILE__) . '/photoalbum-resources.php');
		}
        if (file_exists(TEMPLATEPATH . '/photos.php')) {
            include (TEMPLATEPATH . '/photos.php');
        } else {
            include(dirname(__FILE__).'/view.php');
        }
        if (count($errorMessages) > 0) {
            echo "<!-- \n". $errorMessages . "\n-->\n";
        }
        

		$flickr->clearCacheStale();
        exit;
        
    }
    function setPageTitle($title) {
        $this->config['title'] = $title;
    }
    function wp_title($title) {
        return ' '.($this->config['title'] ? $this->config['title'] : $title).' ';
    }
    
    function meta_noindex() {
        echo '<meta name="robots" content="noindex" />';
    }
    function header() {
        $user = get_option('silas_flickr_user');
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
            return(dirname(__FILE__).'/'.$file);
        }
    }
    function parse_query(&$query) {
		$query->is_404 = false;
		$query->did_permalink = false;
    }
    function request($query_vars) {
        $query_vars['error'] = false;
        return $query_vars;
    }
}
?>