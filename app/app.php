<?php 
// app/app.php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/config/parameters.php';

$app = new Silex\Application();
$app['debug'] = true;

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'dbname' => 'ports',
        'host' => '127.0.0.1',
        'user' => 'root',
        'password' => 'm345s445',
        'port'     => '3306',
    ),
));

$app->register(new Predis\Silex\ClientServiceProvider(), [
    //'predis.parameters' => 'tcp://127.0.0.1:6379',
    //'predis.parameters' => 'tcp://192.168.1.249:6379',
    'predis.parameters' => [
        'scheme' => 'tcp',
        'host' => '192.168.1.249',
        'port' => '6379',
        'password' => ';T7Fyc63JfVXq6KKEj$3;Pvw2H\>d3'
    ],
    'predis.options'    => [
        'prefix'  => 'gpio:',
        'profile' => '3.0',
    ]
]);


$app->register(new Silex\Provider\TwigServiceProvider(), 
    array(
    'twig.path' => __DIR__.'/../web/views',
));

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

// convert json data in anyrequest
$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});



$app->get('/', function () use ($app, $settings) {
        //return var_export($app['predis']->info(), true);
        //var_dump($app);die;    

        /*
        if ($app['predis']->get('counter')){
            $app['predis']->incr('counter');
        } else {
            $app['predis']->set('counter', 1);
        }

        var_dump($app['predis']->get('counter'));

        $arr = array('foo' => 'bar');
        if ($app['predis']->hmget('arr', array('foo'))){
            $app['predis']->hmset('arr', $arr);
        } else {
            $app['predis']->hmset('arr', $arr);
        }

        var_dump($app['predis']->hmget('arr', array('foo')));
        */

        return $app['twig']->render('index.html.twig', array(
        ));
        
});

$data = array("total" => 3, "page" => 1, "perPage" => 10, "ports" => array(
                array("id" => 18, "name" => "18", "occupation" => "red"),
                array("id" => 23, "name" => "23", "occupation" => "yellow"),
                array("id" => 24, "name" => "24", "occupation" => "green")
            )
        );

// get JSON of all ports
$app->get('/ports', function (Silex\Application $app) use ($settings, $data) {
    /*$sql = "SELECT * FROM ports";
    $stmt = $app['db']->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();
    $data = array("total" => count($results), "ports" =>$results);*/

    $results = array();
    
    $pins = array("18" => 'red', "23" => 'yellow', "24" => 'green');
    foreach ($pins as $pin => $colour) {
        if (0 == $app['predis']->exists($pin)){
            $app['predis']->hmset($pin, array("name" => $pin, "occupation" => $colour, "state" => 0));
        }
        $results[] = array(
            "id" => $pin, "name" => $app['predis']->hget($pin, 'name'), 
            "occupation" => $app['predis']->hget($pin, 'occupation'),
            "state" => $app['predis']->hget($pin, 'state')
            );

    }

    $data = array("total" => count($results), "page" => 1, "perPage" => 10, "ports" => $results);

    return $app->json($data, 200);
});

// add a port
$app->post('/ports', function (Silex\Application $app, Request $request) {

    $model = array(
        'name' => $request->request->get('name'),
        'occupation'  => $request->request->get('occupation'),
        'state'  => 0
    );

    $pin = 100;
    $app['predis']->hmset($pin, array("name" => $model['name'], "occupation" => $model['occupation'], "state" => 0));

    //todo dont know if we should have add
    //
    //$app['db']->insert('ports', $model);
    //$model['id'] = $app['db']->lastInsertId();;

    return $app->json($model, 200);
});

// fetch data for a specific port
$app->get('/ports/{id}', function (Silex\Application $app, $id) {

    $model = array(
        "id" => $id, "name" => $app['predis']->hget($id, 'name'), 
        "occupation" => $app['predis']->hget($id, 'occupation'), 
        "state" => $app['predis']->hget($id, 'state')
        );
    //$model = $app['db']->fetchAssoc('SELECT * FROM ports WHERE id = ?', array($id));
    if (!$model) {
        $app->abort(404, "Port $id does not exist.");
    }
    
    return $app->json($model, 200);
})->assert('id', '\d+');

// update a specific port
$app->put('/ports/{id}', function (Silex\Application $app, Request $request, $id) {

    $model = array(
        "id" => $id, "name" => $app['predis']->hget($id, 'name'), 
        "occupation" => $app['predis']->hget($id, 'occupation'), 
        "state" => $app['predis']->hget($id, 'state')
        );
    //$model = $app['db']->fetchAssoc('SELECT * FROM ports WHERE id = ?', array($id));
    if (!$model) {
        $app->abort(404, "Port $id does not exist.");
    }

    $model = array(
        'name' => $request->request->get('name'),
        'occupation'  => $request->request->get('occupation'),
        'state'  => $request->request->get('state')
    );

    // do stuff
    $app['predis']->hmset($id, array("name" => $model['name'], "occupation" => $model['occupation'], "state" => $model['state']));
    //$app['db']->update('ports', $model, array('id' => $id));

    return $app->json($model, 200);
})->assert('id', '\d+');

// delete a port
$app->delete('/ports/{id}', function (Silex\Application $app, $id) {

    $model = array("id" => $id, "name" => $app['predis']->hget($id, 'name'), "occupation" => $app['predis']->hget($id, 'occupation'), "state" => $app['predis']->hget($id, 'state'));
    //$model = $app['db']->fetchAssoc('SELECT * FROM ports WHERE id = ?', array($id));
    if (!$model) {
        $app->abort(404, "Port $id does not exist.");
    }
    //$app['predis']->del('gpio:'.$id);
    $app['predis']->del($id);
    //$app['db']->delete('ports', array('id' => $id));

    return $app->json($model, 200);
})->assert('id', '\d+');

$app->get('/temp', function (Silex\Application $app) use ($settings, $data) {

    $temp = $app['predis']->get('temp');

    $data = array("temp" => $temp);

    return $app->json($data, 200);
});

return $app;
