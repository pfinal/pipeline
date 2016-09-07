<?php

require __DIR__ . '/../vendor/autoload.php';

class M1
{
    public function handle($request, \Closure $next)
    {
        var_dump('m1-start');

        $response = $next($request);
        $response++;
        var_dump('m1-end');
        return $response;
    }
}

class M2
{
    public function handle($request, \Closure $next, $num, $num2, $num3)
    {
        var_dump('m2-start');

        $response = $next($request);
        var_dump($num);
        var_dump($num2);
        var_dump($num3);
        var_dump('m2-end');
        return $response;
    }
}

$app = new \Pimple\Container();

$app['m1'] = function () {
    return new M1;
};
$app['m2'] = function () {
    return new M2;
};
$pipeline = new \PFinal\Pipeline\Pipeline($app);

$middleware = [
    'm1',
    'm2:a,b,c',
    function ($request, \Closure $next) {
        var_dump('fun-start');
        $response = $next($request);
        $response++;
        var_dump('fun-end');
        return $response;
    },
];

$result = $pipeline->send('request')->through($middleware)->then(function ($request) {
    var_dump($request);
    return 2;
});

var_dump($result);