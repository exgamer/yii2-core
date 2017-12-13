<?php

namespace core\helpers;

/**
 * Вспомогательный класс для работы с датами
 *
 * @author STQWZR
 */
class DateHelper
{
    /**
     * Возвращает интервал времени между двумя датами
     *
     * @return array
     */
    public static function dmybetween($d2, $d1) 
    {
        $startArry = date_parse($d1);
        $endArry = date_parse($d2);

        $yleap = ($endArry['year'] % 4 == 0) and (($endArry['year'] % 100<>0) or ($endArry['year'] % 400==0));
        $dm[1] = 31;
        $dm[3] = 31;
        $dm[4] = 30;
        $dm[5] = 31;
        $dm[6] = 30;
        $dm[7] = 31;
        $dm[8] = 31;
        $dm[9] = 30;
        $dm[10] = 31;
        $dm[11] = 30;
        $dm[12] = 31;
        if ($yleap)
            $dm[2] = 29;
        else
            $dm[2] = 28;

        $cf = 0;
        $d = $startArry['day'] - $endArry['day'];

        if ($d<0) {
            $d = $d + $dm[$endArry['month']];
            $cf = 1;
        }
        $m = $startArry['month']-$endArry['month']-$cf;

        $cf = 0;
        if ($m<0) {
            $m = $m+12;
            $cf = 1;
        }
        $y = $startArry['year'] - $endArry['year'] - $cf;
        $r = array('years' => $y, 'months' => $m, 'days' => $d, 'hours' => 0, 'mins' => 0, 'sec' => 0);
        return ($r);
    }
    
    /**
     * Возвращает дату из свойства объекта
     * 
     * @param Object $object - объект
     * @param string $property - свойство
     * @param string $format - формат даты
     * @return string
     */
    public static function getDateByObjectProperty($object , $property = 'from_ts' , $format = "Y-m-d")
    {
        if(property_exists($object, $property) && $object->{$property}){
            $result = date($format, strtotime($object->{$property}));
        } else {
            $result = date($format);
        }
        
        return $result;
    }
    
    /**
     * Получение даты
     * 
     * @param string $value
     * @param string $format
     * @return type
     */
    public static function getDate($value , $format = "Y-m-d")
    {
            if (! $value) {
                $result = date($format);
            } else {
                $result = date($format, strtotime($value));
            }
            
            return $result;
    }
    
    /**
     * Возвращает дату первого дня в месяце
     * 
     * @param string $format
     * @param string $date
     * 
     * @return string
     */
    public static function getMonthFirstDay($format = 'Y-m-d', $date = null)
    {
        list($curentYear, $curentMonth) = self::getDateArray($date);
        
        $time = mktime(0, 0, 0, $curentMonth, 1, $curentYear);
        
        return date($format,  $time);
    }
    
    /**
     * Возвращает дату последнего дня в месяце
     * 
     * @param string $format
     * @param string $date
     * 
     * @return string
     */
    public static function getMonthLastDay($format = 'Y-m-d', $date = null)
    {
        list($curentYear, $curentMonth) = self::getDateArray($date);
        
        $time = mktime(0, 0, 0, $curentMonth + 1, 0, $curentYear);
        
        return date($format,  $time);
    }
    
    /**
     * Возвращает массив текущей даты
     * 
     * @param string $date
     * 
     * @return array
     */
    protected static function getDateArray($date = null)
    {        
        if(! $date){
            return [(int) date('Y') , (int) date('m') , (int) date('d')];
        }
        $ts = strtotime($date);
        return [
            (int) date('Y', $ts) ,
            (int) date('m', $ts) ,
            (int) date('d', $ts)
        ];
    }
    
    /**
     * Возвращает массив недель с датой начала и окончания
     * по заданному интервалу $date_from - $date_to
     * 
     * @param string $date_from
     * @param string $date_to
     * @param string $format
     * 
     * @return array
     */
    public static function getWeeksIntervalByDates($date_from , $date_to , $format = 'Y-m-d')
    {
        $ts_from = strtotime($date_from);
        $ts_to = strtotime($date_to);
        $diff = $ts_to - $ts_from;
        $days = $diff / 60 / 60 / 24;
        $weeks_full = round($days / 7);
        $weeks_remainder = $days % 7;
        $week_count = $weeks_full;
        if($weeks_remainder > 0){
            $week_count ++;
        }
        $dateArray = [];
        $resultData = $date_from;
        for ($i = 0; $i < $week_count; $i++) {
            $plusWeek = " +1 week";
            if($i == 0){
                $plusWeek = null;
            }
            $date = date('Y-m-d', strtotime($resultData . $plusWeek));
            $sunday = date('Y-m-d', strtotime($date . " Sunday"));
            $monday = date('Y-m-d', strtotime($sunday . " Monday - 7 day"));
            $dateArray[] = [
                'from' => date($format,strtotime($monday)),
                'to' => date($format,strtotime($sunday))
            ];
            $resultData = $date;
        }
        
        return $dateArray;
    }
}