<?php
namespace core\controllers;

/**
 * Базовый класс для конольных команд
 */
abstract class BaseCommandController extends \yii\console\Controller
{
    /**
     * Массив с цветами текста
     * @var array 
     */
    private $f_colors = array(
        'black' => '0;30', 'dark_gray' => '1;30', 'blue' => '0;34',
        'light_blue' => '1;34', 'green' => '0;32', 'light_green' => '1;32',
        'cyan' => '0;36', 'light_cyan' => '1;36', 'red' => '0;31',
        'light_red' => '1;31', 'purple' => '0;35', 'light_purple' => '1;35',
        'brown' => '0;33', 'yellow' => '1;33', 'light_gray' => '0;37',
        'white' => '1;37',
    );

    /**
     * Массив с цветами фона текста
     * @var array 
     */
    private $b_colors = array(
        'black' => '40', 'red' => '41', 'green' => '42',
        'yellow' => '43', 'blue' => '44', 'magenta' => '45',
        'cyan' => '46', 'light_gray' => '47',
    );
    
    /**
     * Успешнноое сообщение с переводом строки
     * 
     * @param string $text
     * @param string $color
     * @return string
     */
    public function outputSuccess($text , $color = 'green')
    {
       $color = isset($this->f_colors[$color]) ? $this->f_colors[$color] : \yii\helpers\Console::FG_GREEN;
       
       echo $this->ansiFormat($text . PHP_EOL,  $color); 
    }
    
    /**
     * Сбой с переводом строки
     * 
     * @param string $text
     * @param string $color
     * @return string
     */
    public function outputDone($text, $color = 'red')
    {
        $color = isset($this->f_colors[$color]) ? $this->f_colors[$color] : \yii\helpers\Console::FG_RED;
        
        echo $this->ansiFormat($text . PHP_EOL,  $color); 
    }
}

