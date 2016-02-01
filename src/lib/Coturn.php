<?php
use Location\Coordinate;
use Location\Distance\Vincenty;

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
     *         maximum=25
     *     ),
     *     @SWG\Parameter(
     *         description="realm, the domain of the shared secret, default=lab.vvc.niif.hu",
     *         in="query",
     *         name="realm",
     *         required=false,
     *         type="string",
     *         maximum=254,
     *     ),
     *     @SWG\Parameter(
     *         description="client browser IPv4/IPv6 Address",
     *         in="query",
     *         name="ip",
     *         required=false,
     *         type="string",
     *         maximum=45
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
     *         maximum=25
     *     ),
     *     @SWG\Parameter(
     *         description="realm, the domain of the shared secret, default=lab.vvc.niif.hu",
     *         in="query",
     *         name="realm",
     *         required=false,
     *         type="string",
     *         maximum=254,
     *     ),
     *     @SWG\Parameter(
     *         description="client browser IPv4/IPv6 Address",
     *         in="query",
     *         name="ip",
     *         required=false,
     *         type="string",
     *         maximum=45
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
         define("MAXSERVERS", 2);

         //connectdb
         $db = Db::Connection("coturn-rest");

         /// TOKEN IS A PARAM VARIABLE
         $token=$app->request->params('api_key');
         /// TOKEN IS A HEADER VARIABLE
         //$app->request->headers('api_key')  
         //TODO validate token:
         $sth = $db->prepare("SELECT count(*) AS count FROM token where token='$token'");
         $sth->execute();
         $result = $sth->fetchAll(PDO::FETCH_ASSOC);
         if ($result[0]["count"]==1){
         // response
         $response=new ApiResponse();
         $response->ttl=86400;
         // if ufrag
         if(empty($app->request->params('ufrag'))){
              $response->username=(time() + $response->ttl);
         } else {
              $response->username=(time() + $response->ttl).":".$app->request->params('ufrag');
         }

         //update not existing lat long in server table
         $sth = $db->prepare("SELECT id,ip FROM ip where latitude IS NULL OR longitude IS NULL");
         $sth->execute();
         $result = $sth->fetchAll(PDO::FETCH_ASSOC);
         foreach ($result as $row => $columns) {
            $location=$this->GetGeoIP($columns['ip']);
            $sth2 = $db->prepare("UPDATE ip SET latitude=$location->latitude, longitude=$location->longitude WHERE id=$columns[id]");
            $sth2->execute();
         }

         $uris=array();
         //check if ip presents
         if ($app->request->params('ip') && $location=$this->GetGeoIP ($app->request->params('ip'))){ 
             //geoip distance
             $client_coordinate = new Coordinate($location->latitude, $location->longitude); 


             $sth = $db->prepare("SELECT id,latitude,longitude FROM ip");
             $sth->execute();
             $result = $sth->fetchAll(PDO::FETCH_ASSOC);
             foreach ($result as $row => $columns) {
                 $server_coordinate = new Coordinate($columns['latitude'],$columns['longitude']);
                 $ips[$columns['id']]=$client_coordinate->getDistance($server_coordinate, new Vincenty()); 
             }
             asort($ips);
             $servers=array();
             foreach ($ips as $id => $distance) {
                 $sth = $db->prepare("SELECT server_id FROM ip WHERE id='".$id."'");
                 $sth->execute();
                 $result = $sth->fetchAll(PDO::FETCH_ASSOC);
                 foreach ($result as $row => $columns) {
                    array_push($servers,$columns["server_id"]);
                 }
              }
              for($i=0;$i < MAXSERVERS;$i++) {
                  //add turnserver
                  $this->serverURIs($db,$servers[$i],$uris);
              }
 
             
             
         } else {
             //default random servers
             $sth = $db->prepare("SELECT id FROM server ORDER BY RAND() limit ".MAXSERVERS);
             $sth->execute();
             $result = $sth->fetchAll(PDO::FETCH_ASSOC);
             foreach ($result as $row => $columns) {
                 //add a turnserver
                 $this->serverURIs($db,$columns["id"],$uris);
             }
         }

         //implode uris
         $response->uris=$uris;
         //check if realm presents
         if ($app->request->params('realm')){ 
             $realm=$app->request->params('realm');
         }


         $sth = $db->prepare("SELECT value FROM turn_secret where realm='$realm' ORDER BY timestamp DESC limit 1");
         $sth->execute();
         $sharedsecret = $sth->fetchColumn();
         if($sharedsecret) {
            $response->password=base64_encode(hash_hmac("sha1",$response->username,$sharedsecret,true));
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode($response);
            $db = null;
         } else {
             throw new PDOException('No records found.');
         }


        } else {
          $app->response->setStatus(403);
          $app->response()->headers->set('Content-Type', 'application/json');
          echo '{"error":{"text": "Invalid api_key" }}';
        }
 
      } catch(PDOException $e) {
          $app->response()->setStatus(500);
          $app->response()->headers->set('Content-Type', 'application/json');
          echo '{"error":{"text": "'. $e->getMessage() .'"}}';
      }
    }



    private function GetGeoIP ($ip) {
      $database = (strpos($ip, ":") === false) ? "GeoLiteCity.dat" : "GeoLiteCityv6.dat";
      $gi = geoip_open("/usr/local/share/GeoIP/$database",GEOIP_STANDARD);
      if((strpos($ip, ":") === false)) {
          //ipv4
          $record = geoip_record_by_addr($gi, $ip);
      }
      else {
          //ipv6
          $record = geoip_record_by_addr_v6($gi, $ip);
      }
      return $record;
    }

    private function serverURIs(&$db,&$server_id,&$uris) {
        $sth2 = $db->prepare("SELECT ip,port,protocol,uri_schema FROM ip left join service on ip.id=service.ip_id WHERE server_id='".$server_id."' ORDER BY ip.preference,service.preference");
        $sth2->execute();
        $result2 = $sth2->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result2 as $row2 => $columns2) {
            $uri=$columns2["uri_schema"].':'.$columns2["ip"].':'.$columns2["port"].'?'.'transport='.$columns2["protocol"];
            array_push($uris,$uri);
        }
    }
}
