<?php
/*
$Revision$
$Date$
$Author$
*/
class TanTanFlickrPlugin {

    var $config = array();
    var $request = array();
    
    function TanTanFlickrPlugin() {
    }

	function getUser() {
		return get_option('silas_flickr_user');
	}



	/*
	 * Get a selection of random photos
	 */
	function getrandomPhotos($tags='', $num=15, $everyone=false, $usecache=true) {
		$auth_token = get_option('silas_flickr_token');
		$baseurl = get_option('silas_flickr_baseurl');
		$linkoptions = get_option('silas_flickr_linkoptions');
		if ($auth_token) {
			require_once(dirname(__FILE__).'/lib.flickr.php');
			$flickr = new TanTanFlickr();
			$flickr->setToken($auth_token);
			$flickr->setOption(array(
					'hidePrivatePhotos' => get_option('silas_flickr_hideprivate'),
			));
			$user = $flickr->auth_checkToken();
			$nsid = $user['user']['nsid'];
			if (!$usecache) $flickr->clearCacheStale('random'); 

			//check cache
			//TODO: change cache to shorter time?
		  if ($cache = $flickr->getObjCache('random', "$num-$tags")) {
				return $cache;
			}
			$extra = '';#TODO: set tags
			// Find number of photos 
			$query = $flickr->people_getPublicPhotos( $nsid, $extra, 1, 0);
			$total_photos = $query['total'];
			//get details about $bum photos
			for($i=0; $i < $num; $i++){
				$this_photo = $flickr->people_getPublicPhotos( $nsid, $extra, 1, rand(0,$total_photos));
				if (is_array($this_photo['photo'])) foreach ($this_photo['photo'] as $photo) {
					$row = array();
					$row['id'] = $photo['id'];
					$row['title'] = $photo['title'];
					$row['sizes'] = $flickr->getPhotoSizes($photo['id']);
					$row['pagename2'] = $flickr->_sanitizeTitle($photo['title']);
					$row['pagename'] = $row['pagename2'] . '.html';
					$photos[$photo['id']] = $row;
				}
			}

			shuffle($photos);
			//set cache
			$flickr->setObjCache('random', "$num-$tags", $photos);
			return $photos;
		} else {
			return array();
		}
	}

    function getRecentPhotos($tags='', $offsetpage=0, $max=15, $everyone=false, $usecache=true) {
        $auth_token = get_option('silas_flickr_token');
        $baseurl = get_option('silas_flickr_baseurl');
        $linkoptions = get_option('silas_flickr_linkoptions');
        if ($auth_token) {
            require_once(dirname(__FILE__).'/lib.flickr.php');
            $flickr = new TanTanFlickr();
            $flickr->setToken($auth_token);
            $flickr->setOption(array(
                'hidePrivatePhotos' => get_option('silas_flickr_hideprivate'),
            ));
            $user = $flickr->auth_checkToken();
            $nsid = $user['user']['nsid'];
			if (!$usecache) $flickr->clearCacheStale('search'); // should probably not blanket clear out everything in 'search'
            if (!$tags && $everyone) {
				if (!$usecache) $flickr->clearCacheStale('getRecent');
                $photos = $flickr->getRecent(NULL, $max, $offsetpage);
            } else {
                $photos = $flickr->search(array(
                    'tags' => ($tags ? $tags : ''),
                    'user_id' => ($everyone ? '' : $nsid),
										'license' => ($everyone ? TANTAN_FLICKR_PUBLIC_LICENSE : ''),
                    'per_page' => $max,
                    'page' => $offsetpage,
                ));
            }
            //if (!$usecache) $flickr->doneClearCache();
            //$this->_silas_cacheExpire = -1;
            if ($everyone || !$baseurl || $linkoptions) {
                foreach ($photos as $k => $photo) {
                    $photos[$k]['info'] = $flickr->getPhoto($photo['id']);
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
            $flickr = new TanTanFlickr();
            $flickr->setToken($auth_token);
            $flickr->setOption(array(
                'hidePrivatePhotos' => get_option('silas_flickr_hideprivate'),
            ));
            $user = $flickr->auth_checkToken();
            $nsid = $user['user']['nsid'];
            if (!$usecache) $flickr->clearCacheStale('photosets.getList');
            //$usecache = false;
            //if (!$usecache) $flickr->startClearCache(); // blah, buggy as hell
            //$flickr->_tantan_cacheExpire = 300; // cache just 5 mins
            //$flickr->_tantan_cacheExpire = 3600; // cache one hour
            $albums = $flickr->manualSort($flickr->getAlbums(), get_option('silas_flickr_albumorder'));
            foreach ($albums as $key => $album) {
                $albums[$key]['sizes'] = $flickr->getPhotoSizes($album['primary']);
            }
            //if (!$usecache) $flickr->doneClearCache();
            //$this->_tantan_cacheExpire = -1;
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
    	$key = "flickr-$album-$tag-$num-$size";
    	if ($html = get_post_meta($post->ID, $key, true)) {
    	    return $html;
    	} else {
    	    $html = '';
    	}
    	// grab the flickr photos
    	$photos = array();
    	
    	$auth_token = get_option('silas_flickr_token');
        $baseurl = get_option('silas_flickr_baseurl');
		$baseurl_pre = get_option('silas_flickr_baseurl_pre');
        $linkoptions = get_option('silas_flickr_linkoptions');
        $albumData = array();
        $photos = array();
        if ($auth_token) {
            require_once(dirname(__FILE__).'/lib.flickr.php');
            $flickr = new TanTanFlickr();
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
    			require_once(dirname(__FILE__) . '/../templates/photoalbum-resources.php');
    		}
			$prefix = get_bloginfo('siteurl').'/'.substr($baseurl, strlen($baseurl_pre));
			$linkoptions = get_option('silas_flickr_linkoptions');
			
            foreach (array_slice($photos, 0, $num) as $photo) {
                $html .= TanTanFlickrDisplay::photo($photo, array(
                    'size' => $size,
                    'album' => $albumData,
                    'scale' => $scale,
					'prefix' => $prefix,
					'linkoptions' => $linkoptions
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
    
        if (!isset($_SERVER['_TANTAN_FLICKR_REQUEST_URI'])) {
            return;
        }
        $auth_token = get_option('silas_flickr_token');
        
        $photoTemplate = 'error.html';
        if ($auth_token) {
            require_once(dirname(__FILE__).'/lib.flickr.php');
            $flickr = new TanTanFlickr();
            $flickr->setToken($auth_token);
            $flickr->setOption(array(
                'hidePrivatePhotos' => get_option('silas_flickr_hideprivate'),
            ));
            
            $parts = explode('/', substr($_SERVER['_TANTAN_FLICKR_REQUEST_URI'], strlen($_SERVER['REQUEST_URI'])));
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
				if ($request['group'] && !TANTAN_FLICKR_DISPLAYGROUPS) {
					$message = "Sorry, this feature is not enabled.";
                    $photoTemplate = 'error.html';
				} elseif ($photo['owner']['nsid'] != $nsid) {
 					if (((int) $photo['license'] > 0) && $photo['usage']['canblog']) {
                    	$owner = $flickr->people_getInfo($photo['owner']['nsid']);
		                $photoTemplate = 'photoalbum-photo.html';
					} else {
						$message = "This photo is not available. ";
						if (is_array($photo['urls'])) $message .= '<a href="'.array_pop($photo['urls']).'">View this photo at Flickr</a>';
						
						$photoTemplate = 'error.html';
					}
                } else {
	                $photoTemplate = 'photoalbum-photo.html';
				}
                
            } elseif ($request['album']) {
                $album = $flickr->getAlbum($request['album']);
				$user = $flickr->auth_checkToken();
                $nsid = $user['user']['nsid'];
				if ($album['owner'] != $nsid) {
					$message = "This album is not available. ".
						'<a href="http://www.flickr.com/photos/'.$album['owner'].'/sets/'.$album['id'].'/">View this album on Flickr</a>';

					$photoTemplate = 'error.html';
				} elseif (isset($request['tags'])) {
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
				if (!TANTAN_FLICKR_DISPLAYGROUPS) {
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
            $this->request = $request;
            if ($request['group']) {
                add_action('wp_head', array(&$this, 'meta_noindex'));
            }
            add_action('wp_head', array(&$this, 'rss_feed'));
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
			require_once(dirname(__FILE__) . '/../templates/photoalbum-resources.php');
		}
        if (file_exists(TEMPLATEPATH . '/photoalbum-index.php')) {
            include (TEMPLATEPATH . '/photoalbum-index.php');
        } elseif (file_exists(dirname(__FILE__) . '/../templates/photoalbum-index.php')) {
            include (dirname(__FILE__) . '/../templates/photoalbum-index.php');
        } elseif (file_exists(TEMPLATEPATH . '/photos.php')) {
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
    function rss_feed() {
        $user = get_option('silas_flickr_user');
        if ($this->request['album']) {
            echo '<link rel="alternate" type="application/atom+xml" title="Flickr Album Feed" href="http://api.flickr.com/services/feeds/photoset.gne?set='.$this->request['album'].'&nsid='.$user['user']['nsid'].'" />';
        }
        if ($this->request['group']) {
            echo '<link rel="alternate" type="application/atom+xml" title="Flickr Group Feed" href="http://api.flickr.com/services/feeds/groups_discuss.gne?id='.$this->request['group'].'" />';
        }
        

    }
    function meta_noindex() {
        echo '<meta name="robots" content="noindex" />';
    }
    function header() {
        $user = get_option('silas_flickr_user');
        include($this->getDisplayTemplate('photoalbum-header.html'));
    }
    function footer() {
        if (function_exists('current_user_can') && current_user_can('edit_pages')) {
            $showClearCache = true;
        }
        include($this->getDisplayTemplate('photoalbum-footer.html'));
    }
    function getDisplayTemplate($file) {
        if (file_exists(TEMPLATEPATH . '/'.$file)) {
            return (TEMPLATEPATH . '/'.$file);
        } else {
            return(dirname(__FILE__).'/../templates/'.$file);
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
class SilasFlickrPlugin extends TanTanFlickrPlugin {}; // backwards compatibility
?>
