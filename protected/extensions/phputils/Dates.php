<?php

class Dates
{
    /**
     * 2 szöveges dátum közül a kisebbiket adja vissza szövegesen
     *
     * @param string $date1
     * @param string $date2
     * @return string
     */
    public static function min($date1, $date2)
    {
        return sfDate::getInstance(min(self::convert2DatesToTimestamp($date1, $date2)))->dump();
    }

    /**
     * 2 szöveges dátum közül a nagyobbikat adja vissza szövegesen
     *
     * @param string $date1
     * @param string $date2
     * @return string
     */
    public static function max($date1, $date2)
    {
        return sfDate::getInstance(max(self::convert2DatesToTimestamp($date1, $date2)))->dump();
    }

    /**
     * Összehasonlít 2 szöveges dátumot, ha egyeznek, akkor 0, ha az első kisebb, akkor -1, egyébként +1
     *
     * @param string $date1
     * @param string $date2
     * @return integer
     */
    public static function compare($date1, $date2)
    {
        $dates = self::convert2DatesToTimestamp($date1, $date2);

        return $dates[0]==$dates[1] ? 0 : ($dates[0]<$dates[1] ? -1 : 1);
    }

    public static function convert2DatesToTimestamp($date1, $date2)
    {
        $d1 = sfDate::getInstance($date1)->get();
        $d2 = sfDate::getInstance($date2)->get();

        return array($d1, $d2);
    }

    public static function dateDiffToHumanReadable($timestamp)
    {
        $diff = sfDate::getInstance()->get() - $timestamp;

        if ($diff > 24*60*60) return Yii::t('msg', 'több mint ').((int)($diff/(24*60*60))).Yii::t('msg', ' napja');
        if ($diff >    60*60) return Yii::t('msg', 'több mint ').((int)($diff/(60*60))).Yii::t('msg', ' órája');
        if ($diff >       60) return Yii::t('msg', 'több mint ').((int)($diff/60)).Yii::t('msg', ' perce');
        if ($diff ==       0) return Yii::t('msg', 'most');
        return $diff . Yii::t('msg', ' másodperce');
    }

    public static function getMonths($month = null) {
        $months = array(
            1 => Yii::t('msg','január'),
            2 => Yii::t('msg','február'),
            3 => Yii::t('msg','március'),
            4 => Yii::t('msg','április'),
            5 => Yii::t('msg','május'),
            6 => Yii::t('msg','június'),
            7 => Yii::t('msg','július'),
            8 => Yii::t('msg','augusztus'),
            9 => Yii::t('msg','szeptember'),
            10 => Yii::t('msg','október'),
            11 => Yii::t('msg','november'),
            12 => Yii::t('msg','december')
        );

        if (is_null($month)) return $months;
                        else return $months[$month];
    }

    /**
     * Default intervallum: hónap 1-től a mai napig, de ha épp 1-je van, akkor az előző hó 1-től
     *
     * @return array
     */
    public static function getDefaultDateInterval($time_minutes_interval=15,$floor = true)
    {
        $curr_date = sfDate::getInstance();
        $curr_date_ts = $curr_date->get();
        $day_num = $curr_date->retrieve(sfTime::DAY);
        $month_start_date = new sfDate($curr_date);

        //echo $curr_date->format("H:i"); exit;

        if ($day_num>1) {
            $month_start_date->subtractDay($day_num-1);
        } else {
            $month_start_date->subtractMonth();
        }

        if ($time_minutes_interval) {
            $time_minutes_interval_ts = $time_minutes_interval*60;

            if (!$floor){
                $end_ts   = $time_minutes_interval_ts * ceil($curr_date_ts / $time_minutes_interval_ts);
            }
            else{
                $end_ts   = $time_minutes_interval_ts * floor($curr_date_ts / $time_minutes_interval_ts);
            }

            $intervalled_time = sfDate::getInstance($end_ts);
        }


        return array(
            'date_from' => $month_start_date->formatDbDate(),
            'date_to' => $curr_date->formatDbDate(),
            'time_from' => "00:00",
            'time_to' => ($time_minutes_interval)?$intervalled_time->format("H:i"):"23:45",
        );
    }

    public static function JSdateToGMTTimestamp($js_date_str)
    {
        $datetime_format = Yii::app()->params['extjs_datetime_sec_format'];
        $client_time_zone = Yii::app()->params['timezone'];
        $timezone = new DateTimeZone($client_time_zone);

        $datetime = DateTime::createFromFormat($datetime_format, $js_date_str, $timezone);

        return $datetime->getTimestamp(); // GMT-ben adja vissza
    }
}