# simple-container
Simple container of auto wiring with constructor.

    <?php
    use Nish\Container;

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
    

@see https://github.com/nishimura/simple-container/blob/master/test/ContainerTest.php
