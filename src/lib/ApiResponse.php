<?php

/**
 * @SWG\Definition(definition="ApiResponse")
 */
class ApiResponse {

    /**
     * Username
     * @SWG\Property(default="1375043478:abcd1234")
     * @var string
     */
    public $username;

    /**
     * Password
     * @SWG\Property(default="<HMAC(1375043487:abcd1234, SharedSecrets)>")
     * @var string
     */
    public $password;

    /**
     * Time to Live
     * @SWG\Property(default=3600)
     * @var integer
     */
    public $ttl = 3600;

    /**
     * The stun/turn uris
     * @SWG\Property(default="[turn:turn.bar.com:3478?proto=udp, turn:turn.bar.com:3478?proto=tcp, turns:turn.bar.com:443?proto=tcp]")
     * @var string[]
     */
    public $uris;
}

?>
