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
     * @return string
     */
    public function __toString()
    {
        return $this->raw;
    }

}
