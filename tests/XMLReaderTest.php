<?php

namespace Tests;

use Bavix\Tests\Unit;
use Bavix\XMLReader\XMLReader;

class XMLReaderTest extends Unit
{

    /**
     * @var XMLReader
     */
    protected $xml;

    /**
     * @var array
     */
    protected $storage;

    public function setUp()
    {
        parent::setUp();

        $this->storage = require __DIR__ . '/storage.php';
        $this->xml = new XMLReader();
    }

    public function testArray()
    {
        $xml = $this->xml->asXML($this->storage);

        $this->assertArraySubset(
            $this->storage['person'],
            $this->xml->asArray($xml)['person']
        );
    }

}
