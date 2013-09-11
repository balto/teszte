<?php

class DateIntervals
{
    private $store = array();
    private $overlapped_intervals = array();
    private $closed_interval = false;
    private $interval_limits = array();
    private $interval_value = null;

    public function __construct($from = '', $to = '', $value = null)
    {
        if ($from == '' xor $to == '') throw new Exception('Invalid DateInterval constructor!');

        if ($from != '') {
            $this->closed_interval = true;
            $this->interval_limits = Dates::convert2DatesToTimestamp($from, $to);
            $this->interval_value = $value;

            if ($this->interval_limits[0] == $this->interval_limits[1]) throw new Exception('Invalid date-interval, start date equal end date');
        }
    }

    public function __destruct()
    {
        unset($this->store, $this->overlapped_intervals, $this->closed_interval, $this->interval_limits);
        unset($this);
    }

    /**
     * Az intervallum tárolóba betesz egy új intervallumot. Ha zárt az intervallum, akkor
     * a határok mentén csonkolja
     * Visszaadja a _ténylegesen_ letárolt intervallumot
     *
     * @param any_type $key    ilyen kulcsra teszi le az adatot, az azonos kulcsú elemek metszete, átfedése, stb. lekérdezhető
     * @param string $from
     * @param string $to
     * @param float $value default null
     * @return array(timestamp, timestamp)
     */
    public function push($key, $from, $to, $value = null)
    {
        $sfDate_from = new sfDate($from);
        $date_from_ts = $sfDate_from->get();
        $sfDate_to = new sfDate($to);
        $date_to_ts = $sfDate_to->get();

        // fordított sorrendben vannak a dátumok - vissza kell fordítani
        if ($date_from_ts > $date_to_ts) list($date_from_ts, $date_to_ts) = array($date_to_ts, $date_from_ts);

        if ($this->isIntervalOverlap($key, $date_from_ts, $date_to_ts)) {
            // begyűjtjük az átfedő intervallumokat
            if (!isset($this->overlapped_intervals[$key])) $this->overlapped_intervals[$key] = array();
            $this->overlapped_intervals[$key][] = $from . ' - ' . $to;
        }

        // tároljuk az újat a store-ban
        if (!isset($this->store[$key])) $this->store[$key] = array();
        if ($this->closed_interval) {
            $date_from_ts = max($date_from_ts, $this->interval_limits[0]);
            $date_to_ts   = min($date_to_ts,   $this->interval_limits[1]);
        }
        $this->store[$key][] = array('from' => $date_from_ts, 'to' => $date_to_ts, 'value' => $value);

        return array($date_from_ts, $date_to_ts);
    }

    public function intersect($key, $from, $to, $value = null)
    {
        $sfDate_from = new sfDate($from);
        $date_from_ts = $sfDate_from->get();
        $sfDate_to = new sfDate($to);
        $date_to_ts = $sfDate_to->get();

        // fordított sorrendben vannak a dátumok - vissza kell fordítani
        if ($date_from_ts > $date_to_ts) list($date_from_ts, $date_to_ts) = array($date_to_ts, $date_from_ts);

        if ($this->isIntervalOverlap($key, $date_from_ts, $date_to_ts)) {
            $this->cutIntervalsWith($key, $date_from_ts, $date_to_ts);
        }

        // tároljuk az új adatot a store-ban
        if (!isset($this->store[$key])) $this->store[$key] = array();
        if ($this->closed_interval) {
            $date_from_ts = max($date_from_ts, $this->interval_limits[0]);
            $date_to_ts   = min($date_to_ts,   $this->interval_limits[1]);
        }
        $this->store[$key][] = array('from' => $date_from_ts, 'to' => $date_to_ts, 'value' => $value);

        return array($date_from_ts, $date_to_ts);
    }

    public function getOverlappedIntervals()
    {
        return $this->overlapped_intervals;
    }

    public function getIntervals($key)
    {
        if (!isset($this->store[$key])) return array();

        return $this->store[$key];
    }

    public function getIntervalsOrdered($key)
    {
        if (!isset($this->store[$key])) return array();
        if (empty($this->store[$key])) return array();

        return Arrays::arfsort($this->getIntervals($key), array( array('from','a') ));
    }

    public function getIntervalsClosedUp($key)
    {
        $sorted = $this->getIntervalsOrdered($key);

        $new_intervals = array();
        $last_to = 0;
        $last_value = null;
        foreach ($sorted as $interval) {
            if ($last_to == $interval['from'] && $this->compare($last_value, $interval['value'])) {
                $new_intervals[count($new_intervals)-1]['to'] = $interval['to'];
            } else {
                $new_intervals[] = $interval;
            }

            $last_to = $interval['to'];
            $last_value = $interval['value'];
        }

        return $new_intervals;
    }

    public function makeIntervalsClosedUp($key)
    {
        $this->store[$key] = $this->getIntervalsClosedUp($key);
    }

    public function hasHoles($key)
    {
        if (!$this->closed_interval) throw new Exception('hasHoles not callable on open interval');

        $sorted = $this->getIntervalsOrdered($key);

        // nincs egyetlen intervallum sem => van lyuk
        if (empty($sorted)) return true;

        // Akkor nincs lyuk, ha a sorbarendezett intervallumok
        // - első eleme az intervallum elejéb kezdődik
        // - utolsó eleme az intervallum végén ér véget
        // - minden eleme pont ott kezdődik, ahol az előzőnek vége van
        $last_to = $this->interval_limits[0];
        foreach ($sorted as $interval) {
            if ($interval['from'] != $last_to) return true;
            $last_to = $interval['to'];
        }
        if ($last_to != $this->interval_limits[1]) return true;

        return false;
    }

    public function getHoles($key)
    {
        if (!$this->closed_interval) throw new Exception('getHoles not callable on open interval');

        $sorted = $this->getIntervalsOrdered($key);

        // nincs egyetlen intervallum sem => a teljes zárt intervallum egy nagy lyuk
        if (empty($sorted)) return array(
            'from' => $this->interval_limits[0],
            'to' => $this->interval_limits[1],
            'value' => $this->interval_value
        );

        $holes = array();
        $last_to = $this->interval_limits[0];
        foreach ($sorted as $interval) {
            if ($last_to != $interval['from']) $holes[] = array('from' => $last_to, 'to' => $interval['from'], 'value' => $this->interval_value);
            $last_to = $interval['to'];
        }
        if ($last_to != $this->interval_limits[1]) $holes[] = array('from' => $last_to, 'to' => $this->interval_limits[1], 'value' => $this->interval_value);

        return $holes;
    }

    /**
     * Visszaadja a tárolt intervallumok kulcsait
     *
     * @return array
     */
    public function getKeys()
    {
        return array_keys($this->store);
    }

    /**
     *
     * A különböző kulcsokon szereplő azonos intervallumokat egymást követő egyenlő hosszú időintervallumokra darabolja
     * @param array $keys
     */
    public function justifyOverlappedIntervals(array $keys)
    {
        // először az egy mérésre vonatkozó intervallumokat disjunkttá alakítjuk
        $this->makeDisjunktIntervals($keys);

        // majd ha ugyanarra az időszakra egy gyártósoron több termék gyártásáról kaptunk adatokat,
        // akkor az intervallum feldarabolásával egy időszakhoz egy terméket rendelünk
        $this->justifyIntervals($keys);
    }

    private function justifyIntervals(array $keys)
    {
        $time_line = array();
        foreach ($keys as $key) {
            // végigmegyek a store-on és minden intervallumát ráteszem az idővonalra
            foreach ($this->store[$key] as $interval) {
                // az intervallum két végét ráteszem az idővonalra
                $int_from = $interval['from'];
                $int_to = $interval['to'];

                if (!array_key_exists($int_from, $time_line)) {
                    $time_line[$int_from] = array(
                        'keys_from' => array($key => $interval['value']),
                        'keys_to' => array(),
                        'int_count' => 1,
                    );
                } else {
                    $time_line[$int_from]['keys_from'][$key] = $interval['value'];
                    $time_line[$int_from]['int_count'] += 1;
                }

                if (!array_key_exists($int_to, $time_line)) {
                    $time_line[$int_to] = array(
                        'keys_from' => array(),
                        'keys_to' => array($key => $interval['value']),
                        'int_count' => -1
                    );
                } else {
                    $time_line[$int_to]['keys_to'][$key] = $interval['value'];
                    $time_line[$int_to]['int_count'] -= 1;
                }
            }

        }

        ksort($time_line);

        $open_int_count = 0;
        $run_keys = array();
        $last_from = 0;

//print_r($time_line); exit;
        $new_intervals = array();

        foreach($time_line as $time => $data) {
            // ha nem az első időpontnál vagyunk
            if ($last_from != 0) {
                // ha van nyitott intervallum
                if ($open_int_count > 0) {
                    // ha egyfélét gyártottak
                    if (count($run_keys) == 1) {
                        $keys = array_keys($run_keys);
                        $key = $keys[0];

                        $new_intervals[$key][] = array(
                            'from' => $last_from,
                            'to' => $time,
                            'value' => $run_keys[$key],
                        );
                    // többfélét gyártottak
                    } else {
                        //echo "Open intervals count: $open_int_count\n";
                        // szétdobjuk egymást követő szakaszokra
                        $justified_intervals = $this->justifyIntervalBetween($last_from, $time, $run_keys);
                        foreach($justified_intervals as $key => $interval) {
                            $new_intervals[$key][] = $interval;
                        }
                    }
                }
            }

            $open_int_count += $data['int_count'];
            foreach(array_keys($data['keys_to']) as $key) unset($run_keys[$key]);
            $run_keys += $data['keys_from'];
//echo "Run keys: ";
//print_r($run_keys);
            $last_from = $time;

        }

        foreach ($new_intervals as $key => $intervals) {
            $this->store[$key] = $intervals;
        }

    }

    /**
     * Az adott időszak alatt több párhuzamos kulcs-értékeket diszjunkt szakaszokra osztja úgy,
     * hogy a teljes időintervallumot egyenlő szeletekre vágja
     *
     * @param int $from
     * @param int $to
     * @param array $key_values
     */
    protected function justifyIntervalBetween($from, $to, array $key_values)
    {
        $new_intervals = array();

        $parallel_intervals_num = count($key_values);
        $segment_length = floor( ($to - $from) / $parallel_intervals_num );
        $curr_start = $from;

        foreach ($key_values as $key => $value) {
            $new_intervals[$key] = array(
                'from'  => $curr_start,
                'to'    => $curr_start + $segment_length,
                'value' => $value * $parallel_intervals_num,    // mivel itt az intervallum value_per_sec értékét tároljuk, ezért az intervallum rövidülésének arányában fel kell szorozni
            );

            $curr_start += $segment_length;
        }

        $new_intervals[$key]['to'] = $to;

        return $new_intervals;
    }

    /**
    * Az átfedő intervallumokat diszjunkt halmazokká átdarabolja úgy, hogy
    * - a megadott kulcsú elemeket veszi csak figyelembe
    * - kulcsonként az átfedő szakaszokon az értékeket összegzi
    *
    * @param $keys array a store kulcsai
    */
    private function makeDisjunktIntervals(array $keys)
    {
        foreach ($keys as $key) {
            $time_line = array();
            $open_int_count = 0;
            $run_sum = 0;

            $disj_intervals = array();

            // végigmegyek a store-on és minden intervallumát ráteszem az idővonalra
            foreach ($this->store[$key] as $interval) {
                // az intervallum két végét ráteszem az idővonalra
                $int_from = $interval['from'];
                $int_to = $interval['to'];

                if (!array_key_exists($int_from, $time_line)) {
                    $time_line[$int_from] = array(
                        'sum_value' => $interval['value'],
                        'int_count' => 1
                    );
                } else {
                    $time_line[$int_from]['sum_value'] += $interval['value'];
                    $time_line[$int_from]['int_count'] += 1;
                }

                if (!array_key_exists($int_to, $time_line)) {
                    $time_line[$int_to] = array(
                        'sum_value' => -$interval['value'],
                        'int_count' => -1
                    );
                } else {
                    $time_line[$int_to]['sum_value'] -= $interval['value'];
                    $time_line[$int_to]['int_count'] -= 1;
                }
            }

            ksort($time_line);

            // végigmegyek az idővonalon és gyűtöm a futó sum-ot és új intervallumokat képezek
            $times = array_keys($time_line);
            for($i=0; $i < count($times)-1; $i++) {
                $curr_time = $times[$i];
                $next_time = $times[$i+1];

                $curr_time_point = $time_line[$curr_time];

                $run_sum += $curr_time_point['sum_value'];
                $open_int_count += $curr_time_point['int_count'];

                // ha van nyitott intervallum, akkor előállt egy szakasz
                if ($open_int_count > 0) {
                    $disj_intervals[] = array(
                        'from' => $curr_time,
                        'to'   => $next_time,
                        'value'=> $run_sum,
                    );
                } else {
                    if (round($run_sum, 10) != 0) throw new EException(sprintf(__CLASS__.'- '.__METHOD__.': Running sum(%f) is inconsistant with the count of open intervals (%d) at %d', $run_sum, $open_int_count, $curr_time));
                }

            }

            // konzisztencia ellenőrzés a végén
            $last_time = $times[$i];
            $last_time_point = $time_line[$last_time];

            $run_sum += $last_time_point['sum_value'];
            $open_int_count += $last_time_point['int_count'];
            if (round($run_sum, 10) != 0 || $open_int_count != 0) {
//var_dump($run_sum);exit;
                throw new EException(sprintf(__CLASS__.'- '.__METHOD__.': Running sum(%f) is inconsistant with the count of open intervals (%d) at %d', $run_sum, $open_int_count, $last_time));
            }

            $this->store[$key] = $disj_intervals;

        }
    }


    private function compare($a, $b)
    {
        if (is_array($a) && is_array($b)) {
            asort($a);
            asort($b);

            return $a == $b;
        } elseif (!is_array($a) && !is_array($b)) {
            return $a == $b;
        } else {
            return false;
        }
    }

    /**
     * A megadott from és to közé eső intervallumokat törli a store-ból az adott kulcsúak közül.
     * A from és a to idejével átfedő intervallumokat a from to határáig húzza vissza.
     *
     * @param $key
     * @param $from
     * @param $to
     * @return bool
     */
    private function cutIntervalsWith($key, $from, $to)
    {
        if (!isset($this->store[$key])) return false;
        if (empty($this->store[$key])) return false;
/*
echo "------\n";
echo $from . ' - ' . $to . "\n";
var_dump($this->store[$key]);
echo "***\n";
*/
        $new_store = array();
        foreach ($this->store[$key] as $interval) {
            $new_interval = array('from' => $interval['from'], 'to' => $interval['to'], 'value' => $interval['value']);

            if ($this->isIntersect($interval['from'], $interval['to'], $from, $to)) {
                if ($from <= $interval['from']) {    // az új intervallum korábban kezdődik
                    if($interval['to'] <= $to ) {    // és később ér véget
                        // az új intervallum teljesen elfedi a régit => törölhető
                        //unset($new_interval);
                    } else {                         // de korábban is ér véget
                        // a bal oldalát kell lecsípni
                        $new_interval['from'] = $to;
                        $new_store[] = $new_interval;
                    }
                } else {                             // az új intervallum később kezdődik
                    if ($interval['to'] <= $to) {    // és később is ér véget
                        // a jobb oldalát kell lecsípni
                        $new_interval['to'] = $from;
                        $new_store[] = $new_interval;
                    } else {                         // de korábban ér véget
                        // az új intervallum ennek a belsejében van => ketté kell vágni
                        $new_interval['to'] = $from;
                        $new_store[] = $new_interval;

                        $new_interval['to'] = $interval['to'];
                        $new_interval['from'] = $to;
                        $new_store[] = $new_interval;
                    }
                }
            } else {    // nem átfedő a 2 intervallum
                $new_store[] = $new_interval;    // megtartjuk a régit
            }
        }
//var_dump($new_store);
        $this->store[$key] = $new_store;
    }

    private function makeDisjunctTwoIntervals($from1, $to1, $value1, $from2, $to2, $value2)
    {
        $new_intervals = array();

        if ($from2 <= $from1) {    // az 2. intervallum korábban kezdődik
            if($to1 <= $to2 ) {    // és később ér véget
                // teljes átfedés -> 1. intervallumot teljesen lefedi a 2.
                if ($from2 < $from1) $new_intervals[] = array('from' => $from2, 'to' => $from1, 'value' => $value2);
                $new_intervals[] = array('from' => $from1, 'to' => $to1, 'value' => $value1 + $value2);
                if ($to1 < $to2 ) $new_intervals[] = array('from' => $to1, 'to' => $to2, 'value' => $value2);
            } else {                                // de korábban is ér véget
                // a 2. intervallum balról fedi az 1.-t
                if ($from2 < $from1) $new_intervals[] = array('from' => $from2, 'to' => $from1, 'value' => $value2);
                $new_intervals[] = array('from' => $from1, 'to' => $to2, 'value' => $value1 + $value2);
                $new_intervals[] = array('from' => $to2, 'to' => $to1, 'value' => $value1);
            }
        } else {                                    // az új intervallum később kezdődik
            if ($to1 <= $to2) {    // és később is ér véget
                // a 2. intervallum jobbról fedi az 1.-t
                $new_intervals[] = array('from' => $from1, 'to' => $from2, 'value' => $value1);
                $new_intervals[] = array('from' => $from2, 'to' => $to1, 'value' => $value1 + $value2);
                if ($to1 < $to2) $new_intervals[] = array('from' => $to1, 'to' => $to2, 'value' => $value2);
            } else {
                // 2. átfedi az 1.-t
                $new_intervals[] = array('from' => $from1, 'to' => $from2, 'value' => $value1);
                $new_intervals[] = array('from' => $from2, 'to' => $to2, 'value' => $value1 + $value2);
                $new_intervals[] = array('from' => $to2, 'to' => $to1, 'value' => $value1);
            }
        }
//echo "************* Justify intervals: *********";
//print_r($new_intervals);
        return $new_intervals;
    }

    /**
     * Ellenőrzi, hogy az új intervallum átfed-e az összegyűjtöttekkel
     *
     * Az intervallumokat tároló tömb megadott kulcsán (altömb) végigszaladva eldönti, hogy
     * az új intervallum átfed-e bármely korábbi intervallumot. Csak az 1. átfedést keresi meg.
     * A dátumokat string formában kapja meg.
     *
     * @param array  $store az intervallumok tárolására szolgáló tömb
     * @param string $key   a tömb azon kulcsa, ami alatt további tömbelemekként az intervallumokat kell keresni
     * @param integer $from
     * @param integer $to
     * @return boolean
     */
    public function isIntervalOverlap($key, $from, $to)
    {
        if (!isset($this->store[$key])) return false;
        if (empty($this->store[$key])) return false;

        foreach ($this->store[$key] as $interval) {
            if ($this->isIntersect($interval['from'], $interval['to'], $from, $to)) return true;
        }

        return false;
    }

    /**
     * Két numerikusan megadott idő-intervallumból megmondja, hogy azok átfedőek-e
     *
     * @param integer $start1
     * @param integer $end1
     * @param integer $start2
     * @param integer $end2
     * @return boolean
     */
    private function isIntersect($start1, $end1, $start2, $end2)
    {
//echo $start1.'-'.$end1. ' - '. $start2.'-'.$end2."\n";
        return ($start1 >= $start2) ? ($start1 < $end2) : ($start2 < $end1);
    }

    public function dumpStore()
    {
        print_r($this->store);
    }
}