<?php

class Users {
    /**
     * @SWG\Get(
     *     path="/user",
     *     summary="All users",
     *     @SWG\Response(
     *       response="200",
     *       description="Users")
     *     )
     */
    public function All() {

      $app = \Slim\Slim::getInstance();

      try 
      {
/// TOKEN IS A PARAM VARIABLE
///         if ($app->request->params('token') == $app->token) {
/// TOKEN IS A HEADER VARIABLE
          if ($app->request->headers('token') == $app->token) { 

            $db = Db::Connection();

            $sth = $db->prepare("SELECT * FROM users");
     
            $sth->execute();
     
            $data = $sth->fetchAll(PDO::FETCH_OBJ);
     
            if($data) {
                $app->response->setStatus(200);
                $app->response()->headers->set('Content-Type', 'application/json');
                echo json_encode($data);
                $db = null;
            } else {
                throw new PDOException('No records found.');
            }

          } else {
            $app->response->setStatus(404);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo '{"error":{"text": "Invalid token" }}';
          }
   
      } catch(PDOException $e) {
          $app->response()->setStatus(404);
          $app->response()->headers->set('Content-Type', 'application/json');
          echo '{"error":{"text": "'. $e->getMessage() .'"}}';
      }
    }
}