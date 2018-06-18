<?php

include_once dirname(__DIR__) . '/vendor/autoload.php';

$options = new \Bavix\XMLReader\Options();
$options->setVersion('1.0');
$options->setFormatOutput(false);
$options->setRootName('root');

$reader = \Bavix\XMLReader\XMLReader::sharedInstance($options);

$data = $reader->asArray('c.xml');

var_dump($data, $reader->asXML($data));
