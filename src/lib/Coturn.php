<?php
use Location\Coordinate;
use Location\Distance\Vincenty;
        
define("DEFAULTSERVERCOUNT", 2);
define("DEFAULTURISCHEMALIST", "stun,turn,turns"); //stuns disabled because chrome 48.0.2564.116 has issue with it (Malformed URL)
define("DEFAULTIPVERSIONLIST", "ipv4,ipv6");
define("DEFAULTTRANSPORTLIST", "udp,tcp");

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
     *         description="realm, the domain of the shared secret, default=turn.geant.org",
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
     *     @SWG\Parameter(
     *         description="URI Schema: 'stun','stuns','turn','turns'",
     *         in="query",
     *         name="uri_schema",
     *         required=false,
     *         type="string",
     *         default="stun,turn,turns",
     *         enum={"stun","stuns","turn","turns","stun,stuns","stun,turn","stun,turns","stuns,turn","stuns,turns","turn,turns","stun,stuns,turn","stun,stuns,turns","stun,turn,turns","stuns,turn,turns","stun,stuns,turn,turns" }
     *     ),
     *     @SWG\Parameter(
     *         description="Transport protocol: 'udp','tcp','sctp'",
     *         in="query",
     *         name="transport",
     *         required=false,
     *         type="string",
     *         default="udp,tcp",
     *         enum={"udp","tcp","sctp","udp,tcp","udp,sctp","tcp,sctp","udp,tcp,sctp"}
     *     ),
     *     @SWG\Parameter(
     *         description="Internet Protocol version: 'ipv4','ipv6','ipv4,ipv6'",
     *         in="query",
     *         name="ip_ver",
     *         required=false,
     *         type="string",
     *         default="ipv4,ipv6",
     *         enum={"ipv4","ipv6","ipv4,ipv6"}
     *     ),
     *     @SWG\Parameter(
     *         description="Maximum servers count in Response",
     *         in="query",
     *         name="servercount",
     *         required=false,
     *         type="integer",
     *         maximum=4,
     *         default="2"
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
     *         description="realm, the domain of the shared secret, default=turn.geant.org",
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
     *     @SWG\Parameter(
     *         description="URI Schema: 'stun','stuns','turn','turns'",
     *         in="query",
     *         name="uri_schema",
     *         required=false,
     *         type="string",
     *         default="stun,turn,turns",
     *         enum={"stun","stuns","turn","turns","stun,stuns","stun,turn","stun,turns","stuns,turn","stuns,turns","turn,turns","stun,stuns,turn","stun,stuns,turns","stun,turn,turns","stuns,turn,turns","stun,stuns,turn,turns" }
     *     ),
     *     @SWG\Parameter(
     *         description="Transport protocol: 'udp','tcp','sctp'",
     *         in="query",
     *         name="transport",
     *         required=false,
     *         type="string",
     *         default="udp,tcp",
     *         enum={"udp","tcp","sctp","udp,tcp","udp,sctp","tcp,sctp","udp,tcp,sctp"}
     *     ),
     *     @SWG\Parameter(
     *         description="Internet Protocol version: 'ipv4','ipv6','ipv4,ipv6'",
     *         in="query",
     *         name="ip_ver",
     *         required=false,
     *         type="string",
     *         default="ipv4,ipv6",
     *         enum={"ipv4","ipv6","ipv4,ipv6"}
     *     ),
     *     @SWG\Parameter(
     *         description="Maximum servers count in Response",
     *         in="query",
     *         name="servercount",
     *         required=false,
     *         type="integer",
     *         maximum=4
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
      $realm="turn.geant.org";

      try {
          $servercount=$app->request->params('servercount');
          if (is_numeric($servercount)){
              settype($servercount,"integer");
          }
          if( !is_int($servercount) || $servercount < 1 ){
              $servercount=DEFAULTSERVERCOUNT;
          }

          //connectdb
          $db = Db::Connection("coturn-rest");

          /// TOKEN IS A PARAM VARIABLE
          $token=$app->request->params('api_key');

          //validate token
          $sth = $db->prepare("SELECT count(*) AS count FROM token where token='$token'");
          $sth->execute();
          $result = $sth->fetchAll(PDO::FETCH_ASSOC);
          if ($result[0]["count"]==1){
          // response
          $response=new ApiResponse();
          $response->ttl=86400;
          // if ufrag
          if(empty($app->request->params('ufrag'))){
               $response->username=(string)(time() + $response->ttl);
          } else {
               $response->username=(string)(time() + $response->ttl).":".$app->request->params('ufrag');
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


              $sth = $db->prepare("SELECT id,latitude,longitude FROM ip group by server_id");
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

              for($i=0;$i < $servercount;$i++) {
                  //add turnserver
                  $this->serverURIs($db,$servers[$i],$uris,$app);
              }


          } else {
              //default random servers
              $sth = $db->prepare("SELECT id FROM server ORDER BY RAND() limit :servercount");
              $sth->bindParam(':servercount', $servercount, PDO::PARAM_INT);
              $sth->execute();
              $result = $sth->fetchAll(PDO::FETCH_ASSOC);
              foreach ($result as $row => $columns) {
                  //add a turnserver
                  $this->serverURIs($db,$columns["id"],$uris,$app);
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

    private function serverURIs(&$db,&$server_id,&$uris,&$app) {
        //transport portnumber
        $transport_ports=$app->request->params('transport_port');
        $transport_port=$app->request->params('transport_port');

        //transport protocol
        $transports=$app->request->params('transport');

        $transportsql="";

        //if no praram set use default list
        if( isset($transports) ){
             $transportsarray=explode(",",$transports);
        } else {
             $transportsarray=explode(",",DEFAULTTRANSPORTLIST);
        }
      
        $i=0;
        $transportsql.="and (";
        foreach($transportsarray as $transport) {
            $i++;
            if($i>1) $transportsql.=" or ";
            switch($transport){
                case 'udp':
                    $transportsql.='protocol="udp"';
                    break;
                 case 'tcp':
                    $transportsql.='protocol="tcp"';
                    break;
                 case 'sctp':
                    $transportsql.='protocol="sctp"';
                    break;
                 default:
                    $app->response->setStatus(400);
                    $app->response()->headers->set('Content-Type', 'application/json');
                    echo '{"error":{"text": "Invalid transport protocol srting in transport paramter list! Only "udp", "tcp", "sctp" is allowed." }}';
                    exit;
            }
        }
        $transportsql.=")";


         //ip version
        $ipversions=$app->request->params('ip_ver');

        $ipversionsql="";

        //if no praram set use default list
        if( isset($ipversions) ){
             $ipversionsarray=explode(",",$ipversions);
        } else {
             $ipversionsarray=explode(",",DEFAULTIPVERSIONLIST);
        }
      
        $i=0;
        $ipversionsql.="and (";
         foreach($ipversionsarray as $ipversion) {
            $i++;
            if($i>1) $ipversionsql.=" or ";
            switch($ipversion){
                case 'ipv4':
                    $ipversionsql.="ipv6=0";
                    break;
                 case 'ipv6':
                    $ipversionsql.="ipv6=1";
                    break;
                default:
                    $app->response->setStatus(400);
                    $app->response()->headers->set('Content-Type', 'application/json');
                    echo '{"error":{"text": "Invalid ip version srting in ip_ver paramter list! Only "ipv4", "ipv6" is allowed." }}';
                    exit;
            }
        }
        $ipversionsql.=")";


        //uri schema param
        $urischemas=$app->request->params('uri_schema');

        $urischemassql="";
         //if no praram set use default list
        if(isset($urischemas)){
            $urischemasarray=explode(",",$urischemas);
        } else {
            $urischemasarray=explode(",",DEFAULTURISCHEMALIST);
         }
       
         $i=0;
         $urischemassql.="and (";
          foreach($urischemasarray as $uri_schema) {
             $i++;
             if($i>1) $urischemassql.=" or ";
             switch($uri_schema){
                 case 'stun':
                 case 'stuns':
                 case 'turn':
                 case 'turns':
                     $urischemassql.="uri_schema ='".$uri_schema."'";
                     break;
                 default:
                     $app->response->setStatus(400);
                     $app->response()->headers->set('Content-Type', 'application/json');
                     echo '{"error":{"text": "Invalid uri_schema value in uri_schema list! Only "stun", "stuns", "turn", "turns" is allowed." }}';
                     exit;
             }
         }
         $urischemassql.=")";
         $sql="SELECT ip,ipv6,port,protocol,uri_schema FROM ip left join service on ip.id=service.ip_id WHERE server_id='".$server_id."'".$urischemassql.$ipversionsql.$transportsql." ORDER BY ip.preference,service.preference";
        $sth2 = $db->prepare($sql);
        $sth2->execute();
        $result2 = $sth2->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result2 as $row2 => $columns2) {
            if($columns2["ipv6"]){
                $uri=$columns2["uri_schema"].':['.$columns2["ip"].']:'.$columns2["port"].'?'.'transport='.$columns2["protocol"];
            } else {
                $uri=$columns2["uri_schema"].':'.$columns2["ip"].':'.$columns2["port"].'?'.'transport='.$columns2["protocol"];
            }
            array_push($uris,$uri);
        }
    }
}
