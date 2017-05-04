<?php

namespace Bavix\XMLReader;

use Bavix\Exceptions;
use Bavix\Foundation\SharedInstance;
use DOMElement;

class XMLReader
{

    use SharedInstance;

    /**
     * @var array
     */
    protected $copyright = ['created-with' => 'https://github.com/bavix/xml'];

    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * @return \DOMDocument
     */
    protected function document()
    {
        if (!$this->document)
        {
            $this->document                = new \DOMDocument('1.0', 'utf-8');
            $this->document->formatOutput  = true;
            $this->copyright['created-at'] = date('Y-m-d H:i:s');
        }

        return $this->document;
    }

    /**
     * @param string $name
     *
     * @return \DOMElement
     */
    protected function element($name)
    {
        return $this->document()->createElement($name);
    }

    /**
     * @param string $file
     *
     * @return array
     */
    public function asArray($file)
    {
        $reader = \simplexml_load_file($file);

        return json_decode(json_encode((array)$reader), true);
    }

    /**
     * @param array $storage
     *
     * @return string
     */
    public function asXML(array $storage)
    {
        $element = $this->element('bavix');

        $this->addAttributes($element, $this->copyright);
        $this->document()->appendChild($element);
        $this->convert($element, $storage);
        $xml = $this->document()->saveXML();

        $this->document = null;

        return $xml;
    }

    /**
     * @param DOMElement $element
     * @param mixed      $storage
     *
     * @throws Exceptions\Blank
     */
    protected function convert(DOMElement $element, $storage)
    {
        if (!is_array($storage))
        {
            $element->nodeValue = htmlspecialchars($storage);

            return;
        }

        if (count($storage) === 0)
        {
            throw new Exceptions\Blank('Array is empty');
        }

        $isInt      = array_map('is_int', array_keys($storage));
        $sequential = !in_array(false, $isInt, true);

        foreach ($storage as $key => $data)
        {
            if ($sequential)
            {
                $this->sequential($element, $data);
                continue;
            }

            $this->addNodeWithKey($key, $element, $data);
        }
    }

    /**
     * @param string     $key
     * @param DOMElement $element
     * @param mixed      $storage
     */
    protected function addNodeWithKey($key, DOMElement $element, $storage)
    {
        if ($key === '@attributes')
        {
            $this->addAttributes($element, $storage);
        }
        elseif ($key === '@value' && is_string($storage))
        {
            $element->nodeValue = htmlspecialchars($storage);
        }
        else
        {
            $this->addNode($element, $key, $storage);
        }
    }

    /**
     * @param DOMElement $element
     * @param mixed      $storage
     */
    protected function sequential(DOMElement $element, $storage)
    {
        if (is_array($storage))
        {
            $this->addCollectionNode($element, $storage);
        }
        else
        {

            $this->addSequentialNode($element, $storage);
        }
    }

    /**
     * @param DOMElement $element
     * @param string     $key
     * @param mixed      $value
     *
     * @throws Exceptions\Blank
     */
    protected function addNode(DOMElement $element, $key, $value)
    {
        $key   = str_replace(' ', '_', $key);
        $child = $this->document()->createElement($key);
        $element->appendChild($child);
        $this->convert($child, $value);
    }

    /**
     * @param DOMElement $element
     * @param mixed      $value
     *
     * @throws Exceptions\Blank
     */
    protected function addCollectionNode(DOMElement $element, $value)
    {
        if ($element->childNodes->length === 0)
        {
            $this->convert($element, $value);

            return;
        }

        /**
         * @var $child DOMElement
         */
        $child = $element->cloneNode();
        $element->parentNode->appendChild($child);
        $this->convert($child, $value);
    }

    /**
     * @param DOMElement $element
     * @param mixed      $value
     */
    protected function addSequentialNode(DOMElement $element, $value)
    {
        if (empty($element->nodeValue))
        {
            $element->nodeValue = htmlspecialchars($value);

            return;
        }

        $child            = $element->cloneNode();
        $child->nodeValue = htmlspecialchars($value);
        $element->parentNode->appendChild($child);
    }

    /**
     * @param DOMElement $element
     * @param array      $storage
     */
    protected function addAttributes(DOMElement $element, array $storage)
    {
        foreach ($storage as $attrKey => $attrVal)
        {
            $element->setAttribute($attrKey, $attrVal);
        }
    }

}
