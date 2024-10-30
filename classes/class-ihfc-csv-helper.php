<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class-ihfc-cvs-helper
 *
 * @author jcarlos
 */
class IhfcCsvHelper {

//
    protected static $_instance = null;

    /*     * VERY IMPORTANT TSV FIELDS ORDER */
    protected static $_csvValidHeaders = array("hotels" => array(
            "id", "name", "address", "zip", "city_hotel", "cc1", "ufi", "class", "currencycode", "minrate", "maxrate", "preferred", "nr_rooms", "longitude", "latitude", "public_ranking", "hotel_url", "photo_url", "desc_en", "desc_fr", "desc_es", "desc_de", "desc_nl", "desc_it", "desc_pt", "desc_ja", "desc_zh", "desc_pl", "desc_ru", "desc_sv", "desc_ar", "desc_el", "desc_no", "city_unique", "city_preferred", "continent_id", "review_score", "review_nr"
    ));
    private $csvPath = IHFC_PLUGIN_UPLOAD_CSV_DIR_PATH;
    private static $hasValidJsonCache = array();

    private function getCsvColumn($k) {
        return array_search($k, IhfcCsvHelper::$_csvValidHeaders["hotels"]);
    }

    private function getCsvCount() {
        return count(IhfcCsvHelper::$_csvValidHeaders["hotels"]);
    }

    private function getCsvHeader() {
        return IhfcCsvHelper::$_csvValidHeaders["hotels"];
    }

    public function setCsvPath($path) {
        $this->csvPath = $path;
    }

    static function get_instance() {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct($path = IHFC_PLUGIN_UPLOAD_CSV_DIR_PATH) {
        $this->csvPath = $path;
    }

    public function getCvsFiles() {
        $listDef = array();
        $list = glob($this->csvPath . '*.{tsv,csv}', GLOB_BRACE);
        foreach ($list as $i => $file) {
            if ($this->isValid($file))
                $listDef[] = $file;
        }
        return $listDef;
    }

    private function openCsv($file) {
        $stream = null;
        return ($stream = fopen($file, "r"));
    }

    private function closeCsv($stream) {
        fclose($stream);
    }

    private function getLine($st) {
        $line = fgetcsv($st, 38 * 1024, "\t", "}");
        return $line;
    }

    private function isCvsHotelHeaderValid($header) {
        $isValid = false;
        $aDiff1 = array_diff($this->getCsvHeader(), $header);
        if (count($aDiff1) === 0) {
            $isValid = true;
        }
        return $isValid;
    }

    private function isCvsHotelValidLine($lineArray) {
        $isValid = false;
        if (filter_var($lineArray[$this->getCsvColumn("id")], FILTER_VALIDATE_INT) &&
                filter_var($lineArray[$this->getCsvColumn("ufi")], FILTER_VALIDATE_INT) &&
                !empty($lineArray[$this->getCsvColumn("city_hotel")]) &&
                !empty($lineArray[$this->getCsvColumn("name")]) &&
                filter_var($lineArray[$this->getCsvColumn("hotel_url")], FILTER_VALIDATE_URL) &&
                filter_var($lineArray[$this->getCsvColumn("photo_url")], FILTER_VALIDATE_URL)
        ) {
            $isValid = true;
        }
        return $isValid;
    }

    public function isValid($file) {
        $st = $this->openCsv($file);
        $isValid = false;
        if ($st) {
            $header = $this->getLine($st);
            $isValid=$this->isCvsHotelHeaderValid($header) || $this->isCvsHotelValidLine($header);
            $this->closeCsv($st);
        }
        return $isValid;
    }

    public function existCity($cities) {
        $files = $this->getCvsFiles();
        $auxCities = array();
        $errors = array();
        foreach ($files as $i => $file) {
            $auxCities = $this->extractCities($file, $auxCities, $errors, $cities);
        }
        $existCities = array();
        foreach ($cities as $cityID) {
            if (isset($auxCities[$cityID]))
                $existCities[$cityID] = $auxCities[$cityID];
        }
        return $existCities;
    }

    public function extractCities($file, $cities = array(), &$error = array(), $filterCities = null) {
        if (!$this->isValid($file)) {
            return $cities;
        }
        $citiesAux = $this->getCacheFile($file);
        if ($filterCities != null && count($filterCities) > 0) {
            $citiesAuxFiltered = array();
            foreach ($filterCities as $cityID) {
                if (isset($citiesAux[$cityID]))
                    $citiesAuxFiltered[$cityID] = $citiesAux[$cityID];
            }
            $citiesAux = $citiesAuxFiltered;
        }

        foreach ($citiesAux as $k => &$city) {
            if (!isset($cities[$k]))
                $cities[$k] = $city;
            else {
                $cities[$k]["n_hotels"] = $cities[$k]["n_hotels"] + count($citiesAux[$k]["hotel_idx"]);
            }
            if (isset($cities[$k]["hotel_idx"]))
                unset($cities[$k]["hotel_idx"]);
        }
        return $cities;
    }

    public function getAllHotelsFromCities($cities) {
        $files = $this->getCvsFiles();
        $hotels = array();
        $errors = array();
        foreach ($files as $i => $file) {
            // error_log("FILE: $file");
            $hotels = $this->extractHotels($file, $cities, $hotels, $errors);
        }
        return $hotels;
    }

    public function extractHotels($file, $cities, $hotels = array(), &$error = array()) {
        if (!$this->isValid($file)) {
            return $hotels;
        }
        $citiesAux = $this->getCacheFile($file);
        $idxID = $this->getCsvColumn("id");
        foreach ($cities as $ufi => $city) {
            
            if (isset($citiesAux[$ufi])) {
                $city = $citiesAux[$ufi];
                if(!isset($city["hotel_idx"]))
                    $city["hotel_idx"]=array();
                $hotelsIdx = $city["hotel_idx"];
                $creationTime = (isset($city["creation_time"]) ? $city["creation_time"] : time());
                $hotelsAux = $this->getHotel($file, array_values($hotelsIdx));
                $headerFields=$this->getCsvHeader();
                $numHeaderFields=$this->getCsvCount();
                foreach ($hotelsAux as $i => $hotel) {
                    $combinedHotel = array_combine($headerFields, array_slice($hotel,0,$numHeaderFields));
                    $combinedHotel["creation_time"] = $creationTime;
                    $hotels[$hotel[$idxID]] = new IhfcHotel($combinedHotel);
                }
            }
        }

        return $hotels;
    }

    private function sort_by_city_hotel($a, $b) {
        return strcasecmp(iconv('UTF-8', 'ASCII//TRANSLIT', $a['city_hotel']), iconv('UTF-8', 'ASCII//TRANSLIT', $b['city_hotel']));
    }

    public function hasValidCache($file) {
        $cachefile = $file . ".json";
        if (!isset(IhfcCsvHelper::$hasValidJsonCache[$file])) {
            $ret = false;
            if (is_file($cachefile) && filemtime($cachefile) > filemtime($file)) {
                $aux = file_get_contents($cachefile);
                $cache = json_decode($aux, true);
                $ret = ($cache && json_last_error() == JSON_ERROR_NONE);
            }
            IhfcCsvHelper::$hasValidJsonCache[$file] = $ret;
            // error_log("hasValidCache: " . $file . "  =>  " . IhfcCsvHelper::$hasValidJsonCache[$file]);
        }

        return IhfcCsvHelper::$hasValidJsonCache[$file];
    }

    public function getCacheFile($file) {
        if ($this->hasValidCache($file)) {
            $cachefile = $file . ".json";
            $aux = file_get_contents($cachefile);
            $cache = json_decode($aux, true);
            return $cache;
        } else {
            return $this->generateCache($file);
        }
    }

    public function hasValidCacheIndexes() {
        $list = $this->getCvsFiles();
        $ret = true;
        foreach ($list as $i => $file) {
            $ret = $ret && $this->hasValidCache($file);
        }
        return $ret;
    }

    function shutdown() {
        $a = error_get_last();
        if ($a != null) {
            //error_log(print_r($a, true));
            $url = add_query_arg(array('maxError' => $a["type"]), './options-general.php?page=' . IHFC_SETTINGS_OPTIONS_PAGE);
            header('Location: ' . $url, true);
        }
    }

    public function generateCacheIndexes() {
        register_shutdown_function(array($this, "shutdown"));
        $list = $this->getCvsFiles();
        $ret = array();
        foreach ($list as $i => $file) {
            if ($this->hasValidCache($file)) {
                $ret[$file] = true;
            } else {
                $ret[$file] = (count($this->generateCache($file)) > 0);
            }
        }
        return $ret;
    }

    public function generateCache($file) {
        if (!$this->isValid($file))
            return $cities;
        $st = $this->openCsv($file);
        $filePointer = ftell($st);
        $idxCity = $this->getCsvColumn("city_hotel");
        $idxUFI = $this->getCsvColumn("ufi");
        $idxID = $this->getCsvColumn("id");
        $countHeader = $this->getCsvCount();
        $filePointer = ftell($st);
        while (($line = $this->getLine($st)) !== false) {
            if ($this->isCvsHotelValidLine($line)) {
                $ufi = $line[$idxUFI];
                $cityName = $line[$idxCity];
                $idHotel = $line[$idxID];
                if (!isset($cities[$ufi])) {
                    $cities[$line[$idxUFI]] = array(
                        "city_hotel" => $cityName,
                        "ufi" => $ufi,
                        "n_hotels" => 0,
                        "creation_time" => time(),
                        "hotel_idx" => array()
                    );
                }
                if (!isset($cities[$ufi]["hotel_idx"][$idHotel])) {
                    $cities[$ufi]["hotel_idx"][$idHotel] = $filePointer;
                    $cities[$line[$idxUFI]]["n_hotels"] ++;
                }
            }
            $filePointer = ftell($st);
        }
        $this->closeCsv($st);
        file_put_contents($file . ".json", json_encode($cities));
        //uasort($cities, array($this, "sort_by_city_hotel"));
        return $cities;
    }

    public function getHotel($file, $pointers) {
        $hotels = array();
        if (is_numeric($pointers))
            $pointers = array($pointers);
        if ($this->isValid($file)) {
            $st = $this->openCsv($file);
            foreach ($pointers as $i => $pointer) {
                // print_r($i." - ".$pointer."\n");
                fseek($st, $pointer);

                $hotel = $this->getLine($st);
                // print_r($hotel);
                $hotels[] = $hotel;
            }
            $this->closeCsv($st);
        }
        return $hotels;
    }

}
