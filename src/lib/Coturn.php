<?php

class Coturn {
    /**
     * @SWG\Get(
     *     path="/turn",
     *     summary="Request for stun/turn time limited long term credential",
     *     tags={"rest api"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         description="username fragement, any desired application data",
     *         in="query",
     *         name="ufrag",
     *         required=false,
     *         type="string",
     *         maxLength=25
     *     ),
     *     @SWG\Parameter(
     *         description="realm, the domain of the shared secret, default=lab.vvc.niif.hu",
     *         in="query",
     *         name="realm",
     *         required=false,
     *         type="string",
     *         maxLength=254,
     *     ),
     *     @SWG\Parameter(
     *         description="client browser iso country",
     *         in="query",
     *         name="country",
     *         required=false,
     *         type="string",
     *         minLength=2,
     *         maxLength=2
     *     ),
     *     @SWG\Response(
     *       response="200",
     *       description="STUN time limited credentials",
     *       @SWG\Schema(
     *         ref="#/definitions/ApiResponse"
     *       ),
     *     ),
     *     security={
     *          {
     *       		"api_key":{ }
     *          }
     *     }
     * )
     */
    /**
     * @SWG\Get(
     *     path="/stun",
     *     summary="Request for stun/turn time limited long term credential",
     *     tags={"rest api"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         description="username fragement, any desired application data",
     *         in="query",
     *         name="ufrag",
     *         required=false,
     *         type="string",
     *         maxLength=25
     *     ),
     *     @SWG\Parameter(
     *         description="realm, the domain of the shared secret, default=lab.vvc.niif.hu",
     *         in="query",
     *         name="realm",
     *         required=false,
     *         type="string",
     *         maxLength=254,
     *     ),
     *     @SWG\Parameter(
     *         description="client browser iso country",
     *         in="query",
     *         name="country",
     *         required=false,
     *         type="string",
     *         minLength=2,
     *         maxLength=2
     *     ),
     *     @SWG\Response(
     *       response="200",
     *       description="STUN time limited credentials",
     *       @SWG\Schema(
     *         ref="#/definitions/ApiResponse"
     *       )
     *     ),
     *     security={
     *          {
     *       		"api_key":{ }
     *          }
     *     }
     * )
     */
    public function Get() {

      $app = \Slim\Slim::getInstance();

      //default realm
      $realm="lab.vvc.niif.hu";

      try 
      {
          /// TOKEN IS A PARAM VARIABLE
          if ($app->request->params('api_key') == $app->token) {
          /// TOKEN IS A HEADER VARIABLE
          //if ($app->request->headers('api_key') == $app->token) { 

            // response
            $response=new ApiResponse();
            $response->ttl=3600;
            $response->username=time().":".$app->request->params('ufrag');

            if ($app->request->params('realm')){ 
                $realm=$app->request->params('realm');
            }

            $db = Db::Connection();
            $sth = $db->prepare("SELECT value FROM turn_secret where realm='$realm' limit 1");
            $sth->execute();
            $data = $sth->fetchAll(PDO::FETCH_OBJ);
            if($data[0]->value) {
               $response->password=hash_hmac("sha1",$response->username,$data[0]->value);
               $app->response->setStatus(200);
               $app->response()->headers->set('Content-Type', 'application/json');
               echo json_encode($response);
               $db = null;
            } else {
                throw new PDOException('No records found.');
            }


          } else {
            $app->response->setStatus(404);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo '{"error":{"text": "Invalid api_key" }}';
          }
   
      } catch(PDOException $e) {
          $app->response()->setStatus(404);
          $app->response()->headers->set('Content-Type', 'application/json');
          echo '{"error":{"text": "'. $e->getMessage() .'"}}';
      }
    }

}
