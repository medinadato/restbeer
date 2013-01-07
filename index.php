<?php

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//Composer loader
$loader = require_once __DIR__ . '/vendor/autoload.php';

// new application
$app = new Application();

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_sqlite',
        'path' => __DIR__ . '/app.db',
    )
));

// debug 
$app['debug'] = true;
// logger
//$app['db']->getConfiguration()->setSQLLogger(new Doctrine\DBAL\Logging\EchoSQLLogger());

/**
 * Search
 */
$app->get('/beers/{id}', function($id) use ($app) {

            if ($id == null) {
                $sql = "SELECT b.id, b.name, s.name AS style 
                    FROM beer b, style s 
                    WHERE b.style_id = s.id";
                $beers = $app['db']->fetchAll($sql);
                return new Response(json_encode($beers), 200);
            }

            $sql = "SELECT b.id, b.name, s.name AS style 
                FROM beer b, style s 
                WHERE b.style_id = s.id AND b.name = ?";
            $beer = $app['db']->fetchAssoc($sql, array($id));

            if (!$beer)
                return new Response(json_encode('Beer not found'), 404);

            return new Response(json_encode($beer), 200);
        })->value('id', null);


/**
 * Insert
 */
$app->post('/beers', function (Request $request) use ($app) {

            // get data
            if (!$data = $request->get('beer'))
                return new Response('Missing parameters', 400);
            try {
                $app['db']->insert('beer', array(
                    'id' => null,
                    'name' => $data['name'],
                    'style_id' => (int) $data['style_id'],
                ));
            } catch (\Exception $e) {
                return new Response(json_encode($e->getMessage()), 404);
            }

            // redirect to new beer
            return $app->redirect('/beers/' . $data['name'], 201);
        });

/**
 * Update (put)
 */
$app->put('/beers/{id}', function (Request $request, $id) use ($app) {
            // get data
            if (!$data = $request->get('beer'))
                return new Response('Missing parameters', 400);
            try {
                $sql = "SELECT * FROM beer WHERE name = ?";
                $beer = $app['db']->fetchAssoc($sql, array($id));

                if (!$beer)
                    return new Response(json_encode('Beer not found'), 404);

                $app['db']->update('beer', array(
                    'name' => $data['name'],
                    'style_id' => $data['style_id'],
                        ), array(
                    'id' => $beer['id'],
                        )
                );
            } catch (\Exception $e) {
                echo $e->getMessage();
            }

            // redirect to new beer
            return new Response('Beer updated', 200);
        });
/**
 * Delete (delete)
 */
$app->delete('/beers/{id}', function (Request $request, $id) use ($app) {
            // get data
            try {
                $sql = "SELECT * FROM beer WHERE name = ?";
                $beer = $app['db']->fetchAssoc($sql, array($id));

                if (!$beer)
                    return new Response(json_encode('Beer not found'), 404);

                $app['db']->delete('beer', array(
                    'id' => $beer['id'],
                        )
                );
            } catch (\Exception $e) {
                return new Response(json_encode($e->getMessage()), 404);
            }

            // redirect to new beer
            return new Response('Beer Deleted', 200);
        });
/**
 * 
 */
$app->before(function (Request $request) use ($app) {
//            if (!$request->headers->has('authorization')) {
//                return new Response('Unauthorized', 401);
//            }
//            require_once 'configs/keys.php';
//            if (!in_array($request->headers->get('authorization'), array_keys($authorized_keys))) {
//                return new Response('Unauthorized', 401);
//            }
        });

/**
 * 
 */
$app->after(function(Request $request, Response $response) {
            $response->headers->set('Content-type', 'text/json');
        });
        
/**
 * 
 */
$app->get('/styles', function() use ($app) {
            $sql = "SELECT * FROM style";
            $styles = $app['db']->fetchAll($sql);
            
            if(!$styles)
                return new Response('No styles found', 404);
            
            return new Response(json_encode($styles), 200);
        });

/**
 * Run Forrest, run!
 */
$app->run();