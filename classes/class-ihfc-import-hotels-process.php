<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
  DELETE FROM  `wp_posts` WHERE ID IN (
  SELECT post_id
  FROM wp_postmeta
  WHERE meta_key =  '_ihfc_hotel'
  )
 *  */

/**
 * Description of class-ihfc-wp-utils
 *
 * @author jcarlos
 */
class IhfcImportHotelsProcess {

    /** @var IhfcCsvHelper csvHelper */
    protected $csvHelper = null;

    /** @var IhfcWpUtils wpHelper */
    protected $wpHelper = null;
    protected $templatePost = null;
    protected $templatePostId = null;
    protected $messages = array();
    protected $postStatus = "draft";
    protected $hotels = null;
    protected $cities = null;
    protected $templatePostThumb = false;
    protected $isWPMLActive = false;
    protected $wpmlLanguages = null;
    protected $wpInsertCounter = 0;
    protected $wpInsertHotelsCounter = 0;

    function getTemplatePost() {
        return $this->templatePost;
    }

    function getTemplatePostId() {
        return $this->templatePostId;
    }

    function setTemplatePost($templatePost) {
        $this->templatePost = $templatePost;
    }

    function setTemplatePostId($templatePostId) {
        $this->templatePostId = $templatePostId;
    }

    public function __construct($csvHelper = null, $wpHelper = null) {
        $this->csvHelper = $csvHelper;
        $this->wpHelper = $wpHelper;
    }

    public function setCvsHelper($ch) {
        $this->csvHelper = $ch;
    }

    public function setWpHelper($wh) {
        $this->wpHelper = $wh;
    }

    public function getCvsHelper() {
        return $this->csvHelper;
    }

    function getWpHelper() {
        return $this->wpHelper;
    }

    private function generateHotelPost(IhfcHotel $hotel, $tplPost) {
        $newPost = array();
        $fields = array('comment_status', 'ping_status', 'post_author', 'post_content',
            'post_excerpt', 'post_parent', 'post_password', 'post_title',
            'post_type', 'to_ping', 'menu_order', 'tags_input');
// 'post_name',
        foreach ($fields as $v) {
            $newPost[$v] = $tplPost[$v];
        }
        $poststatus = "draft";
        if ($this->postStatus == "draft" || $this->postStatus == "publish") {
            $poststatus = $this->postStatus;
        }
        $newPost['post_status'] = $poststatus;
        $hr = new IhfcReplace();
        $hr->setHotel($hotel);
        foreach (array("post_title", "post_excerpt", "post_content") as $k)
            $newPost[$k] = $hr->replace($tplPost[$k]);
        $newPost["post_name"] = sanitize_title($newPost["post_title"]);
        return $newPost;
    }

    private function copyPostMetadata($orgPostId, $destPostId) {
        global $wpdb;
        $exceptions = " AND meta_key NOT IN('_wp_old_slug', '_edit_last', '_edit_lock', '_icl_translator_note','_icl_lang_duplicate_of', '_wpml_media_duplicate', '_wpml_media_featured')";
        $avoidDuplication = " AND meta_key NOT IN (SELECT meta_key from " . $wpdb->postmeta . " where post_id=$destPostId) ";
        $sql_query = "insert into " . $wpdb->postmeta . " (post_id,meta_key,meta_value) " .
                " SELECT $destPostId,meta_key, meta_value FROM " . $wpdb->postmeta .
                " WHERE post_id= $orgPostId and meta_key NOT LIKE '\\_ihfc\\_%' $exceptions $avoidDuplication";
        error_log($sql_query);
        $wpdb->query($sql_query);
        //UPDATE metakeys
        $sql_query="UPDATE ".$wpdb->postmeta." PM1 INNER JOIN ".$wpdb->postmeta." PM2 ON PM1.meta_key = PM2.meta_key AND PM2.post_id=$orgPostId AND PM1.post_id=$destPostId SET PM1.meta_value = PM2.meta_value";
        error_log($sql_query);
        $wpdb->query($sql_query);
    }

    private function copyPostTerms($orgPostId, $destPostId) {
        global $wpdb;
        $avoidDuplication = " AND term_taxonomy_id NOT IN (SELECT term_taxonomy_id from " . $wpdb->term_relationships . " where object_id=$destPostId) ";
        $sql_query = "insert into " . $wpdb->term_relationships . " (object_id,term_taxonomy_id,term_order) " .
                " SELECT $destPostId,term_taxonomy_id,term_order FROM " . $wpdb->term_relationships .
                " WHERE object_id= $orgPostId $avoidDuplication";
        $wpdb->query($sql_query);
    }

    public function insertPostsHotel(IhfcHotel $hotel, $postType, $postHotelId = null) {
        global $sitepress;
        //get translations form template
        $translations = $this->wpHelper->getWPMLPostTranlations($this->templatePost["ID"], $this->templatePost["post_type"]);
        $res = array();
        if ($translations) {
            $defLang = $this->wpHelper->getDefaultLanguage();
            /**
             * trans => {element_id=> postid , language_code=>"en|es|...", original=>1 idioma defecto 0 otros
             */
            $trans = $translations[$defLang];
            $_POST['icl_post_language'] = $language_code = $defLang;
            $postId = $this->insertPostHotel($hotel, $trans, $postHotelId);
            update_post_meta($postId, '_ihfc_wpml_is_original', 1, true);
            update_post_meta($postId, '_ihfc_wpml_original_post_id', $postId, true);
            $res[$defLang] = $postId;
            if ($this->wpHelper->isWPMLActive()) {
                $defTrid = $sitepress->get_element_trid($res[$defLang], "post_" . $postType);
                error_log("DEFLang: $defLang TRID: " . $defTrid);
                foreach ($translations as $lang => $trans) {
                    if (!empty($lang) && $lang != $defLang) {
                        //get translated ID
                        $_POST['icl_post_language'] = $language_code = $lang;
                        $tradPostId = $this->wpHelper->getLangObjectId($postHotelId, $postType, $lang);
                        $postId = $this->insertPostHotel($hotel, $trans, $tradPostId);
                        $res[$lang] = $postId;
                        update_post_meta($postId, '_ihfc_wpml_is_original', 0, true);
                        update_post_meta($postId, '_ihfc_wpml_original_post_id', $res[$defLang], true);
                        /*
                          $iclLangDuplicateOf = get_post_meta($postId, "_icl_lang_duplicate_of", true);
                          if ($iclLangDuplicateOf > 0) {
                          update_post_meta($postId, '_icl_lang_duplicate_of', $res[$defLang], true);
                          }
                         * 
                         */
                        if ($postId != $tradPostId) {
                            $sitepress->set_element_language_details($postId, 'post_' . $postType, $defTrid, $lang);
                        }
                    }
                }
            }
        }
        return $res;
    }

    public function insertPostHotel(IhfcHotel $hotel, $trans, $postHotelId = null) {
        $tplPost = $this->wpHelper->getPost($trans->element_id);
        if ($postHotelId != null && $postHotelId > 0) {
            $tplPostTime = get_post_modified_time('U', false, $trans->element_id);
            $postHotelTime = get_post_modified_time('U', false, $postHotelId);
            error_log("tplPostTime: $tplPostTime");
            error_log("postHotelTime: $postHotelTime");
            if ($tplPostTime < $postHotelTime && $postHotelTime > $hotel->getCreationTime()) {
                /** only change status if it is needed * */
                $postStatus = get_post_status($postHotelId);
                if ($this->postStatus != "unchanged" && $this->postStatus != $postStatus) {
                    $this->wpHelper->updatePostStatus($postHotelId, $this->postStatus);
                    $this->wpInsertCounter++;
                }
                return $postHotelId;
            }            
        }
        //New or need to be updated

        $hotelPost = $this->generateHotelPost($hotel, $tplPost);
        $newPostHotelId=null;
        if ($postHotelId != null && $postHotelId > 0) {
            $hotelPost["ID"] = $postHotelId;
            if ($this->postStatus == "unchanged") {
                $hotelPost["post_status"] = get_post_status($postHotelId);
            }
            $newPostHotelId = wp_update_post($hotelPost, true);
        }
        else
        {
            $newPostHotelId = wp_insert_post($hotelPost, true);
        }
        
        $this->wpInsertCounter++;
        if ($newPostHotelId) {
            $this->copyPostMetadata($tplPost["ID"], $newPostHotelId);
            $this->copyPostTerms($tplPost["ID"], $newPostHotelId);
            $this->updateHotelInformationPostMetadata($hotel, $newPostHotelId, $tplPost);
            update_post_meta($newPostHotelId, '_ihfc_hotel_id', $hotel->getId(), true);
            if ($newPostHotelId != $postHotelId) {

                update_post_meta($newPostHotelId, '_ihfc_date_add', date('Y-m-d H:i:s'), true);
            }
        }
        return $newPostHotelId;
    }

    private function updateHotelInformationPostMetadata(IhfcHotel $hotel, $postHotelId, $tplPost) {
        update_post_meta($postHotelId, '_ihfc_tpl_post_id', $tplPost["ID"], true);
        update_post_meta($postHotelId, "_ihfc_hotel", serialize($hotel->getHotelProperties()), true);
        update_post_meta($postHotelId, "_ihfc_date_modification", date('Y-m-d H:i:s'), true);
        update_post_meta($postHotelId, '_ihfc_photo_url', $hotel->getPhotoUrl(), true);
        update_post_meta($postHotelId, '_ihfc_hotel_name', $hotel->getName(), true);
        if ($this->templatePostThumb) {
            update_post_meta($postHotelId, '_thumbnail_id', $this->templatePostThumb, true);
        }
    }

    public function importHotel(IhfcHotel $hotel, &$messages) {
        $tplPost = $this->templatePost;
        $posts = $this->wpHelper->getPostsByHotelId($hotel,$tplPost["post_type"]);        
        $om = __("Hotel '%s' with booking id: %s %s. Wordpress Post id: %s (status: %s & language: %s)", IHFC_TEXT_DOMAIN);
        $result = array();
        if (count($posts) == 0) {
            $hotelPostIds = $this->insertPostsHotel($hotel, $tplPost["post_type"]);
            if (count($hotelPostIds) > 0) {
                $this->wpInsertHotelsCounter++;
                foreach ($hotelPostIds as $lang => $hotelPostId) {
                    $result[$hotelPostId] = $hotel->getId();
                    $messages[] = sprintf($om, $hotel->getName(), $hotel->getId(), "CREATED", $hotelPostId, get_post_status($hotelPostId), $lang);
                }
            } else {
                $messages[] = sprintf(__("Hotel '%s' with booking id: %s. ERROR", IHFC_TEXT_DOMAIN), $hotel->getName(), $hotel->getId());
            }
        } else {
            $this->wpInsertCounter = 0;
            foreach ($posts as $hotelPost) {
                $hotelPostId = $hotelPost["ID"];
                $hotelPostIds = $this->insertPostsHotel($hotel, $tplPost["post_type"], $hotelPostId);
                if (count($hotelPostIds) > 0) {
                    if ($this->wpInsertCounter > 0) {
                        $this->wpInsertHotelsCounter++;
                        foreach ($hotelPostIds as $lang => $hotelPostId) {
                            $result[$hotelPostId] = $hotel->getId();
                            $messages[] = sprintf($om, $hotel->getName(), $hotel->getId(), "UPDATED", $hotelPostId, get_post_status($hotelPostId), $lang);
                        }
                    } else {
                        //not modification required
                        $messages[] = sprintf($om, $hotel->getName(), $hotel->getId(), "NOT MODIFIED", implode(',', array_values($hotelPostIds)), 'unchanged', implode(',', array_keys($hotelPostIds)));
                    }
                } else {
                    $messages[] = sprintf(__("Hotel '%s' with booking id: %s. ERROR", IHFC_TEXT_DOMAIN), $hotel->getName(), $hotel->getId());
                }
            }
        }
        return $result;
    }

    function importHotels($cities, $templatePostId, $postStatus, &$messages = array()) {
        $tini=time();
       // error_log("Start importHotels");
        $this->templatePostId = $templatePostId;
        $this->templatePost = $this->wpHelper->getPost($templatePostId);
        $this->templatePostThumb = get_post_meta($templatePostId, "_thumbnail_id", true);
        $this->messages = $messages;
        $this->result = array();
        $this->postStatus = $postStatus;
        $this->cities = $cities;
        $this->hotels = $this->csvHelper->getAllHotelsFromCities($cities);
        uasort($this->hotels, array($this, "sort_by_hotel_name"));
        /** @var IhfcHotel $hotel */
        foreach ($this->hotels as $hotel) {
            $this->result[] = $this->importHotel($hotel, $messages);
            if ($this->wpInsertHotelsCounter >= IHFC_MAX_HOTELS_IMPORT_AT_ONCE)
                break;
        }
      //  error_log("END importHotels ".(time()-$tini)." secs");
        return $this->result;
    }

    private function sort_by_hotel_name(IhfcHotel $a, IhfcHotel $b) {
        return strcasecmp(iconv('UTF-8', 'ASCII//TRANSLIT', $a->getName()), iconv('UTF-8', 'ASCII//TRANSLIT', $b->getName()));
    }

}
