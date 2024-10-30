<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class-ihfc-replace
 *
 * @author jcarlos
 */
class IhfcReplace {

//put your code here
    private $_hotel = null;
    private $_replacement = null;
    private $_replacementKeys = null;
    private $_replacementValues = null;
    //visual composer compatibility
    private $_replacementVC = null;
    private $_replacementKeysVC = null;
    private $_replacementValuesVC = null;
    
    public function setHotel(IhfcHotel $hotel) {
        $this->_hotel = $hotel;
        $this->_replacement = $this->createReplaceArrays();
        $this->_replacementVC = $this->createReplaceArraysVC();
        $this->_replacementKeys = array_keys($this->_replacement);
        $this->_replacementValues = array_values($this->_replacement);
        $this->_replacementKeysVC = array_keys($this->_replacementVC);
        $this->_replacementValuesVC = array_values($this->_replacementVC);
        error_log(print_r($this,true));
    }

    private function createReplaceArrays() {
        $prop = $this->_hotel->getHotelProperties();
        $trans = array();
        foreach ($prop as $k => $v) {
            $trans['/{{hotel_' . strtolower($k) . '}}/i'] = $v;
        }
        return $trans;
    }
    
    private function createReplaceArraysVC() {
        $prop = $this->_hotel->getHotelProperties();
        $trans = array();
        foreach ($prop as $k => $v) {
            $keybase64=base64_encode(rawurlencode('{{hotel_' . strtolower($k) . '}}'));
            $valuebase64=base64_encode(rawurlencode($v));
            $trans['/'.$keybase64.'/'] = $valuebase64;
        }
        return $trans;
    }
    
    private function replace1($string)
    {
        return preg_replace($this->_replacementKeys, $this->_replacementValues, $string);
    }

    
    private function replace2OLD($string)
    {
        return preg_replace($this->_replacementKeysVC, $this->_replacementValuesVC, $string);   
    }
    
    private function replace2($string)
    {
        $matches=array();
        $res=preg_match_all('~\]([a-zA-Z0-9+/=]+)\[~', $string, $matches);  
        $matches=$matches[1];
        $replacements=array();
        foreach($matches as $match)
        {
            $str= rawurldecode(base64_decode($match));
            $resStr=$this->replace1($str);
            $replacements['/'.$match.'/']= base64_encode(rawurlencode($resStr));
        }
      //  error_log(print_r($replacements,true));
       return preg_replace(array_keys($replacements),  array_values($replacements), $string);
    }
    
    public function replace($string) {
        $r1=$this->replace1($string);
        $r2=$this->replace2($r1);
        return $r2;
    }

}
