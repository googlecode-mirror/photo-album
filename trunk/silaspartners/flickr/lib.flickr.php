<?php
/*
Copyright (C) 2007  Silas Partners

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
*/

// keys
define("SILAS_FLICKR_APIKEY", get_option('silas_flickr_apikey'));
define("SILAS_FLICKR_SHAREDSECRET", get_option('silas_flickr_sharedsecret'));

require_once(dirname(__FILE__)."/lib.phpFlickr.php");

class SilasFlickr extends silas_phpFlickr {
    var $_silas_apiKey;
    var $_silas_sharedSecret;
    var $_silas_user;
    var $_silas_useCache;
    var $_silas_errorCode;
    var $_silas_errorMsg;
    var $_silas_cacheExpire;
    var $_silas_options;
    
    function SilasFlickr() {
        $this->_silas_apiKey = SILAS_FLICKR_APIKEY;
        $this->_silas_sharedSecret = SILAS_FLICKR_SHAREDSECRET;
        $this->_silas_errorCode = array();
        $this->_silas_errorMsg = array();
        $this->_silas_cacheExpire = -1; //3600;
        $this->_silas_options = array();
        
        parent::silas_phpFlickr(SILAS_FLICKR_APIKEY, SILAS_FLICKR_SHAREDSECRET, false);
        if (SILAS_FLICKR_CACHEMODE == 'db') {
            global $wpdb; // hmm, might need to think of a better way of doing this
            $this->enableCache('db', $wpdb);
		} elseif (SILAS_FLICKR_CACHEMODE == 'false') {
			// no cache
        } else {
            $cacheDir = dirname(__FILE__).'/flickr-cache';
            if (!file_exists($cacheDir)) {
                mkdir($cacheDir, 0770);
            }
            $this->enableCache('fs', $cacheDir);
        }
    }
    
    function getAPIKey() {
        return $this->_silas_apiKey;
    }
    function getSharedSecret() {
        return $this->_silas_sharedSecret;
    }
    function getFrob() {
        $this->startClearCache();
        return $this->auth_getFrob();
    }
    
    function getUser() {
        return $this->_silas_user;
    }
    
    function setUser($user) {
        $this->_silas_user = $user;
    }
    
    function setOption($key, $value=NULL) {
        if (is_array($key)) {
            $this->_silas_options = $key;
        } else {
            $this->_silas_options[$key] = $value;
        }
    }
    function getOption($key) {
        return $this->_silas_options[$key];
    }
    
    function getRecent($extras = NULL, $per_page = NULL, $page = NULL) {
		if ($return = $this->getObjCache('getRecent', array($extras, $per_page, $page))) {
			return $return;
		}
        $photos = $this->photos_getRecent($extras, $per_page, $page);
        $return = array();
        if (is_array($photos['photo'])) foreach ($photos['photo'] as $photo) {
            $row = array();
            $row['id'] = $photo['id'];
            $row['title'] = $photo['title'];
            $row['sizes'] = $this->getPhotoSizes($photo['id']);
            $row['pagename2'] = $this->_sanitizeTitle($photo['title']);
            $row['pagename'] = $row['pagename2'] . '.html';
            //$row['total'] = $photos['total'];
            $return[$photo['id']] = $row;
        }
		$this->setObjCache('getRecent', array($extras, $per_page, $page), $return);
        return $return;
    }
    
    function getPhotosByTags($tags) {
        $user = $this->auth_checkToken();
        //TODO: should disable caching here or something
        $photos = $this->search(array(
            'tags' => $tags,
            'user_id' => $user['user']['nsid'],
        ));
        return $photos;
    }
    function getRelatedTags($tags) { // hmm this get's everyones tags
        $tags = $this->tags_getRelated($tags);
        return $tags;
    }
    
    function getTags($count=100) {
        $data = $this->tags_getListUserPopular(NULL, $count);
        $return = array();
        if (is_array($data)) foreach ($data as $tag) {
            $return[$tag['_content']] = $tag['count'];
        }
        return $return;
    }
    function getTagsByGroup($group_id, $count=100) {
        $return = array();
        return $return;
    }
    function getTagsByAlbum($album_id, $count=100) {
        $return = array();
        return $return;
    }
    
    // no caching
    function search($args) {
		if ($return = $this->getObjCache('search', $args)) {
			return $return;
		}
        $photos = $this->photos_search($args);
        $return = array();
        if (is_array($photos['photo'])) foreach ($photos['photo'] as $photo) {
            $row = array();
            $row['id'] = $photo['id'];
            $row['title'] = $photo['title'];
            $row['sizes'] = $this->getPhotoSizes($photo['id']);
            $row['pagename2'] = $this->_sanitizeTitle($photo['title']);
            $row['pagename'] = $row['pagename2'] . '.html';
            //$row['total'] = $photos['total'];
            $return[$photo['id']] = $row;
        }
		$this->setObjCache('search', $args, $return);
        return $return;
    }
    
    function getAlbumsActual() { // get non cached list
        $this->startClearCache();
        $albums = $this->getAlbums();
        $this->doneClearCache();
        return $albums;
    }
    
    function getAlbums() {
        $albums = $this->photosets_getList();
        $return = array();
        if (is_array($albums['photoset'])) foreach ($albums['photoset'] as $album) {
            $row = array();
            $row['id'] = $album['id'];
            $row['title'] = $album['title'];
            $row['description'] = $album['description'];
            $row['primary'] = $album['primary'];
            $row['photos'] = $album['photos'];
            $row['pagename2'] = $this->_sanitizeTitle($album['title']);
            $row['pagename'] = $row['pagename2'] . '.html';
            $return[$row['id']] = $row;
        }
        return $return;
    }
    
    function getAlbum($album_id) {
        $album_id = $album_id . '';
        $album = $this->photosets_getInfo($album_id);
        $return = array();
        if (is_array($album)) {
            $return['id'] = $album['id'];
            $return['owner'] = $album['owner'];
            $return['primary'] = $album['primary'];
            $return['title'] = $album['title'];
            $return['description'] = $album['description'];
            $return['pagename2'] = $this->_sanitizeTitle($album['title']);
            $return['pagename'] = $return['pagename2'] . '.html';
            
        }
        return $return;
    }
    
    function getGroupsActual() {
		if (!SILAS_FLICKR_DISPLAYGROUPS) return array();
	
        $this->startClearCache();
        $groups = $this->getGroups();
        $this->doneClearCache();
        return $groups;
    }
    function getGroups() {
		if (!SILAS_FLICKR_DISPLAYGROUPS) return array();
		
        $groups = $this->groups_pools_getGroups();
        $return = array();

        if (is_array($groups['group'])) foreach ($groups['group'] as $group) {
            $row = array();
            $row['id'] = $group['id'];
            $row['name'] = $group['name'];
            $row['photos'] = $group['photos'];
            $row['privacy'] = $group['privacy'];
            $row['admin'] = $group['admin'];
            $row['pagename2'] = $this->_sanitizeTitle($group['name']);
            $row['pagename'] = $row['pagename2'] . '.html';
            $row['iconurl'] = ($group['iconserver'] > 0) ? 'http://static.flickr.com/'.$group['iconserver'].'/buddyicons/'.$group['id'].'.jpg'
                : 'http://www.flickr.com/images/buddyicon.jpg';
            
            $info = $this->getGroup($group['id']);
            $row['description'] = $info['description'];
            $row['privacy'] = $info['privacy'];
            $row['members'] = $info['members'];
            $row['flickrURL'] = $info['flickrURL'];
            $return[$row['id']] = $row;
        }
        return $return;
    }
    function getGroup($group_id) {
		if (!SILAS_FLICKR_DISPLAYGROUPS) return array();
	
        $group_id = $group_id . '';
        $group = $this->groups_getInfo($group_id);
        $return = array();
        if (is_array($group)) {
            $return['id'] = $group['id'];
            $return['name'] = $group['name'];
            $return['description'] = $group['description'];
            $return['members'] = $group['members'];
            $return['privacy'] = $group['privacy'];
            $return['pagename2'] = $this->_sanitizeTitle($group['name']);
            $return['pagename'] = $return['pagename2'] . '.html';
            $return['flickrURL'] = $this->urls_getGroup($group_id);
        }
        return $return;
    }
    
    function getPhotosByGroup($group_id, $tags=NULL, $user_id = NULL, $extras = NULL, $per_page = NULL, $page = NULL) {
		if (!SILAS_FLICKR_DISPLAYGROUPS) return array();
	
        $group_id = $group_id . '';
        
        $this->_silas_cacheExpire = 3600;
        $photos = $this->groups_pools_getPhotos($group_id, $tags, $user_id, $extras, $per_page, $page);
        $this->_silas_cacheExpire = -1;
        
        
        $return = array();
        if (is_array($photos['photo'])) foreach ($photos['photo'] as $photo) {
            $row = array();
            $row['id'] = $photo['id'];
            $row['title'] = $photo['title'];
            $row['sizes'] = $this->getPhotoSizes($photo['id']);
            $row['pagename2'] = $this->_sanitizeTitle($photo['title']);
            $row['pagename'] = $row['pagename2'] . '.html';
            $return[$photo['id']] = $row;
        }
        return $return;
    }
        
    function getPhotos($album_id) {
		if ($return = $this->getObjCache('getPhotos', $album_id)) {
			return $return;
		}
        $album_id = $album_id . '';
        $photos = $this->photosets_getPhotos($album_id);
        $return = array();
        if (is_array($photos['photo'])) foreach ($photos['photo'] as $photo) {
            $row = array();
            $row['id'] = $photo['id'];
            $row['title'] = $photo['title'];
            $row['sizes'] = $this->getPhotoSizes($photo['id']);
            $row['pagename2'] = $this->_sanitizeTitle($photo['title']);
            $row['pagename'] = $row['pagename2'] . '.html';
            $row = array_merge($row, (array) $this->getPhoto($photo['id']));
            $return[$photo['id']] = $row;
        }
		$this->setObjCache('getPhotos', $album_id, $return);
        return $return;
    }
    
    function getPhoto($photo_id) {
        $photo_id = $photo_id . '';
        $photo = $this->photos_getInfo($photo_id);
        return $photo;
    }
    function getComments($photo_id) {
        $photo_id = $photo_id . '';
        $this->_silas_cacheExpire = 3600;
        $comments = $this->photos_comments_getList($photo_id);
        $this->_silas_cacheExpire = -1;
        
        $return = array();
        $comments = $comments['comment'];
        if (is_array($comments)) foreach ($comments as $comment) {
            $row = array();
            $row['id'] = $comment['id'];
            $row['author'] = $this->people_getInfo($comment['author']);
            $row['datecreate'] = $comment['datecreate'];
            $row['permalink'] = $comment['permalink'];
            $row['comment'] = $comment['_content'];
            $return[$comment['id']] = $row;
        }
        return $return;
    }
    
    function getPhotoSizes($photo_id) {
        $photo_id = $photo_id . '';
        $sizes = $this->photos_getSizes($photo_id);
        $return = array();
        if (is_array($sizes)) foreach ($sizes as $k => $size) {
            $return[$size['label']] = $size;
        }
        return $return;
    }
    function getContext($photo_id, $album_id='') {
        $photo_id = $photo_id . '';
        $album_id = $album_id . '';
        $context = array();
        if ($album_id) {
            $context = $this->photosets_getContext($photo_id, $album_id);
        } else {
            $context = $this->photos_getContext($photo_id);
        }
        $context['prevphoto']['pagename'] = $this->_sanitizeTitle($context['prevphoto']['title']).'.html';
        $context['nextphoto']['pagename'] = $this->_sanitizeTitle($context['nextphoto']['title']).'.html';
        return $context;
    }
    function getContextByGroup($photo_id, $group_id) {
		if (!SILAS_FLICKR_DISPLAYGROUPS) return array();
	
        $photo_id = $photo_id . '';
        $group_id = $group_id . '';
        $context = $this->groups_pools_getContext($photo_id, $group_id);
        $context['prevphoto']['pagename'] = $this->_sanitizeTitle($context['prevphoto']['title']).'.html';
        $context['nextphoto']['pagename'] = $this->_sanitizeTitle($context['nextphoto']['title']).'.html';
        return $context;
    }

    function manualSort($array, $order) {
        if (!is_array($array)) { return array(); }
        
        if (is_array($order)) {
            $pre = array();
            //$mid = array();
            $pos = array();
            foreach ($order as $id => $ord) {
                if ($array[$id]) {
                    if (((int) $ord < 0)) { 
                        $pre[$id] = $array[$id];
                        unset($array[$id]);
                    } elseif (((int) $ord > 0)) { 
                        $pos[$id] = $array[$id];
                        unset($array[$id]);
                    //} else {
                        //$mid[$id] = $array[$id];
                    }
                }
            }
            return $pre + $array + $pos;
        } else {
            return $array;
        }
    }

    function startClearCache() {
        $this->_silas_useCache = false;
    }
    function doneClearCache() {
        $this->_silas_useCache = true;
    }
    function clearCache() {
        if (SILAS_FLICKR_CACHEMODE == 'db') {
            $result = $this->cache_db->query("DELETE FROM " . $this->cache_table . ";");
            return true;
        } elseif ($this->_clearCache($this->cache_dir)) {
            return @mkdir($this->cache_dir, 0770);
        } else {
            return false;
        }
    }
	function clearCacheStale() {
		if (SILAS_FLICKR_CACHEMODE == 'db') {
			$commands = array(
			    //'flickr.groups.getInfo' => 4320000,
				'flickr.groups.pools.getContext' => 432000,
				'flickr.groups.pools.getGroups' => 432000,
				'flickr.groups.pools.getPhotos' => 432000,
				//'flickr.people.getInfo' => 432000,
				'flickr.photos.comments.getList' => 86400,
				'flickr.photos.getContext' => 86400,
				//'flickr.photos.getInfo' => 86400,
				'flickr.photos.getRecent' => 43200,
				//'flickr.photos.getSizes' => 86400,
				'flickr.photos.search' => 43200,
				//'flickr.photosets.getContext' => 43200,
				//'flickr.photosets.getInfo' => 86400,
				'flickr.photosets.getList' => 43200,
				'flickr.photosets.getPhotos' => 43200,
				'flickr.tags.getListUserPopular' => 86400,
				//'flickr.urls.getGroup' => 86400,
				'getPhotos' => 43200,
				'search' => 43200,
				'getRecent' => 43200,
				);
			foreach ($commands as $command => $timeout) {
				$time = time() - $timeout;
            	$result = $this->cache_db->query("DELETE FROM " . $this->cache_table . " WHERE command = '".$command."' AND created < '".strftime("%Y-%m-%d %H:%M:%S", $time)."' ;");
			}
            return true;
        }
	}
    function _clearCache($dir) {
       if (substr($dir, strlen($dir)-1, 1) != '/')
           $dir .= '/';
    
       if ($handle = opendir($dir)) {
           while ($obj = readdir($handle)) {
               if ($obj != '.' && $obj != '..') {
                   if (is_dir($dir.$obj)) {
                       if (!$this->_clearCache($dir.$obj))
                           return false;
                   }
                   elseif (is_file($dir.$obj)) {
                       if (!unlink($dir.$obj)) return false;
                   }
               }
           }
           closedir($handle);
    
           if (!@rmdir($dir)) return false;
           return true;
       }
       return false;
    }
    function _sanitizeTitle($title) {
        // try this WP function sanitize_title_with_dashes()

        // comment out these two lines, and use the next two if you like underscores instead
        $output = preg_replace('/\s+/', '-', $title);
        $output = preg_replace("/[^a-zA-Z0-9-]/" , "" , $output);
        //$output = preg_replace("/\s/e" , "_" , $title);
        //$output = preg_replace("/\W/e" , "" , $output);
   	
       // Remove non-word characters
       return $output;
    }
    function getErrorMsgs() {
        return implode("\n", $this->_silas_errorMsg);
    }
        
    /*
        Reimplemented methods
    */
    function request ($command, $args = array(), $nocache = false) {
        $nocache = (($this->_silas_cacheExpire > 0) ? true : false);
        $nocache = ($nocache ? true : 
            ($this->_silas_useCache ? false : true));
        if ($this->getOption('hidePrivatePhotos')) {
            $args['privacy_filter'] = 1;
            if ($command != 'flickr.auth.checkToken')  {
                $token = $this->token;
                //$this->token = ''; // just make an unathenticated call
            }
        }
        parent::request($command, $args, $nocache);
        if ($token) $this->token = $token;
    }

    
    function enableCache($type, $connection, $cache_expire = 600, $table = 'flickr_cache') {
        global $wpdb;
        if ($type == 'db') {
            $this->cache = 'db';
            $this->cache_db =& $connection;
            $this->cache_table = $wpdb->prefix.'silas_flickr_cache';
            $this->_silas_useCache = true;
        } elseif ($type == 'fs') {
            $this->cache = 'fs';
            $this->cache_expire = $cache_expire;
            $this->cache_dir = $connection;
            $this->_silas_useCache = true;
        }
    }

    function getCached ($request) // buggy, time based caching doesnt work
    {
        $reqhash = $this->makeReqHash($request);
        if ($this->cache == 'db') {
            $result = $this->cache_db->get_col("SELECT response FROM " . $this->cache_table . " WHERE request = '" . $reqhash . "'");
            if (!empty($result)) {
                return array_pop($result);
            }
            return false;
        } elseif ($this->cache == 'fs') {
            //Checks the database or filesystem for a cached result to the request.
            //If there is no cache result, it returns a value of false. If it finds one,
            //it returns the unparsed XML.
            
            $pre = substr($reqhash, 0, 2);
            $file = $this->cache_dir . '/' . $pre . '/' . $reqhash . '.cache';

            if (file_exists($file)) {
                if ($this->_silas_cacheExpire > 0) {
                    if ((time() - filemtime($file)) > $this->_silas_cacheExpire) {
                        return false;
                    }
                } 
                return file_get_contents($file);
            } else {
                return false;
            }
        }
    }
    
    function cache ($request, $response, $expiration=false) {
		if (!$expiration) {
			$expiration = time() + SILAS_FLICKR_CACHE_TIMEOUT; // 30 days default cache
		}
        $reqhash = $this->makeReqHash($request);
        if ($this->cache == 'db') {
            $this->cache_db->query("DELETE FROM $this->cache_table WHERE request = '$reqhash'");
            $sql = "INSERT INTO " . $this->cache_table . " (command, request, response, created, expiration) VALUES ('".$request['method']."', '$reqhash', '" . addslashes($response) . "', '" . strftime("%Y-%m-%d %H:%M:%S") . "', '" . strftime("%Y-%m-%d %H:%M:%S", $expiration) . "')";
            $this->cache_db->query($sql);
        } elseif ($this->cache == 'fs') {
            //Caches the unparsed XML of a request.
            
            $pre = substr($reqhash, 0, 2);  // store into buckets
            $file = $this->cache_dir . "/" . $pre . '/' . $reqhash . ".cache";
            
            if (!file_exists($this->cache_dir . '/' . $pre)) {
                mkdir($this->cache_dir . '/' . $pre, 0770);
            }
            $fstream = fopen($file, "w");
            if ($fstream) {
                $result = fwrite($fstream,$response);
                fclose($fstream);
            }
            return $result;
        }
    }
    function makeReqHash($request) {
        if (is_array($request)) {
			unset($request['api_key']);
			unset($request['auth_token']);
			unset($request['format']);
		}
        return md5(serialize($request));
    }

	function getObjCache($function, $params) {
		if ($this->cache == 'db') {
			$request = array(
				'method' => $function,
				'params' => $params,
			);
			$return = $this->getCached($request);
			if ($return) {
				return unserialize($return);
			} else {
				return false;
			}
		}
	}
	function setObjCache($function, $params, $obj) {
		if ($this->cache == 'db') {
			$request = array(
				'method' => $function,
				'params' => $params,
			);
            return $this->cache($request, serialize($obj));
		}
	}
    function auth_getToken ($frob) 
    {
        if ($_SESSION['phpFlickr_auth_token']) return $_SESSION['phpFlickr_auth_token'];

        /* http://www.flickr.com/services/api/flickr.auth.getToken.html */
        $this->request('flickr.auth.getToken', array('frob'=>$frob));
        $_SESSION['phpFlickr_auth_token'] = $this->parsed_response['auth']['token'];
        return $_SESSION['phpFlickr_auth_token'];
    }
    
}
?>