<?php

class Strings
{
    private static $arr_ones = array("", "egy", "kettő", "három", "négy", "öt", "hat", "hét", "nyolc", "kilenc", "tíz");
    private static $arr_tens = array("", "tizen", "huszon", "harminc", "negyven", "ötven", "hatvan", "hetven", "nyolcvan", "kilencven");
    private static $arr_tens_alone = array("", "tíz", "húsz", "harminc", "negyven", "ötven", "hatvan", "hetven", "nyolcvan", "kilencven");

    public static function fixIdentifier($string) {
        return str_replace(array(' ',',','.'), '_', $string);
    }

    public static function numberToRoman($num){

        $result = '';

        // Make sure that we only use the integer portion of the value
        $n = intval($num);

        // Declare a lookup array that we will use to traverse the number:
        $lookup = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
            'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
            'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);

        foreach ($lookup as $roman => $value){
            // Determine the number of matches
            $matches = intval($n / $value);

            // Store that many characters
            $result .= str_repeat($roman, $matches);

            // Substract that from the number
            $n = $n % $value;
        }

        // The Roman numeral should be built, return it
        return $result;
    }
    
    public static function utf8tolatin1($string){        
        $mit = array("\xC3\x81","\xC3\x89","\xC3\x8D","\xC3\x93","\xC3\x96","\xC5\x90","\xC3\x9A","\xC3\x9C","\xC5\xB0","\xC3\x94","\xC3\x95","\xC3\x9B","\xC5\xA8","\xC3\xA1","\xC3\xA9","\xC3\xAD","\xC3\xB3","\xC3\xB6","\xC5\x91","\xC3\xBA","\xC3\xBC","\xC5\xB1","\xC3\xB4","\xC3\xB5","\xC5\x91","\xC3\xBB","\xC5\xA9");
        $mire = array("Á","É","Í","Ó","Ö","Ö","Ú","Ü","Ü","Ô","Õ","Û","Ü","á","é","í","ó","ö","ö","ú","ü","ü","ô","õ","ő","û","ü");
            
        $string = str_replace($mit, $mire, $string);
        
        return $string;
    }
    
    public static function replaceUtf8NewLine($new_line, $string){
        $string = preg_replace("/[\x{2028}\x{2029}]/u",$new_line,$string);
        $string = preg_replace("/[\x{000A}]/u",$new_line,$string);
        $string = preg_replace("/[\x{000D}]/u","",$string);
        
        return $string;
    }

    public static function numberToText($number) {
        $number = round($number);
        $res = "";
        if ($number < 0) { $res = "mínusz "; $number *= -1; }
        if ($number > 999999999) return "$number --- túl nagy szám";

        $mills = floor($number / 1000000);  /* Millions (giga) */
        $number -= $mills * 1000000;

        $thousands = floor($number / 1000);     /* Thousands (kilo) */
        $number -= $thousands * 1000;
        $hundreds = floor($number / 100);      /* Hundreds (hecto) */
        $number -= $hundreds * 100;
        $tens = floor($number / 10);       /* Tens (deca) */
        $ones = $number % 10;               /* Ones */

        if ($mills) $res .= self::numberToText($mills)."millió" . ($thousands || $hundreds || $tens || $ones ? '-' : '')  ;
        if ($thousands) $res .= self::numberToText($thousands) . "ezer" . ($hundreds || $tens || $ones ? '-' : '');
        if ($hundreds) $res .= self::numberToText($hundreds) . "száz";

        if ($tens || $ones){
            if ($tens == 0){
                $res .= self::$arr_ones[$ones];
            } else{
                if ($ones) $res .= self::$arr_tens[$tens].self::$arr_ones[$ones];
                      else $res .= self::$arr_tens_alone[$tens];
            }
        }

        if (empty($res)) $res = "nulla";

        return $res;
    }
}