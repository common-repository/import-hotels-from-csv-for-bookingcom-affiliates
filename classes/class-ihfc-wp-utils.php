<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class-ihfc-wp-utils
 *
 * @author jcarlos
 */
class IhfcWpUtils {

//put your code here
    function getPostTypes() {
        $args = array(
            'public' => true
        );
        $output = 'names'; // names or objects, note names is the default
        $operator = 'and'; // 'and' or 'or'
        $post_types = get_post_types($args, $output, $operator);
        return $post_types;
    }

    function getTaxonomies() {
        $args = array(
            'public' => true
        );
        $output = 'names'; // or objects
        $operator = 'and'; // 'and' or 'or'
        $taxonomies = get_taxonomies($args, $output, $operator);
        return $taxonomies;
    }

    function getTerms() {
        $terms = get_terms(array(
            'hide_empty' => false,
        ));
        return $terms;
    }

    /**
     * 
     * 
     */
    function insertPost($args) {
        $post_id = wp_insert_post($args, true);
        return $post_id;
    }

    function updatePostStatus($post_id, $status) {
        $current_post = get_post($post_id, 'ARRAY_A');
        $current_post['post_status'] = $status;
        return wp_update_post($current_post);
    }

    function clonePost($postIdToClone, $argsToOverride) {
        $post = get_post($postIdToClone, 'ARRAY_A', 'raw');
        $argsToOverride["post_type"] = $post["post_type"];
        $argsToOverride["post_author"] = $post["post_author"];
        $argsToOverride["post_category"] = $post["post_category"];
        $argsToOverride["tags_input"] = $post["tags_input"];
        $argsToOverride["post_status"] = $post["post_status"];
        $postId = wp_insert_post($argsToOverride, true);
        return $postId;
    }

    function getPost($args) {
        $post = get_post($args, 'ARRAY_A', 'raw');
        return $post;
    }

    function getPosts($args) {
        $posts = get_posts($args);
        $aposts = array();
        foreach ($posts as $p) {
            $aposts[] = $p->to_array();
        }
        return $aposts;
    }

    function getPostsByHotels() {
        $args = array(
            'numberposts' => -1,
            'post_status' => "any",
            'meta_key' => '_ihfc_hotel_id'
        );

        $posts = $this->getPosts($args);
        return $posts;
    }

    function getHotelIdByPostId($postId) {
        return get_post_meta($postId, '_ihfc_hotel_id', true);
    }

    function getPostsByHotelId($hotel,$postType="post") {
        $id = -1;
        if ($hotel instanceof IhfcHotel) {
            $id = $hotel->getId();
        } else {
            $id = $hotel;
        }

        $args = array(
            'numberposts' => -1,
            'post_status' => "any",
            'suppress_filters' => true,
            'post_type' => $postType,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_ihfc_hotel_id',
                    'value' => $id,
                    'compare' => '='
                ),
                array(
                    'key' => '_ihfc_wpml_is_original',
                    'value' => '1',
                    'compare' => '='
                )
            )
        );
        error_log(print_r($args,true));
        $posts = $this->getPosts($args);
        return $posts;
    }

    /*     * *** WPML ** */

    function getDefaultLanguage() {
        global $sitepress;
        if ($sitepress && $this->isWPMLActive()) {
            $lang = $sitepress->get_default_language();
        } else {
            $lang = (WPLANG != "" ? WPLANG : get_locale());
        }
        return $lang;
    }

    function getLanguages() {
        $langs = null;
        if ($this->isWPMLActive()) {
            $langs = icl_get_languages('skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str');
        } else {
            $langs[$this->getDefaultLanguage()] = array("original" => 1);
        }
        return $langs;
    }

    function isWPMLActive() {
        $ret = function_exists('icl_get_languages');
        //error_log("isWPMLActive(): " . $ret);
        return $ret;
    }

    function getWPMLPostTranlations($postId, $postType) {
        global $sitepress;
        $translations = array();
        if ($sitepress && $this->isWPMLActive()) {
            $trid = $sitepress->get_element_trid($postId, 'post_' . $postType);
            $translations = $sitepress->get_element_translations($trid, 'post_' . $postType);
        } else {
            $obj = new stdClass;
            $obj->element_id = $postId;
            $obj->original = 1;
            $obj->language_code = $this->getDefaultLanguage();
            $translations[$this->getDefaultLanguage()] = $obj;
        }
        return $translations;
    }

    function getWPMLLanguages() {
        return icl_get_languages('skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str');
    }

    function getDefaultLangObjectId($id, $type) {
        if (function_exists('icl_object_id')) {
            $id = icl_object_id($id, $type, false, $this->getDefaultLanguage());
        }
        return $id;
    }

    function getLangObjectId($id, $type, $lang) {
        if (function_exists('icl_object_id')) {
            $id = icl_object_id($id, $type, false, $lang);
        }
        return $id;
    }

    function lang_object_ids($ids_array, $type) {
        if (function_exists('icl_object_id')) {
            $res = array();
            foreach ($ids_array as $id) {
                $xlat = icl_object_id($id, $type, false);
                if (!is_null($xlat))
                    $res[] = $xlat;
            }
            return $res;
        } else {
            return $ids_array;
        }
    }

}
