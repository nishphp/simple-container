# simple-container

[![Build Status](https://travis-ci.org/nishimura/simple-container.svg?branch=master)](https://travis-ci.org/nishimura/simple-container)
[![Coverage Status](https://coveralls.io/repos/github/nishimura/simple-container/badge.svg?branch=master)](https://coveralls.io/github/nishimura/simple-container?branch=master)
[![Code Climate](https://codeclimate.com/github/nishimura/simple-container/badges/gpa.svg)](https://codeclimate.com/github/nishimura/simple-container)


[![Latest Stable Version](https://poser.pugx.org/nish/simple-container/v/stable)](https://packagist.org/packages/nish/simple-container)
[![License](https://poser.pugx.org/nish/simple-container/license)](LICENSE)


Simple container of auto wiring with constructor.

```php
<?php
use Nish\Container\Container;

require_once 'vendor/autoload.php';

$c = Container::getInstance();
$obj = $c->get(stdClass::class);

$c->call(Foo::class, 'staticMethod');

$foo = new Foo();
$c->call($foo, 'method');

$c->setFactory('MyClass', function($c){
    return new MyClass('custom param');
});
$c->setFactory(MyClass::class, function($c){
    $obj = new MyClass('custom param');
    $c->set(MyClass::class, $obj); // singleton
    return $obj;
});

// set arguments
namespace MyProject;
class Db {
    private $dsn;
    public function __construct(string $dsn){
        $this->dsn = $dsn;
    }
    // ...
}
$c->set('MyProject\\Db#__construct.dsn', 'mysql://dbname...');
$db = $c->get(MyProject\\Db::class);
```


@see https://github.com/nishimura/simple-container/blob/master/test/ContainerTest.php
