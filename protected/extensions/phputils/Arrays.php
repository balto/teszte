<?php

class Arrays
{
    /**
     * Flatten associative multi dimension array values recursive
     *
     * @param     array
     * @since     1.0
     * @return    array
     */
    public static function array_flatten(array $aNonFlat)
    {
        $result = array();

        $result = $aNonFlat;

        $r = array();

        foreach($result AS $key=>$value){
            $r[] = array('field' => $key, 'message' => implode(', ',$value));
        }

        //array_walk_recursive($aNonFlat, create_function('$val, $key, $obj', 'array_push($obj, $val);'), $result);

        return $r;
    }

    /**
     * Flatten associative multi-dimension arrays recursive, preserve keys
     *
     * @param array $aNonFlat
     * @param array $flat optional, uses for recursion
     */
    public static function array_flatten_with_keys(array $aNonFlat, array $flat=array())
    {
      if (!$aNonFlat || !is_array($aNonFlat)) return '';

      foreach($aNonFlat as $k => $v){
        if (is_array($v)) {
            $flat = self::array_flatten_with_keys($v,$flat);
        } else {
            $flat[$k]=$v;
        }
      }

      return $flat;
    }

    /**
     * A paraméterként megkapott tömbből eldobja a numerikus kulcsokat rekurzívan
     *
     * @param array több dimenziós tömb
     * @return array
     */
    public static function array_values_recursive($array) {
        $temp = array();
        foreach ($array as $key => $value) {
            if (is_numeric($key)) {
                $temp[] = is_array($value) ? self::array_values_recursive($value) : $value;
            } else {
                $temp[$key] = is_array($value) ? self::array_values_recursive($value) : $value;
            }
        }
        return $temp;
    }

    /**
     * Rendezi az adott tömböt a megadott tulajdonság(ok) alapján.
     * pl. Arrays::arfsort($a, array(array('from', 'a'), array('to', 'a')))

     * @param array $a
     * @param array $fl
     */
    public static function arfsort($a, $fl){
        $GLOBALS['__ARFSORT_LIST__'] = $fl;
        setlocale(LC_COLLATE,'hu_HU.utf8');
        usort($a, array('Arrays', 'arfsort_func'));
        return $a;
    }

    public static function arfsort_func($a,$b) {
        foreach ($GLOBALS['__ARFSORT_LIST__'] as $f) {
            $sign = $f[1]=="d" ? -1 : 1;

            if (is_numeric($a[$f[0]])) {
                if ($b[$f[0]] > $a[$f[0]]) $strc = -$sign;
                elseif ($b[$f[0]] < $a[$f[0]]) $strc = $sign;
                else $strc = 0;
            } else {
                $strc = $sign*strnatcasecmp($a[$f[0]],$b[$f[0]]);
            }
            if ($strc != 0){
                return $strc;
            }
        }
        return 0;
    }

    /**
    * Merges any number of arrays of any dimensions, the later overwriting
    * previous keys, unless the key is numeric, in whitch case, duplicated
    * values will not be added.
    *
    * The arrays to be merged are passed as arguments to the function.
    *
    * @access public
    * @return array Resulting array, once all have been merged
    */
    public static function array_merge_replace_recursive() {
        // Holds all the arrays passed
        $params = & func_get_args ();

        // First array is used as the base, everything else overwrites on it
        $return = array_shift ( $params );
        //var_dump($params);
        if (is_array($return)){
        // Merge all arrays on the first array
            foreach ( $params as $array ) {
                //var_dump($array); exit;
                if (is_array($array)){
                    foreach ( $array as $key => $value ) {
                        // Numeric keyed values are added (unless already there)
                        if (is_numeric($key) && (!array_key_exists( $key, $return ))) {
                            $return [$key] = $value;

                            // String keyed values are replaced
                        } else {
                            if (isset ( $return [$key] ) && is_array ( $value ) && is_array ( $return [$key] )) {
                                $return [$key] = self::array_merge_replace_recursive ( $return [$key], $value );
                            } else {
                                if (!isset ( $return [$key] )) {
                                    $return [$key] = $value;
                                } else {
                                    if (!empty($value)) $return [$key] = $value;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $return;
    }
}