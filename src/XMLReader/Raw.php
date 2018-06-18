<?php

namespace Bavix\XMLReader;

class Raw
{

    /**
     * @var string 
     */
    protected $raw;

    /**
     * Raw constructor.
     *
     * @param string $raw
     */
    public function __construct(string $raw)
    {
        $this->raw = $raw;
    }

    /**
     * @param \DOMDocument $document
     * @return \DOMDocumentFragment
     */
    public function fragment(\DOMDocument $document): \DOMNode
    {
        $fragment = $document->createDocumentFragment();
        $fragment->appendXML((string)$this);
        return $fragment;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->raw;
    }

}
