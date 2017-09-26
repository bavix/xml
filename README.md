# XML Reader

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bavix/xml/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/bavix/xml/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/bavix/xml/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/bavix/xml/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/bavix/xml/badges/build.png?b=master)](https://scrutinizer-ci.com/g/bavix/xml/build-status/master)

```php
$reader = \Bavix\XMLReader\XMLReader::sharedInstance();
$data = $reader->asArray('c.xml');
var_dump($reader->asXML($data));
```
