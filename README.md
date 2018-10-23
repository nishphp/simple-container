# simple-container
Simple container of auto wiring with constructor.

    $c = Container::getInstance();
    $obj = $c->get(stdClass::class);

    $c->call(Foo::class, 'staticMethod');
    
    $foo = new Foo();
    $c->call($foo, 'method');

@see https://github.com/nishimura/simple-container/blob/master/test/ContainerTest.php
