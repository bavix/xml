<?php

namespace Bavix\XMLReader;

use Bavix\Foundation\SharedInstance;
use Bavix\Helpers\File;
use Bavix\Helpers\Arr;
use Bavix\Exceptions;
use DOMElement;

class XMLReader
{

    use SharedInstance;

    /**
     * @var array
     */
    protected $copyright = [
        'created-with' => 'https://github.com/bavix/xml'
    ];

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
            $this->copyright['created-at'] = \date('Y-m-d H:i:s');
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
     * @param \SimpleXMLElement $element
     * @param string            $property
     *
     * @return array
     */
    protected function _property(\SimpleXMLElement $element, $property)
    {
        $output = [];

        if (method_exists($element, $property))
        {

            $properties = $element->$property();

            if ($properties)
            {
                $output['@' . $property] = is_array($properties) ?
                    $properties : $this->_asArray($properties);

                if (empty($output['@' . $property]))
                {
                    Arr::remove($output, '@' . $property);
                }
            }

        }

        return $output;
    }

    /**
     * @param \SimpleXMLElement $element
     *
     * @return array|string
     *
     * @codeCoverageIgnore
     */
    protected function _asData(\SimpleXMLElement $element)
    {
        $output = $this->_property($element, 'attributes');

        if (!$element->count())
        {
            $output['@value'] = (string)$element;

            if (!isset($output['@attributes']))
            {
                $output = $output['@value'];
            }
        }

        return $output;
    }

    /**
     * @param \SimpleXMLElement $element
     *
     * @return array|string
     *
     * @codeCoverageIgnore
     */
    protected function _asArray(\SimpleXMLElement $element)
    {
        $output = $this->_asData($element);

        if (!$element->count())
        {
            return $output;
        }

        return $this->_pushArray($output, $element);
    }

    /**
     * @param array             $output
     * @param \SimpleXMLElement $element
     *
     * @return array
     *
     * @codeCoverageIgnore
     */
    protected function _pushArray(array &$output, \SimpleXMLElement $element)
    {
        $first = [];

        /**
         * @var \SimpleXMLElement $item
         */
        foreach ($element as $key => $item)
        {
            if (!isset($output[$key]))
            {
                $first[$key]  = true;
                $output[$key] = $this->_asArray($item);
                continue;
            }

            if (!empty($first[$key]))
            {
                $output[$key] = [$output[$key]];
            }

            $output[$key][] = $this->_asArray($item);
            $first[$key]    = false;
        }

        return $output;
    }

    /**
     * @param string|\DOMNode $mixed
     *
     * @return \SimpleXMLElement
     *
     * @codeCoverageIgnore
     */
    protected function _simpleXml($mixed)
    {
        if ($mixed instanceof \DOMNode)
        {
            return \simplexml_import_dom($mixed);
        }

        if (File::isFile($mixed))
        {
            return \simplexml_load_file($mixed);
        }

        return \simplexml_load_string($mixed);
    }

    /**
     * @param string|\DOMNode $mixed
     *
     * @return array
     */
    public function asArray($mixed)
    {
        $data = $this->_simpleXml($mixed);

        return $data ?
            $this->_asArray($data) : null;
    }

    /**
     * @return \DOMDocument
     */
    public function asObject()
    {
        return clone $this->document();
    }

    /**
     * @param array|\Traversable $storage
     *
     * @return array
     *
     * @codeCoverageIgnore
     */
    protected function _convertStorage($storage)
    {
        if ($storage instanceof \Traversable)
        {
            return \iterator_to_array($storage);
        }

        return $storage;
    }

    /**
     * @param array|\Traversable $storage
     * @param string             $name
     * @param array              $attributes
     *
     * @return string
     */
    public function asXML($storage, $name = 'bavix', array $attributes = [])
    {
        $element = $this->element($name);

        $this->addAttributes($element, $attributes);
        $this->addAttributes($element, $this->copyright);
        $this->document()->appendChild($element);
        $this->convert($element, $this->_convertStorage($storage));
        $xml = $this->document()->saveXML();

        $this->document = null;

        return $xml;
    }

    /**
     * @param DOMElement $element
     * @param mixed      $storage
     *
     * @throws Exceptions\Blank
     *
     * @codeCoverageIgnore
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

        $isInt      = Arr::map(Arr::getKeys($storage), '\is_int');
        $sequential = !Arr::in($isInt, false);

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
     *
     * @codeCoverageIgnore
     */
    protected function addNodeWithKey($key, DOMElement $element, $storage)
    {
        if ($key === '@attributes')
        {
            $this->addAttributes($element, $storage);
        }
        else if ($key === '@value')
        {
            if (\is_string($storage))
            {
                $element->nodeValue = \htmlspecialchars($storage);

                return;
            }

            $dom = new \DOMDocument();
            $dom->loadXML(
                (new XMLReader())->asXML($storage)
            );

            $fragment = $element->ownerDocument->createDocumentFragment();

            foreach ($dom->firstChild->childNodes as $value)
            {
                $fragment->appendXML(
                    $value->ownerDocument->saveXML($value)
                );
            }

            $element->appendChild($fragment);
        }
        else
        {
            $this->addNode($element, $key, $storage);
        }
    }

    /**
     * @param DOMElement $element
     * @param mixed      $storage
     *
     * @codeCoverageIgnore
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
     *
     * @codeCoverageIgnore
     */
    protected function addNode(DOMElement $element, $key, $value)
    {
        $key   = \str_replace(' ', '-', $key);
        $child = $this->document()->createElement($key);
        $element->appendChild($child);
        $this->convert($child, $value);
    }

    /**
     * @param DOMElement $element
     * @param mixed      $value
     *
     * @throws Exceptions\Blank
     *
     * @codeCoverageIgnore
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
        $child = $this->document()->createElement($element->nodeName);
//        $child = $element->cloneNode();
        $element->parentNode->appendChild($child);
        $this->convert($child, $value);
    }

    /**
     * @param DOMElement $element
     * @param mixed      $value
     *
     * @codeCoverageIgnore
     */
    protected function addSequentialNode(DOMElement $element, $value)
    {
        if (empty($element->nodeValue))
        {
            $element->nodeValue = \htmlspecialchars($value);

            return;
        }

        $child = $this->document()->createElement($element->nodeName);
//        $child = $element->cloneNode();
        $child->nodeValue = \htmlspecialchars($value);
        $element->parentNode->appendChild($child);
    }

    /**
     * @param DOMElement         $element
     * @param array|\Traversable $storage
     *
     * @codeCoverageIgnore
     */
    protected function addAttributes(DOMElement $element, $storage)
    {
        foreach ($storage as $attrKey => $attrVal)
        {
            $element->setAttribute($attrKey, $attrVal);
        }
    }

}
