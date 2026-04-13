<?php
class ModelToolWebpImage extends Model {
  
  public function webpSupported() {
    if (defined('DISABLE_WEBP')) {
      $isWebpConverterEnabled = false;
    } else {
      $isWebpConverterEnabled = $this->config->get('webp_image_status') ? true : false;
    }
    
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    $isAvifSupported = false;
    
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/avif') !== false && function_exists('imageavif') && $this->config->get('webp_image_mode') != 'webp') {
      $isAvifSupported = true;
    }
    
    // Check if it is an Apple device
    if (strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) {
      // Find the iOS version
      if (preg_match('/OS (\d+)_\d+/', $userAgent, $matches)) {
          $iOSVersion = (int) $matches[1];
          // Compatible from iOS 14
          if ($iOSVersion < 14) {
              $isWebpConverterEnabled = false;
          }
      }
    }

    // Check if it's Safari on macOS
    if (strpos($userAgent, 'Macintosh') !== false && strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) {
      // Find the version of Safari
      if (preg_match('/Version\/(\d+)/', $userAgent, $matches)) {
          $safariVersion = (int) $matches[1];
          // Safari supported from 14 onwards, but only on macOS 11 Big Sur or later
          if ($safariVersion >= 14 && $safariVersion <= 15) {
              if (preg_match('/Mac OS X 10_(\d+)/', $userAgent, $macMatches)) {
                  $macVersion = (int) $macMatches[1];
                  // If it's a version of macOS older than 11 Big Sur, disable WebP
                  if ($macVersion < 16) {
                      $isWebpConverterEnabled = false;
                  }
              }
          } elseif ($safariVersion < 14) {
              $isWebpConverterEnabled = false;
          }
      }
    }

    
    if (strpos($userAgent, 'Firefox') !== false) {
      // Find the version of Firefox
      if (preg_match('/Firefox\/(\d+)/', $userAgent, $matches)) {
        $firefoxVersion = (int) $matches[1];
        // Enable WebP only if Firefox supports it (version 65 or higher)
        if ($firefoxVersion < 65) {
            $isWebpConverterEnabled = false;
        }
      }
    }
    
    if (!$isAvifSupported && $this->config->get('webp_image_mode') == 'avif') {
      $isWebpConverterEnabled = false;
    }
    
    return $isWebpConverterEnabled;
  }
  
	public function convert($filename, $reload = false, $pathMode = false) {
    $basePath = DIR_IMAGE;
    
		if (!is_file(DIR_IMAGE . $filename) || substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE)) != str_replace('\\', '/', DIR_IMAGE)) {
      if (is_file(DIR_SYSTEM . '../' . $filename)) {
        $basePath = DIR_SYSTEM . '../';
      } else {
        return $filename;
      }
		}
    
    $isAvifSupported = false;
    
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/avif') !== false && function_exists('imageavif') && $this->config->get('webp_image_mode') != 'webp') {
      $isAvifSupported = true;
    }
    
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
    
    if ($extension == 'webp' || $extension == 'avif') {
      return $filename;
    }
    
    if ($isAvifSupported) {
      $extension = 'avif';
    } else {
      $extension = 'webp';
    }
    
    $webpQuality = $this->config->get('webp_image_quality') ? $this->config->get('webp_image_quality') : 90;
		$image_old = $filename;
		$image_new = 'cache/' . mb_substr($filename, 0, mb_strrpos($filename, '.')) . '.' . $extension;

		if ($reload || !is_file(DIR_IMAGE . $image_new) || (filemtime($basePath . $image_old) > filemtime(DIR_IMAGE . $image_new))) {
			list($width_orig, $height_orig, $image_type) = getimagesize($basePath . $image_old);
				 
			if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF))) { 
				return $basePath . $image_old;
			}
			
			$path = '';

			$directories = explode('/', dirname($image_new));

			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;

				if (!is_dir(DIR_IMAGE . $path)) {
					@mkdir(DIR_IMAGE . $path, 0777);
				}
			}

				$image = new Image($basePath . $image_old);
				//$image->resize($width, $height);
				$image->save(DIR_IMAGE . $image_new, $webpQuality);
		}
		
		$image_new = str_replace(' ', '%20', $image_new);
		    
		if ($pathMode) {
      return $image_new;
		} else if ($this->request->server['HTTPS']) {
			return $this->config->get('config_ssl') . 'image/' . $image_new;
		} else {
			return $this->config->get('config_url') . 'image/' . $image_new;
		}
	}
  
  public function convertInHtml($html) {
    if (!$this->webpSupported()) return $html;
    
    preg_match_all('~<img[^>]*\bsrc=(["\']?)(.*?)\1[^>]*>~i', $html, $images);
    
    foreach ($images[0] as $k => $image) {
      $convert = true;
      
      $imgSrc = $origImgSrc =$images[2][$k];
      
      $imgSrc = trim($imgSrc);
      
      $imgType = pathinfo($imgSrc, PATHINFO_EXTENSION);
      
      if (!in_array(strtolower($imgType), array('png', 'jpg', 'jpeg', 'gif'))) {
        continue;
      }
      
      $imgUrl = str_replace(array('http://www.', 'https://www.', 'http://', 'https://'), '', HTTP_SERVER . 'image/');
      $imgNewSrc = str_replace(array('http://www.', 'https://www.', 'http://', 'https://'), '', $imgSrc);

      if (strpos($imgNewSrc, $imgUrl) !== false) {
        $imgSrc = str_replace($imgUrl, '', $imgNewSrc);
      /*
      if (strpos($imgSrc, HTTP_SERVER . 'image/') !== false) {
        $imgSrc = str_replace(HTTP_SERVER . 'image/', '', $imgSrc);
      } else if (strpos($imgSrc, HTTPS_SERVER . 'image/') !== false) {
        $imgSrc = str_replace(HTTPS_SERVER . 'image/', '', $imgSrc);
      */
      } else if (substr($imgSrc, 0, 8) == 'catalog/') {
        
      } else if (substr($imgSrc, 0, 6) == 'image/') {
        $imgSrc = substr($imgSrc, 6);
      } else if (substr($imgSrc, 0, 7) == '/image/') {
        $imgSrc = substr($imgSrc, 7);
      } else {
        $convert = false;
      }
      
      if ($imgType == 'gif') {
        if ($this->isAnimatedGif(DIR_IMAGE . $imgSrc)) {
          continue;
        }
      }
      
      if ($convert) {
        $imgSrcConverted = $this->convert($imgSrc);
        if ($imgSrcConverted && $imgSrcConverted != $imgSrc) {
          $imageConverted = str_replace($origImgSrc, $imgSrcConverted, $image);
          
          $html = str_replace($image, $imageConverted, $html);
        }
      }
    }
    
    preg_match_all('~url\((?:"|\')?(.+?)(?:"|\')?\)~', $html, $images);
    
    foreach ($images[0] as $k => $image) {
      $convert = true;
      
      $imgSrc = $origImgSrc =$images[1][$k];
      
      $imgType = pathinfo($imgSrc, PATHINFO_EXTENSION);
      
      if (!in_array(strtolower($imgType), array('png', 'jpg', 'jpeg', 'gif'))) {
        continue;
      }
      
      $imgUrl = str_replace(array('http://www.', 'https://www.', 'http://', 'https://'), '', HTTP_SERVER . 'image/');
      $imgNewSrc = str_replace(array('http://www.', 'https://www.', 'http://', 'https://'), '', $imgSrc);

      if (strpos($imgNewSrc, $imgUrl) !== false) {
        $imgSrc = str_replace($imgUrl, '', $imgNewSrc);
      /*
      if (strpos($imgSrc, HTTP_SERVER . 'image/') !== false) {
        $imgSrc = str_replace(HTTP_SERVER . 'image/', '', $imgSrc);
      } else if (strpos($imgSrc, HTTPS_SERVER . 'image/') !== false) {
        $imgSrc = str_replace(HTTPS_SERVER . 'image/', '', $imgSrc);
      */
      } else if (substr($imgSrc, 0, 8) == 'catalog/') {
        
      } else if (substr($imgSrc, 0, 6) == 'image/') {
        $imgSrc = substr($imgSrc, 6);
      } else if (substr($imgSrc, 0, 7) == '/image/') {
        $imgSrc = substr($imgSrc, 7);
      } else {
        $convert = false;
      }
      
      if ($convert) {
        $imgSrcConverted = $this->convert($imgSrc);
        
        if ($imgSrcConverted && $imgSrcConverted != $imgSrc) {
          $imageConverted = str_replace($origImgSrc, $imgSrcConverted, $image);
          
          $html = str_replace($image, $imageConverted, $html);
        }
      }
    }
    
    return $html;
  }
  
  public function convertInHtmlDom($html) {
    $dom = new DOMDocument();
    //@$dom->loadHTML($html);
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html); //load in utf-8
    $images = $dom->getElementsByTagName('img');
    
    $changed = false;
    
    foreach ($images as $image) {
      $convert = true;
      
      $imgSrc = $image->getAttribute('src');
      
      $imgSrc = trim($imgSrc);
      
      $imgType = pathinfo($imgSrc, PATHINFO_EXTENSION);
      
      if (!in_array(strtolower($imgType), array('png', 'jpg', 'jpeg', 'gif'))) {
        continue;
      }
      
      $imgUrl = str_replace(array('http://www.', 'https://www.', 'http://', 'https://'), '', HTTP_SERVER . 'image/');
      $imgNewSrc = str_replace(array('http://www.', 'https://www.', 'http://', 'https://'), '', $imgSrc);

      if (strpos($imgNewSrc, $imgUrl) !== false) {
        $imgSrc = str_replace($imgUrl, '', $imgNewSrc);
      /*
      if (strpos($imgSrc, HTTP_SERVER . 'image/') !== false) {
        $imgSrc = str_replace(HTTP_SERVER . 'image/', '', $imgSrc);
      } else if (strpos($imgSrc, HTTPS_SERVER . 'image/') !== false) {
        $imgSrc = str_replace(HTTPS_SERVER . 'image/', '', $imgSrc);
      */
      } else if (substr($imgSrc, 0, 6) == 'image/') {
        $imgSrc = substr($imgSrc, 6);
      } else if (substr($imgSrc, 0, 7) == '/image/') {
        $imgSrc = substr($imgSrc, 7);
      } else if (substr($imgSrc, 0, 8) == 'catalog/') {
      } else {
        $convert = false;
      }
      
      if ($convert) {
        $image->setAttribute('src', $this->convert($imgSrc)); 
      }
      
      $changed = true;
    }
    
    if (!$changed) {
      return $html;
    }
    
    return $dom->saveHTML();
    //return utf8_decode($dom->saveHTML($dom->documentElement)); // save in utf-8, to use if load method is not working
  }
  
  function isAnimatedGif($filename) {
    if(!($fh = @fopen($filename, 'rb')))
      return false;
    
    $count = 0;
    //an animated gif contains multiple "frames", with each frame having a
    //header made up of:
    // * a static 4-byte sequence (\x00\x21\xF9\x04)
    // * 4 variable bytes
    // * a static 2-byte sequence (\x00\x2C) (some variants may use \x00\x21 ?)

    // We read through the file til we reach the end of the file, or we've found
    // at least 2 frame headers
    $chunk = false;
    while(!feof($fh) && $count < 2) {
      //add the last 20 characters from the previous string, to make sure the searched pattern is not split.
      $chunk = ($chunk ? substr($chunk, -20) : "") . fread($fh, 1024 * 100); //read 100kb at a time
      $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
    }

    fclose($fh);
    return $count > 1;
  }

  public function clearCache($images) {
    foreach ((array) $images as $image) {
      if (isset($image['image'])) {
        $image = $image['image'];
      }
      
      foreach (glob(DIR_IMAGE.'cache/'.dirname($image).'/'.basename($image, '.'.pathinfo($image, PATHINFO_EXTENSION)).'*.webp') as $file) {
        unlink($file);
      }
    }
  }
  
  public function getPageList() {
    $list = array();
    
    // products
    $items = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "product` WHERE status = 1")->rows;
    
    foreach ($items as $item) {
      $list[] = HTTP_CATALOG.'index.php?route=product/product&product_id='.$item['product_id'];
    }
    
    // brands
    $items = $this->db->query("SELECT manufacturer_id FROM `" . DB_PREFIX . "manufacturer`")->rows;
    
    $list[] = HTTP_CATALOG.'index.php?route=product/manufacturer';
    
    foreach ($items as $item) {
      $list[] = HTTP_CATALOG.'index.php?route=product/manufacturer/info&manufacturer_id='.$item['manufacturer_id'];
    }
    
    // categories
    $items = $this->db->query("SELECT category_id, parent_id FROM `" . DB_PREFIX . "category` WHERE status = 1")->rows;
    
    foreach ($items as $item) {
      $list[] = HTTP_CATALOG.'index.php?route=product/category&path='.$item['category_id'];
    }
    
		return $list;
	}
}