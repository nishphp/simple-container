<?php
declare(strict_types=1);

namespace Nish\Container;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Exception;
use Error;
use RuntimeException;
use stdClass;
use ArrayObject;

error_reporting(-1);

class Foo
{
    public $bar;
    public function __construct(Bar $bar){ $this->bar = $bar; }
    public function method(Bar $bar, Baz $baz){
        return [$bar, $baz];
    }
    public static function staticMethod(Bar $bar, Baz $baz){
        return [$baz, $bar];
    }

    public function methodWithDefault(string $arg = 'def')
    {
        return $arg;
    }

    public function notResolve(Bar $arg = null)
    {
        return $arg;
    }

    public function notFound(Fooo $arg)
    {
        return $arg;
    }
}
class Bar {}
class Baz {}


final class ContainerTest extends TestCase
{
    public function tearDown(): void
    {
        Container::getInstance()->clear();
    }

    public function testPrivateConstructor()
    {
        $this->expectException(Error::class);
        $c = new Container();
    }

    public function testGetInstance()
    {
        $c = Container::getInstance();
        $this->assertInstanceOf(Container::class, $c);
        $this->assertInstanceOf(ContainerInterface::class, $c);
    }

    public function testSetAndGet()
    {
        $c = Container::getInstance();
        $obj = new stdClass();
        $obj->foo = 'bar';
        $c->set(stdClass::class, $obj);
        $obj2 = $c->get(stdClass::class);

        $this->assertSame($obj, $obj2);

        $obj3 = Container::gets(stdClass::class);
        $this->assertInstanceOf(stdClass::class, $obj3);
    }

    public function testMayGet()
    {
        $c = Container::getInstance();

        $obj = Container::mayget(stdClass::class);
        $this->assertNull($obj);

        $obj = new stdClass();
        $obj->foo = 'bar';
        $c->set(stdClass::class, $obj);

        $obj = Container::mayget(stdClass::class);
        $this->assertInstanceOf(stdClass::class, $obj);
    }

    public function testFactory()
    {
        $c = Container::getInstance();
        $c->setFactory(stdClass::class, function($c){
            $obj = new stdClass();
            $obj->foo = 'bar';
            return $obj;
        });

        $a = $c->get(stdClass::class);
        $this->assertInstanceOf(stdClass::class, $a);
        $b = $c->get(stdClass::class);
        $this->assertInstanceOf(stdClass::class, $b);
        $this->assertNotSame($a, $b);
    }

    public function testFactoryRegister()
    {
        $c = Container::getInstance();
        $c->setFactory(stdClass::class, function($c){
            $obj = new stdClass();
            $obj->foo = 'bar';
            $c->set(stdClass::class, $obj);
            return $obj;
        });

        $a = $c->get(stdClass::class);
        $b = $c->get(stdClass::class);
        $this->assertSame($a, $b);
    }

    public function testFactoryException()
    {
        $this->expectException(ContainerExceptionInterface::class);

        $c = Container::getInstance();
        $c->setFactory(stdClass::class, function($c){
            $obj = new NotFound();
            return $obj;
        });
        $a = $c->get(stdClass::class);
    }

    public function testAutoWiring()
    {
        $c = Container::getInstance();
        $a = $c->get(Foo::class);
        $this->assertInstanceOf(Foo::class, $a);
        $this->assertInstanceOf(Bar::class, $a->bar);
    }

    public function testAutoWiringWithParameter()
    {
        $c = Container::getInstance();
        $c->set('ArrayObject#__construct.array', ['foo' => 'bar']);
        $c->set('ArrayObject#__construct.flags', 0);
        $c->set('ArrayObject#__construct.iteratorClass', 'ArrayIterator');
        $a = $c->get(ArrayObject::class);
        $this->assertEquals($a['foo'], 'bar');
    }

    public function testCallAutoWiring()
    {
        $c = Container::getInstance();

        list($a, $b) = $c->call(Foo::class, 'staticMethod');
        $this->assertInstanceOf(Baz::class, $a);
        $this->assertInstanceOf(Bar::class, $b);

        $foo = new Foo(new Bar());
        list($a, $b) = $c->call($foo, 'method');
        $this->assertInstanceOf(Bar::class, $a);
        $this->assertInstanceOf(Baz::class, $b);
    }

    public function testCallMethodWithDefault()
    {
        $c = Container::getInstance();
        $foo = new Foo(new Bar());
        $ret = $c->call($foo, 'methodWithDefault');
        $this->assertEquals('def', $ret);
    }

    public function testOverrideDefaultArgument()
    {
        $c = Container::getInstance();
        $c->set(Foo::class . '#methodWithDefault.arg', 'override');

        $foo = new Foo(new Bar());
        $ret = $c->call($foo, 'methodWithDefault');
        $this->assertEquals('override', $ret);
    }

    public function testNotResolveArgument()
    {
        $c = Container::getInstance();
        $c->setFactory(Bar::class, function($c){
            throw new RuntimeException('deep error');
        });

        $foo = new Foo(new Bar());
        $ret = $c->call($foo, 'notResolve');
        $this->assertNull($ret);
    }

    public function testCallException()
    {
        // auto wiring
        // not ContainerExceptionInterface
        $this->expectException(Exception::class);

        $c = Container::getInstance();
        $c->call(Foo::class, 'dummyMethod');
    }

    public function testExceptionDeep()
    {
        $this->expectException(ContainerExceptionInterface::class);

        $c = Container::getInstance();
        $c->setFactory(Bar::class, function($c){
            throw new RuntimeException('deep error');
        });
        // constructor is not allowed null argument
        $c->get(Foo::class);
    }

    public function testCallNotFoundArgumentClass()
    {
        $this->expectException(Exception::class);

        $c = Container::getInstance();
        $c->call(Foo::class, 'notFound');
    }

}
