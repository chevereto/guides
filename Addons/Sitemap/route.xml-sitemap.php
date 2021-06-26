<?php
// https://chevereto.com/community/threads/xml-sitemap.7850/#post-68312
$route = function($handler) {
try {
  $sitemapsFolder = 'xml-sitemaps/'; //Child sitemaps folder(Optional change)
  $max_url_limit = 5000; // 50k is maximum  https://support.google.com/webmasters/answer/183668?hl=en

  //DO NOT CHANGE
  //////////////////////////////////////////////////////////
  $absSitemapsFolder = getcwd().'/'.$sitemapsFolder;
  $handler::$vars['sitemapsFolder'] = $sitemapsFolder;

  // Basic things
  if( !file_exists($absSitemapsFolder)){
  if(!mkdir($absSitemapsFolder))
  throw new Exception('Can not create '.$sitemapsFolder);

  if(!is_readable($absSitemapsFolder) or !is_writeable($absSitemapsFolder))
  throw new Exception('Can not read/write to '.$sitemapsFolder. '. Check permissions.');
  }
  // lastmod
  $objDateTime  =  new DateTime('NOW');
  $dateTime  = $objDateTime->format(DATE_W3C);

  // Get paths
  $site_upload_image_route = CHV\getSetting('upload_image_path');
  $site_album_route = CHV\getSetting('route_album');
  $site_image_route = CHV\getSetting('route_image');
  $site_user_route = CHV\getSetting('user_routing');

  // To get no of child sitemaps in each one
  /////////////////////////////////////////////////////////////////////////////////////////////////

  // Count no of images and then round off to higher number.
  $count_images = CHV\DB::queryFetchSingle('SELECT COUNT(*) as total FROM ' . CHV\DB::getTable('images'). '')['total'];
  if ($count_images > 0) {
  $image_sitemap_count = ceil($count_images/$max_url_limit);
  for ($i=1; $i<= $image_sitemap_count; $i++) {
  $lists[] = array('list_id' => 'images', 'list_name' => 'Images '.$i, 'list_slug' => 'images'.$i);
  }
  }

  // Count no of albums and then round off to higher number.
  $count_albums = CHV\DB::queryFetchSingle('SELECT COUNT(*) as total FROM ' . CHV\DB::getTable('albums'). ' WHERE album_privacy = "public" AND album_password IS NULL AND NOT album_image_count = "0"')['total'];
  if ($count_albums > 0) {
  $album_sitemap_count = ceil($count_albums/$max_url_limit);
  for ($j=1; $j<= $album_sitemap_count; $j++) {
  $lists[] = array('list_id' => 'albums', 'list_name' => 'Albums '.$j, 'list_slug' => 'albums'.$j);
  }
  }

  // Count no of users and then round off to higher number.
  $count_users = CHV\DB::queryFetchSingle('SELECT COUNT(*) as total FROM ' . CHV\DB::getTable('users'). ' WHERE user_is_private = "0"')['total'];
  if ($count_users > 0) {
  $user_sitemap_count = ceil($count_users/$max_url_limit);
  for ($k=1; $k<= $user_sitemap_count; $k++) {
  $lists[] = array('list_id' => 'users', 'list_name' => 'Users '.$k, 'list_slug' => 'users'.$k);
  }
  }

  $handler::$vars['lists'] = $lists;
 
  //Strip away the unwanted chars
  function utf8_for_xml($string)
  {
  return preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
  }
 

  // Create index sitemap on root dir
  //////////////////////////////////////////////////////////////////////////////////////
  $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" />');


  foreach($lists as $list) {
  $c = $xml->addChild('sitemap');

  $xml_url  = $handler->base_url.$sitemapsFolder.$list['list_slug'].'.xml';
  $xml_local  = $sitemapsFolder.$list['list_slug'].'.xml';

  $c->addChild('loc', $xml_url);
  $c->addChild('lastmod', $dateTime );


  // Lets create a sitemaps
  /////////////////////////////////////////////////////////////////////////////////
  $xml2 = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" />');

  $DB = CHV\DB::getInstance();

  if($list['list_id'] == 'images' and $count_images > 0) {

  for ($l=1; $l<= $image_sitemap_count; $l++) {

  $iurl_offset = $max_url_limit*($l-1);
  if($list['list_slug'] == 'images'.$l) {
  $q = $DB->query('SELECT image_id, image_title, image_description, DATE_FORMAT(image_date, "%Y") as year, DATE_FORMAT(image_date, "%m") as month, DATE_FORMAT(image_date, "%d") as day, image_storage_mode, image_storage_id, image_date, image_name, image_extension, storage_url FROM ' . CHV\DB::getTable('images'). '
  LEFT JOIN ' . CHV\DB::getTable('storages'). ' on storage_id = image_storage_id
  LEFT JOIN ' . CHV\DB::getTable('users'). ' on user_id = image_user_id
  WHERE user_is_private = 0 AND image_user_id IS NOT NULL
  UNION
  SELECT image_id, image_title, image_description, DATE_FORMAT(image_date, "%Y") as year, DATE_FORMAT(image_date, "%m") as month, DATE_FORMAT(image_date, "%d") as day, image_storage_mode, image_storage_id, image_date, image_name, image_extension, storage_url FROM ' . CHV\DB::getTable('images'). '
  LEFT JOIN ' . CHV\DB::getTable('storages'). ' on storage_id = image_storage_id
  WHERE image_user_id IS NULL
  ORDER BY image_date DESC LIMIT '.$max_url_limit.' OFFSET '.$iurl_offset.'');
  }
  }

  }
 
  if($list['list_id'] == 'albums' and $count_albums > 0) {

  for ($m=1; $m<= $album_sitemap_count; $m++) {

  $aurl_offset = $max_url_limit*($m-1);
  if($list['list_slug'] == 'albums'.$m) { 
  $q = $DB->query('SELECT album_id FROM ' . CHV\DB::getTable('albums'). ' LEFT JOIN ' . CHV\DB::getTable('users'). ' on user_id = album_user_id WHERE album_privacy = "public" AND album_password IS NULL AND NOT album_image_count = "0" AND user_is_private = "0" ORDER BY album_id DESC LIMIT '.$max_url_limit.' OFFSET '.$aurl_offset.'');
  }
  } 

  }
  if($list['list_id'] == 'users' and $count_users > 0) {

  for ($n=1; $n<= $user_sitemap_count; $n++) {
  $uurl_offset = $max_url_limit*($n-1);
  if($list['list_slug'] == 'users'.$n) { 
  $q = $DB->query('SELECT user_username FROM ' . CHV\DB::getTable('users'). ' WHERE user_is_private = "0" ORDER BY user_id DESC LIMIT '.$max_url_limit.' OFFSET '.$uurl_offset.'');
  }
  }
  }

  $images = $DB->fetchAll();
  //Inner xml elements
  /////////////////////////////////////////////////////////////
  foreach($images as $image) {

  if($list['list_id'] == 'albums' and $count_albums > 0) {

  // Every album gets its own URL
  $cc = $xml2->addChild('url');
  $cc->addChild('loc', $handler->base_url.$site_album_route.'/'.CHV\encodeID($image['album_id'], 'encode') ); 

  }
 
  if($list['list_id'] == 'users' and $count_users > 0) {

  if ($site_user_route == '1') {
  $user_base_url = $handler->base_url;
  } else {
  $user_base_url = $handler->base_url.'user/';
  }
 
  // Every user gets their own URL
  $cc = $xml2->addChild('url');
  $cc->addChild('loc', $user_base_url.$image['user_username']); 

  }
 
  if($list['list_id'] == 'images' and $count_images > 0) { 

  $folder = '';

  switch ($image['image_storage_mode']){
  case 'datefolder':
  $folder = $image['year'].'/'.$image['month'].'/'.$image['day'].'/';
  break;

  default:
  $folder = '';
  break;
  }
 
  // Every image gets its own URL
  $cc = $xml2->addChild('url');
  $cc->addChild('loc', $handler->base_url.$site_image_route.'/'.CHV\encodeID($image['image_id'], 'encode') );
 
  if ($image['image_storage_id'] == NULL) {
  $image_base_url = $handler->base_url.$site_upload_image_route.'/';
  } else {
  $image_base_url = $image['storage_url'];
  } 

  $cc_image = $cc->addChild('xmlns:image:image');
  $cc_image->addChild('xmlns:image:loc', $image_base_url.$folder.$image['image_name'].'.'.$image['image_extension']);
  $cc_image->addChild("xmlns:image:title", htmlspecialchars($image["image_title"]));
  //if (strlen($image['image_description']) > 3) {
  //$cc_image->addChild('xmlns:image:caption', utf8_for_xml($image['image_description']));
  //}
  }

  }

  $dom = new DomDocument();
  $dom->loadXML($xml2->asXML());
  $dom->formatOutput = true;

  file_put_contents( $xml_local, $dom->saveXML(), LOCK_EX);
  }

  $dom = new DomDocument();
  $dom->loadXML($xml->asXML());
  $dom->formatOutput = true;

  if(file_put_contents('sitemap-index.xml', $dom->saveXML(), LOCK_EX) === false){
  die('Could not write sitemap-index.xml');
  }

  $handler::setVar('pre_doctitle', _s('XML Sitemaps'));

} catch(Exception $e) {
  G\exception_to_error($e);
}
};