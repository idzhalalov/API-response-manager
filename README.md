#Response Manager
The library is intended to make valid http **json** responses for an API users. It's based on "Response" class of Unirest PHP 
library (mashape/unirest-php).

I had to work with legacy code. It was an API implemented using a procedural paradigm. It was important to make sure 
that a software engineer is able to stop the program on a particular step and return a valid http response.

##Description

The library consists of 2 main components: a ResponseManager and a Response.

##Examples

###Status 200
```
$result = ['ok' => true, 'user_name' => 'Foo'];
ResponseManager::getInstance(200, $result)->send()->finish();
```
###Status 304
```
ResponseManager::getInstance(304)->send()->finish();
```
###Using of `finish()` separately 
```
if ($somethig) {
    // doing something
    
    $response = ResponseManager::getInstance(206);
} else {
    $response = ResponseManager::getInstance(200);
}
$resp->setHeader($headers);
$resp->send();

// Then we have to do something
while ($someConditions) {
    // doing something
}
$response->finish();
```

Also you can take look for an example within `index.php`.

##Running tests

Use `--process-isolation` while running tests to escape of `headers already sent` error:

```
./vendor/bin/phpunit --process-isolation ResponseTest.php
```
