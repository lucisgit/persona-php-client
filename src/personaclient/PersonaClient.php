<?php
namespace personaclient;

class PersonaClient {

    const VERIFIED_BY_PERSONA   =  'verified_by_persona';
    const VERIFIED_BY_CACHE     =  'verified_by_cache';

    /**
     * Cached connection to redis
     * @var \Predis\Client
     */
    private $tokenCacheClient = null;

    /**
     * Configuration object
     * @var Array
     */
    private $config = null;

    /**
     * Constructor
     *
     * @param array $config An array of options with the following keys: <pre>
     *      persona_host: (string) the persona host you'll be making requests to (e.g. 'http://localhost')
     *      persona_oauth_route: (string) the token api route to query ( e.g: '/oauth/tokens')
     *      tokencache_redis_host: (string) the host address of redis token cache
     *      tokencache_redis_port: (integer) the port number the redis host ist listening
     *      tokencache_redis_db: (integer) the database to connnect to</pre>
     * @throws \InvalidArgumentException if any of the required config parameters are missing
     */
    public function __construct($config) {
        if($this->checkConfig($config)){
            $this->config = $config;
        };
    }
    /**
     * Validates the specified token/scope if you supply as a parameters.
     * Otherwise calls the internal method getTokenFromRequest() in order to
     * extract the token from $_SERVER, $_GET or $_POST
     *
     * @param array $params a set of optional parameters you can pass to this method <pre>
     *      access_token: (string) a token to validate explicitly, if you do not specify one the method tries to find one,
     *      scope: (string) specify this if you wish to validate a scoped token
     * @return bool|string will return FALSE if could not validate the token. If it did validate the token it will return VERIFIED_BY_CACHE | VERIFIED_BY_PERSONA
     * $throws \Exception if you do not supply a token AND it cannot extract one from $_SERVER, $_GET, $_POST
     */
    public function validateToken($params = array()){

        $token = null;

        if(isset($params['access_token']) || !empty($params['access_token'])){
            $token = $params['access_token'];
        } else {
            $token = $this->getTokenFromRequest();
        }

        $cacheKey = $token;
        if(isset($params['scope']) && !empty($params['scope'])){
            $cacheKey .= '@' . $params['scope'];
        }

        $reply = $this->getCacheClient()->get("access_token:".$cacheKey);
        if($reply == 'OK'){
            // verified by cache
            return self::VERIFIED_BY_CACHE;
        } else {
            // verify against persona
            $url = $this->config['persona_host'].$this->config['persona_oauth_route'].'/'.$token;

            if(isset($params['scope']) && !empty($params['scope'])){
                $url .= '?scope=' . $params['scope'];
            }

            if($this->personaCheckTokenIsValid($url)){
                // verified by persona, now cache the token
                $this->getCacheClient()->set("access_token:".$cacheKey, 'OK');
                $this->getCacheClient()->expire("access_token:".$cacheKey, 60);
                return self::VERIFIED_BY_PERSONA;
            } else {
                return FALSE;
            }
        }
    }

    /**
     * Use this method to generate a new token. Works by first checking to see if a cookie is set containing the
     * access_token, if so this is returned. If there is no cookie we request a new one from persona. You must
     * specify client credentials to do this, for that reason this method will throw an exception if the credentials are missing.

     * @param $clientId
     * @param $clientSecret
     * @param array $params a set of optional parameters you can pass into this method <pre>
     *          scope: (string) to obtain a new scoped token </pre>
     * @return array containing the token details
     * @throws Exception if we were unable to generate a new token or if credentials were missing
     */
    public function obtainNewToken($clientId = "", $clientSecret = "", $params = array()) {
        if(!isset($_COOKIE['access_token'])) {

            if( empty($clientId) || empty($clientSecret)){
                throw new \Exception("You must specify clientId, and clientSecret to obtain a new token");
            }

            $query = array(
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret
            );

            if(isset($params['scope']) && !empty($params['scope'])){
                $query['scope'] = $params['scope'];
            }

            $url = $this->config['persona_host'].$this->config['persona_oauth_route'];
            return $this->personaObtainNewToken($url, $query);
        } else {
            return json_decode($_COOKIE['access_token'],true);
        }
    }

    /* Protected functions */

    /**
     * Attempts to find an access token based on the current request.
     * It first looks at $_SERVER headers for a Bearer, failing that
     * it checks the $_GET and $_POST for the access_token param.
     * If it can't find one it throws an exception.
     *
     * @return mixed the access token if it is found
     * @throws \Exception if no access token is found
     */
    protected function getTokenFromRequest(){
        $headers = array();
        foreach($_SERVER as $key => $value) {
            if (substr($key, 0, 5) <> 'HTTP_') {
                continue;
            }
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }
        if (isset($headers['Bearer'])) {
            if (!preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                throw new \Exception('Malformed auth header');
            }
            return $matches[1];
        }

        if (isset($_GET['access_token'])) return $_GET['access_token'];
        if (isset($_POST['access_token'])) return $_POST['access_token'];

        throw new \Exception("No OAuth token supplied");
    }

    /**
     * Checks the supplied config, verifies that all required parameters are present and
     * contain a non null value;
     *
     * @param array $config the configuration options to validate
     * @return bool if config passed
     * @throws \InvalidArgumentException if the config is invalid
     */
    protected function checkConfig($config){
        if(empty($config)){
            throw new \InvalidArgumentException("No config provided to Persona Client");
        }

        $requiredProperties = array(
            'persona_host',
            'persona_oauth_route',
            'tokencache_redis_host',
            'tokencache_redis_port',
            'tokencache_redis_db'
        );

        $missingProperties = array();
        foreach($requiredProperties as $property){
            if(!isset($config[$property])){
                array_push($missingProperties, $property);
            }
        }

        if(empty($missingProperties)){
            return true;
        } else {
            throw new \InvalidArgumentException("Config provided does not contain values for: " . implode(",", $missingProperties));
        }
    }

    /**
     * Lazy Loader, returns a predis client instance
     *
     * @return \Predis\Client a connected predis instance
     * @throws \Predis\Connection\ConnectionException if it cannot connect to the server specified
     */
    protected function getCacheClient(){
        if(!$this->tokenCacheClient){
            $this->tokenCacheClient = new \Predis\Client(array(
                'scheme'   => 'tcp',
                'host'     => $this->config['tokencache_redis_host'],
                'port'     => $this->config['tokencache_redis_port'],
                'database' => $this->config['tokencache_redis_db']
            ));
        }

        return $this->tokenCacheClient;
    }

    /**
     * This method wraps the curl request that is made to persona and
     * returns true or false depending on whether or not persona was
     * able to validate the token.
     *
     * @param $url string this is the full qualified url that will be hit
     * @return bool true if persona responds that the token was valid
     */
    protected function personaCheckTokenIsValid($url){
        $request = curl_init($url);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($request, CURLOPT_TIMEOUT, 30);
        curl_setopt($request, CURLOPT_CUSTOMREQUEST, 'HEAD');
        curl_exec($request);
        $meta = curl_getinfo($request);
        if (isset($meta) && $meta['http_code']==204) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Method that wraps the curl post request to persona for obtaining a new
     * token.
     *
     * @param $url string the persona endpoint to make the request against
     * @param $query array the set of parameters that will make up the post fields
     * @return array json decoded array containing the response body from persona
     * @throws \Exception if persona was unable to generate a token
     */
    protected function personaObtainNewToken($url, $query){
        $curlOptions = array(
            CURLOPT_POST            => true,
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_POSTFIELDS      => http_build_query($query)
        );

        $curl = curl_init();
        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $headers = curl_getinfo($curl);

        if (isset($headers['http_code']) && $headers['http_code']==200)
        {
            $data = json_decode($response,true);
            return $data;
        }
        else
        {
            throw new \Exception("Could not retrieve OAuth response code");
        }
    }
}