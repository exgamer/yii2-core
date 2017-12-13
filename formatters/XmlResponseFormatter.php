<?php
namespace core\formatters;
use DOMElement;
use DOMText;
use yii\base\Arrayable;
use yii\helpers\StringHelper;

/**
 * Created by PhpStorm.
 * User: niaz
 * Date: 06.03.2017
 * Time: 10:19
 */
class XmlResponseFormatter extends \yii\web\XmlResponseFormatter
{
    /**
     * @param DOMElement $element
     * @param mixed $data
     */
    protected function buildXml($element, $data)
    {
        if (is_array($data) ||
            ($data instanceof \Traversable && $this->useTraversableAsArray && !$data instanceof Arrayable)
        ) {
            foreach ($data as $name => $value) {
                if ($name == 'attributes'){
                    $this->applyAttributes($element, $value);
                    continue;
                }
                if ($name == 'value'){
                    $element->appendChild(new DOMText($this->formatScalarValue($value)));
                    continue;
                }
                if (is_int($name) && is_object($value)) {
                    $this->buildXml($element, $value);
                } elseif (is_array($value) || is_object($value)) {
                    $child = new DOMElement(is_int($name) ? $this->itemTag : $name);
                    $element->appendChild($child);
                    $this->buildXml($child, $value);
                } else {
                    $child = new DOMElement(is_int($name) ? $this->itemTag : $name);
                    $element->appendChild($child);
                    $child->appendChild(new DOMText($this->formatScalarValue($value)));
                }
            }
        } elseif (is_object($data)) {
            $child = new DOMElement(StringHelper::basename(get_class($data)));
            $element->appendChild($child);
            if ($data instanceof Arrayable) {
                $this->buildXml($child, $data->toArray());
            } else {
                $array = [];
                foreach ($data as $name => $value) {
                    $array[$name] = $value;
                }
                $this->buildXml($child, $array);
            }
        } else {
            $element->appendChild(new DOMText($this->formatScalarValue($data)));
        }
    }

    private function applyAttributes($element, $attrs){
        foreach ($attrs as $name => $value) {
            $element->setAttribute($name, $this->formatScalarValue($value));
        }
    }

}