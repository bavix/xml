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
    protected $namespaces = [];

    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * @var Options
     */
    protected $options;

    /**
     * XMLReader constructor.
     * @param Options $options
     */
    public function __construct(Options $options = null)
    {
        if ($options) {
            $this->options = $options;
        }
    }

    /**
     * @return Options
     */
    protected function options(): Options
    {
        if (!$this->options) {
            $this->options = new Options();
        }
        return $this->options;
    }

    /**
     * @param array|\Traversable $storage
     * @param string $name
     * @param array $attributes
     * @return string
     */
    public static function toXml($storage, string $name = null, array $attributes = []): string
    {
        return (new static())
            ->asXML($storage, $name, $attributes);
    }

    /**
     * @return \DOMDocument
     */
    public function makeDocument(): \DOMDocument
    {
        $document = new \DOMDocument(
            $this->options()->getVersion(),
            $this->options()->getCharset()
        );

        $document->formatOutput = $this->options()->isFormatOutput();

        return $document;
    }

    /**
     * @return \DOMDocument
     */
    protected function document(): \DOMDocument
    {
        if (!$this->document)
        {
            $this->document = $this->makeDocument();
        }

        return $this->document;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return DOMElement
     */
    protected function createElement(string $name, $value = null): \DOMElement
    {
        return $this->document()->createElement($name, $value);
    }

    /**
     * @param string $name
     *
     * @return \DOMElement
     */
    protected function element(string $name): \DOMElement
    {
        if (\strpos($name, ':') !== false)
        {
            $keys = explode(':', $name);

            return $this->document()->createElementNS(
                $this->namespaces[$this->options()->getNamespace() . ':' . \current($keys)],
                $name
            );
        }

        return $this->createElement($name);
    }

    /**
     * @param \SimpleXMLElement $element
     * @param string            $property
     *
     * @return array
     */
    protected function _property(\SimpleXMLElement $element, $property): array
    {
        $output = [];

        if (\method_exists($element, $property)) {
            $properties = $element->$property();

            if ($properties) {

                $data = \is_array($properties) ?
                    $properties : $this->_asArray($properties);

                if ($data !== null || $data === '') {
                    $output['@' . $property] = $data;
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

        if (!$element->count()) {
            $output['@value'] = (string)$element;
            if (!isset($output['@attributes'])) {
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

        if (!$element->count()) {
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
    protected function _pushArray(array &$output, \SimpleXMLElement $element): array
    {
        $first = [];

        /**
         * @var \SimpleXMLElement $item
         */
        foreach ($element as $key => $item)
        {
            if (!isset($output[$key])) {
                $first[$key]  = true;
                $output[$key] = $this->_asArray($item);
                continue;
            }

            if (!empty($first[$key])) {
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
    protected function _simpleXml($mixed): \SimpleXMLElement
    {
        if ($mixed instanceof \DOMNode) {
            return \simplexml_import_dom($mixed);
        }

        if (File::isFile($mixed))
        {
            $mixed = \file_get_contents($mixed);
        }

        return \simplexml_load_string($mixed);
    }

    /**
     * @param string|\DOMNode $mixed
     *
     * @return array|null
     */
    public function asArray($mixed): ?array
    {
        $data = $this->_simpleXml($mixed);
        return $data ? $this->_asArray($data) : null;
    }

    /**
     * @return \DOMDocument
     */
    public function asObject(): \DOMDocument
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
    protected function _convertStorage($storage): array
    {
        if ($storage instanceof \Traversable) {
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
    public function asXML($storage, string $name = null, array $attributes = []): string
    {
        $storage = $this->fragments($storage);
        $element = $this->element($name ?? $this->options()->getRootName());

        $this->addAttributes($element, $attributes);
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
    protected function convert(DOMElement $element, $storage): void
    {
        if (\is_object($storage)) {
            $element->appendChild($element->ownerDocument->importNode($storage));
            return;
        }

        if (!\is_array($storage)) {
            $element->nodeValue = \htmlspecialchars($storage);
            return;
        }

        if (empty($storage)) {
            throw new Exceptions\Blank('Array is empty');
        }

        $isInt      = Arr::map(Arr::getKeys($storage), '\is_int');
        $sequential = !Arr::in($isInt, false);

        foreach ($storage as $key => $data) {
            if ($sequential) {
                $this->sequential($element, $data);
                continue;
            }

            $this->addNodeWithKey($key, $element, $data);
        }
    }

    /**
     * @param array $storage
     * @return array
     */
    protected function fragments(array $storage): array
    {
        Arr::walkRecursive($storage, function(&$value) {
            if (\is_object($value) && $value instanceof Raw) {
                $value = $value->fragment($this->document());
            }
        });

        return $storage;
    }

    /**
     * @param string     $key
     * @param DOMElement $element
     * @param mixed      $storage
     *
     * @codeCoverageIgnore
     */
    protected function addNodeWithKey($key, DOMElement $element, $storage): void
    {
        if ($key === '@attributes') {
            $this->addAttributes($element, $storage);
            return;
        }

        if ($key === '@value') {

            if (\is_string($storage)) {
                $element->nodeValue = $storage;
                return;
            }

            $dom      = new \DOMDocument();
            $fragment = $element->ownerDocument->createDocumentFragment();
            $dom->loadXML(static::toXml($storage, 'root', $this->namespaces));

            /**
             * @var $childNode \DOMText
             */
            foreach ($dom->firstChild->childNodes as $childNode) {
                $fragment->appendXML($childNode->ownerDocument->saveXML($childNode));
            }

            $element->appendChild($fragment);
            return;
        }

        $this->addNode($element, $key, $storage);
    }

    /**
     * @param DOMElement $element
     * @param mixed      $storage
     *
     * @codeCoverageIgnore
     */
    protected function sequential(DOMElement $element, $storage): void
    {
        if (\is_array($storage)) {
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
    protected function addNode(DOMElement $element, $key, $value): void
    {
        $key   = \str_replace(' ', '-', $key);
        $child = $this->element($key);
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
    protected function addCollectionNode(DOMElement $element, $value): void
    {
        if ($element->childNodes->length === 0) {
            $this->convert($element, $value);
            return;
        }

        /**
         * @var $child DOMElement
         */
        $child = $this->element($element->nodeName);
        $element->parentNode->appendChild($child);
        $this->convert($child, $value);
    }

    /**
     * @param DOMElement $element
     * @param mixed      $value
     *
     * @codeCoverageIgnore
     */
    protected function addSequentialNode(DOMElement $element, $value): void
    {
        if (empty($element->nodeValue)) {
            $element->nodeValue = \htmlspecialchars($value);
            return;
        }

        $child = $this->element($element->nodeName);
        $child->nodeValue = \htmlspecialchars($value);
        $element->parentNode->appendChild($child);
    }

    /**
     * @param DOMElement         $element
     * @param array|\Traversable $storage
     *
     * @codeCoverageIgnore
     */
    protected function addAttributes(DOMElement $element, $storage): void
    {
        foreach ($storage as $attrKey => $attrVal) {
            if (strpos($attrKey, ':') !== false) {
                $this->namespaces[$attrKey] = $attrVal;
            }

            $element->setAttribute($attrKey, $attrVal);
        }
    }

}
