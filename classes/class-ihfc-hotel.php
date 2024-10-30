<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class-ihfc-hotel
 *
 * @author jcarlos
 */
class IhfcHotel {

//put your code here
    static public $keys = array(
        "id", "name", "address", "zip", "city_hotel", "cc1", "ufi", "class", "currencycode", "minrate", "maxrate", "preferred", "nr_rooms", "longitude", "latitude", "public_ranking", "hotel_url", "photo_url", "desc_en", "desc_fr", "desc_es", "desc_de", "desc_nl", "desc_it", "desc_pt", "desc_ja", "desc_zh", "desc_pl", "desc_ru", "desc_sv", "desc_ar", "desc_el", "desc_no", "city_unique", "city_preferred", "continent_id", "review_score", "review_nr"
    );
    var $_prop = null;

    function __construct($h) {
        if (!isset($h["creation_time"])) {
            $h["creation_time"] = time();
        }
        $this->_prop = $h;
    }

    function setHotelProperties($h) {
        $this->_prop = $h;
    }

    function getKey($k) {
        return $this->_prop[$k];
    }

    function setKey($k, $v) {
        $this->_prop[$k] = $v;
    }

    function getId() {
        return $this->getKey("id");
    }

    function getName() {
        return $this->getKey("name");
    }

    function getCityHotel() {
        return $this->getKey("city_hotel");
    }

    function getPhotoUrl() {
        return $this->getKey("photo_url");
    }

    function getUfi() {
        return $this->getKey("ufi");
    }

    function getHotelProperties() {
        return $this->_prop;
    }

    function getCreationTime() {
        return $this->getKey("creation_time");
    }

}
