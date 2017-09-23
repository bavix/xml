# XML Reader

```php
$reader = \Bavix\XMLReader\XMLReader::sharedInstance();
$data = $reader->asArray('c.xml');
var_dump($reader->asXML($data));
```
