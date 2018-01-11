<?php

namespace Bavix\XMLReader;

class CData extends Raw
{

    /**
     * @return string
     */
    public function __toString()
    {
        return '<![CDATA[ ' . parent::__toString() . ' ]]>';
    }

}
