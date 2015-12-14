<?php

/**
 * @SWG\Swagger(
 *     basePath="/restapi",
 *     host="brain.lab.vvc.niif.hu",
 *     schemes={"https"},
 *     @SWG\Info(
 *         version="1.0",
 *         title="STUN TURN REST API",
 *         description="NIIF Intitute STUN TURN REST API pilot",
 *         @SWG\Contact(name="Mihály MÉSZÁROS", url="https://brain.lab.vvc.niif.hu"),
 *     ),
 *     @SWG\Tag(
 *       name="rest api",
 *       description="STUN/TURN time limited long term credential mechanism"
 *     )
 * )
 */


require_once("../vendor/autoload.php");
require_once("../../Db.php");
require_once("lib/Coturn.php");
require_once("lib/ApiResponse.php");

$app = new \Slim\Slim();
$app->setName('TURN REST API');

$app->container->singleton('token', function (){
    return "xyz"; /// token
});

$app->get('/', function () use ($app) {
  $app->redirect('doc/index.html');
});

 
$app->get('/swagger.json', function () {
  $swagger = \Swagger\scan('.');
  header('Content-Type: application/json');
  echo $swagger;
});

$app->get('/stun', '\Coturn:Get');
$app->get('/turn', '\Coturn:Get');

$app->run();
