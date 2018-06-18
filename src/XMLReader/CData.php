<?php

namespace Bavix\XMLReader;

class CData extends Raw
{

    /**
     * @param \DOMDocument $document
     * @return \DOMNode
     */
    public function fragment(\DOMDocument $document): \DOMNode
    {
        return $document->createCDATASection((string)$this);
    }

}
