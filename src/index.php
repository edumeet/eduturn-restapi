<?php
/**
 * @SWG\Info(
 *     version="1.0",
 *     title="TURN REST API",
 *     @SWG\Contact(name="Mihaly MESZAROS", url="http://misi.rest.netip.hu/")
 * )
 */
require_once("../vendor/autoload.php");
require_once("lib/Db.php");
require_once("lib/Users.php");

$app = new \Slim\Slim();
$app->setName('TURN REST API');

$app->container->singleton('token', function (){
    return "xyz"; /// token
});

$app->get('/', function () {
  $swagger = \Swagger\scan('../src');
  header('Content-Type: application/json');
  echo $swagger;
});

$app->get('/user', '\Users:All');

$app->run();