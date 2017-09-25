<?php

namespace Bavix\XMLReader;

use Bavix\Exceptions;
use Bavix\Foundation\SharedInstance;
use Bavix\Helpers\Arr;
use Bavix\Helpers\File;
use Bavix\Helpers\JSON;
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

    protected function _asArray(\SimpleXMLElement $element)
    {
        $output = [];

        $attributes = $element->attributes();

        if ($attributes)
        {
            $output['@attributes'] = $this->_asArray($attributes);

            if (empty($output['@attributes']))
            {
                Arr::remove($output, '@attributes');
            }
        }

        /**
         * @var \SimpleXMLElement $item
         */
        foreach ($element as $key => $item)
        {
            if (!$item->count())
            {
                 $output[$key] = (string)$item;
                 continue;
            }

            Arr::initOrPush(
                $output,
                $key,
                $this->_asArray($item)
            );
        }

        return $output;
    }

    /**
     * @param string|\DOMNode $mixed
     *
     * @return array
     */
    public function asArray($mixed)
    {
        if ($mixed instanceof \DOMNode)
        {
            return $this->_asArray(\simplexml_import_dom($mixed));
        }

        if (File::isFile($mixed))
        {
            return $this->_asArray(\simplexml_load_file($mixed));
        }

        return $this->_asArray(\simplexml_load_string($mixed));
    }

    /**
     * @return \DOMDocument
     */
    public function asObject()
    {
        return clone $this->document();
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

        if (empty($storage))
        {
            throw new Exceptions\Blank('Array is empty');
        }

        $isInt      = Arr::map(Arr::getKeys($storage), 'is_int');
        $sequential = !Arr::in($isInt, false, true);

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
        else if ($key === '@value' && is_string($storage))
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

            return;
        }

        $this->addSequentialNode($element, $storage);
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
