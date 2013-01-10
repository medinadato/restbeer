<?php

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\SessionServiceProvider;

//Composer loader
$loader = require_once __DIR__ . '/vendor/autoload.php';

// new application
$app = new Application();


# ------------------------------------------------------------------------------
# Registering
# ------------------------------------------------------------------------------
// debug 
$app['debug'] = true;
// timezone
date_default_timezone_set('Australia/Melbourne');

$app->register(new DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_sqlite',
        'path' => __DIR__ . '/app.db',
    )
));

// logger
//$app['db']->getConfiguration()->setSQLLogger(new Doctrine\DBAL\Logging\EchoSQLLogger());

$app->register(new MonologServiceProvider, array(
    'monolog.logfile' => __DIR__ . '/logs/app.log',
));

if ($app['debug']) {
//    $app['monolog']->addInfo('Testing');
//    $app['monolog']->addDebug('Debuging file foo');
//    $app['monolog']->addWarning('Warning parameter');
//    $app['monolog']->addError('Class foo not found');
}

$app->register(new TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/app/views',
));


$app->register(new SwiftmailerServiceProvider(), array(
    'swiftmailer.options' => array(
        'host' => 'smtp.gmail.com',
        'port' => 465,
        'username' => 'contato@mdnsolutions.com',
        'password' => '',
        'encryption' => 'ssl',
        'auth_mode' => 'login')
));

////Right we now need to set the transport to Spool.
// 
////Set the directory you want the spool to be in.
//$app["swiftmailer.spool"] = new \Swift_FileSpool(__DIR__."/../spool");
////Take a copy of the original transport, we'll need that later.
//$app["swiftmailer.transport.original"] = $app["swiftmailer.transport"];
////Create a spool transport
//$app["swiftmailer.transport"] = new \Swift_Transport_SpoolTransport($app['swiftmailer.transport.eventdispatcher'], $app["swiftmailer.spool"]);

$app->register(new SessionServiceProvider());

# ------------------------------------------------------------------------------
# Routing
# ------------------------------------------------------------------------------

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
//                return $app->json($beers, 200); (The same)
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
$app->get('/styles', function() use ($app) {

            $sql = "SELECT * FROM style";
            $styles = $app['db']->fetchAll($sql);

            if (!$styles)
                return new Response('No styles found', 404);

            return $app['twig']->render('styles.xml.twig', array(
                        'styles' => $styles,
                    ));
        });

/**
 * 
 */
$app->get('/mail', function() use ($app) {
            $message = \Swift_Message::newInstance()
                    ->setSubject('teste')
                    ->setFrom('medinadato@gmail.com')
                    ->setTo('medinadato@hotmail.com')
                    ->setBody('this is a body message');

            $app['mailer']->send($message);

            return new Response('Tks for your feedback', 201);
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
$app->after(function(Request $request, Response $response) use ($app) {
//            if (!$views = $app['session']->get('views')) {
//                $app['session']->set('views', 1);
//            } else {
//                $app['session']->set('views', ++$views);
//            }
            
//            $response->headers->set('Content-type', 'text/xml');
        });

/**
 * 
 */
$app->error(function (\Exception $e, $code) {
//            switch ($code) {
//                case 400:
//                    $message = 'Bad Request';
//                    break;
//                case 404:
//                    $message = 'Page not found';
//                    break;
//                default:
//                    $message = 'Internal Server Error.';
//            }
//            
//            return new Response($message, $code);
        });

/**
 * Run Forrest, run!
 */
$app->run();