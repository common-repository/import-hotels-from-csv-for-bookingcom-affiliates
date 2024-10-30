<?php

class IhfcFrontReplacemets {
/*square150
square200
square300
max1024x768
max200
max300
max500
max600*/
//put your code here
    protected static $_instance = null;

    static function get_instance() {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    function init() {
        // add_filter( 'the_title', array($this,"replaceTitle"), 10, 2 );
        // add_action('the_post', array($this,'selectFeaturedImage'));
        //add_filter('post_thumbnail_html', array($this, 'replaceFeaturedImage'), 10, 2);
        add_filter('wp_get_attachment_image_src', array($this, 'returnImageSrc'), 10, 2);
    }

    /*
      function replaceTitle($title, $id = null) {
      return $title;
      }

      function selectFeaturedImage($post) {
      $postId = $post->ID;
      $photoUrl = get_post_meta($postId, '_ihfc_photo_url', true);
      $featuredImage = get_post_meta($postId, '_thumbnail_id', true);

      if ($photoUrl) {
      if (!$featuredImage)
      update_post_meta($postId, '_thumbnail_id', true);
      }
      }
     */
/*
    function replaceFeaturedImage($html, $postId) {

        $photoUrl = get_post_meta($postId, '_ihfc_photo_url', true);
        $hotelName = get_post_meta($postId, '_ihfc_hotel_name', true);

        if ($photoUrl) {
            $html = sprintf("<img src='%s' alt='%s'/>", $photoUrl, esc_attr($hotelName));
        }

        return $html;
    }
*/
    function returnImageSrc($image, $attachment_id, $size, $icon) {
        $postId=get_the_ID();
        $photoUrl = get_post_meta($postId, '_ihfc_photo_url', true);
        if ($photoUrl) {
            return array($photoUrl, $attachment_id, $size, $icon);
        } else {
            return $image;
        }
    }

}
