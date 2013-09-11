<?php

class Maths {

    /**
    * Legkisebb közös többszörös
    *
    * @param array $items
    * @return integer
    */
    public static function lcm_arr($items){
        //Input: An Array of numbers
        //Output: The LCM of the numbers
        while(2 <= count($items)){
            array_push($items, self::lcm(array_shift($items), array_shift($items)));
        }
        return reset($items);
    }

    /**
     * Legnagyobb közös osztó
     *
     * @param integer $n
     * @param integer $m
     * @return integer
     */
    public static function gcd($n, $m) {
        if (!$m) return $n;

        return self::gcd($m, $n%$m);
    }


    /**
     * Legkisebb közös többszörös
     *
     * @param integer $n
     * @param integer $m
     * @return integer
     */
    public static function lcm($n, $m) {
        return $m * ($n/self::gcd($n,$m));
    }

    /**
     * Luhn algoritmus - ellenőrző összeg képzés/ellenőrzés
     * http://hu.wikipedia.org/wiki/Luhn-formula
     *
     * Ha ellenőrizni kell, akkor 0-t kell kapni,
     * generálásnál a számot ki kell egészíteni 0-val, majd 10 - a kapott eredmény lesz az ellenőrző összeg
     *
     * @static
     * @param $str integer
     * @return bool
     */
    public static function luhn($str) {
        $len = strlen($str);
        $odd = !$len%2;
        $sum = 0;
        for($i=0; $i<$len; ++$i) {
            $n = 0 + $str[$i];
            $odd = !$odd;
            if ($odd) {
                $sum += $n;
            } else {
                $x = 2*$n;
                $sum += $x>9 ? $x-9 : $x;
            }
        }
        return $sum%10;
    }

    public static function ean13_check_digit($digits){
        //first change digits to a string so that we can access individual numbers
        $digits =(string)$digits;
        // 1. Add the values of the digits in the even-numbered positions: 2, 4, 6, etc.
        $even_sum = $digits{1} + $digits{3} + $digits{5} + $digits{7} + $digits{9} + $digits{11};
        // 2. Multiply this result by 3.
        $even_sum_three = $even_sum * 3;
        // 3. Add the values of the digits in the odd-numbered positions: 1, 3, 5, etc.
        $odd_sum = $digits{0} + $digits{2} + $digits{4} + $digits{6} + $digits{8} + $digits{10};
        // 4. Sum the results of steps 2 and 3.
        $total_sum = $even_sum_three + $odd_sum;
        // 5. The check character is the smallest number which, when added to the result in step 4,  produces a multiple of 10.
        $next_ten = (ceil($total_sum/10))*10;
        $check_digit = $next_ten - $total_sum;
        return $digits . $check_digit;
    }
}