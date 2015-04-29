<?php

$appRoot = dirname(dirname(__DIR__));
if (!defined('APPROOT'))
{
    define('APPROOT', $appRoot);
}

require_once $appRoot . '/test/unit/TestBase.php';

class PersonaClientTest extends TestBase {

    function testEmptyConfigThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No config provided to Persona Client'
        );
        $personaClient = new personaclient\PersonaClient(array());
    }

    function testNullConfigThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No config provided to Persona Client'
        );
        $personaClient = new personaclient\PersonaClient(null);
    }

    function testMissingRequiredConfigParamsThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'Config provided does not contain values for: persona_host,persona_oauth_route,tokencache_redis_host,tokencache_redis_port,tokencache_redis_db'
        );
        $personaClient = new personaclient\PersonaClient(array(
            'persona_host' => null,
            'persona_oauth_route' => null,
            'tokencache_redis_host' => null,
            'tokencache_redis_port' => null,
            'tokencache_redis_db' => null,
        ));
    }

    function testValidConfigDoesNotThrowException(){
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
    }

    function testMissingUrlThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No url provided to sign'
        );
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('','mysecretkey',null);

    }

    function testMissingSecretThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No secret provided to sign with'
        );
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl','',null);

    }

    function testPresignUrlNoExpiry() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute','mysecretkey',null);
        $this->assertContains('?expires=',$signedUrl);
    }

    function testPresignUrlNoExpiryAnchor() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute#myAnchor','mysecretkey',null);

        // assert ?expiry comes before #
        $pieces = explode("#",$signedUrl);
        $this->assertTrue(count($pieces)==2);
        $this->assertContains('?expires=',$pieces[0]);

    }

    function testPresignUrlNoExpiryExistingQueryString() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',null);

        $this->assertContains('?myparam=foo&expires=',$signedUrl);
    }

    function testPresignUrlNoExpiryAnchorExistingQueryString() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',null);


        // assert ?expiry comes before #
        $pieces = explode("#",$signedUrl);
        $this->assertTrue(count($pieces)==2);
        $this->assertContains('?myparam=foo&expires=',$pieces[0]);
    }

    function testPresignUrlWithExpiry() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?expires=1234567890&signature=5be20a17931f220ca03d446a25748a9ef707cd508c753760db11f1f95485f1f6',$signedUrl);
    }

    function testPresignUrlWithExpiryAnchor() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute#myAnchor','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?expires=1234567890&signature=c4fbb2b15431ef08e861687bd55fd0ab98bb52eee7a1178bdd10888eadbb48bb#myAnchor',$signedUrl);
    }

    function testPresignUrlWithExpiryExistingQuerystring() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?myparam=foo&expires=1234567890&signature=7675bae38ddea8c2236d208a5003337f926af4ebd33aac03144eb40c69d58804',$signedUrl);
    }

    function testPresignUrlWithExpiryAnchorExistingQuerystring() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?myparam=foo&expires=1234567890&signature=f871db0896f6e893b607d2987ccc838786114b9778b4dbae2b554c2faf9486a1#myAnchor',$signedUrl);
    }

    function testIsPresignedUrlValidTimeInFuture() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute','mysecretkey',"+5 minutes");

        $this->assertTrue($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidTimeInFutureExistingParams() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo','mysecretkey',"+5 minutes");

        $this->assertTrue($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidTimeInFutureExistingParamsAnchor() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',"+5 minutes");

        $this->assertTrue($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidTimeInPastExistingParamsAnchor() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',"-5 minutes");

        $this->assertFalse($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidRemoveExpires() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',"+5 minutes");

        $presignedUrl = str_replace('expires=','someothervar=',$presignedUrl);

        $this->assertFalse($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidRemoveSig() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',"+5 minutes");

        $presignedUrl = str_replace('signature=','someothervar=',$presignedUrl);

        $this->assertFalse($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testUseCacheFalseOnObtainToken() {
        $mockClient = $this->getMock('\personaclient\PersonaClient',array('getCacheClient','personaObtainNewToken'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));

        $mockClient->expects($this->once())->method("personaObtainNewToken")->will($this->returnValue(array("access_token"=>"foo","expires"=>"100","scopes"=>"su")));
        $mockClient->expects($this->never())->method("getCacheClient");

        $mockClient->obtainNewToken('client_id','client_secret',array('useCache'=>false));
    }

    function testUseCacheTrueOnObtainToken() {
        $mockClient = $this->getMock('\personaclient\PersonaClient',array('getCacheClient','personaObtainNewToken'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));

        $mockCache = $this->getMock('\Predis\Client',array("get"),array());
        $mockCache->expects($this->once())->method("get")->will($this->returnValue('{"access_token":"foo","expires":1000,"scopes":"su"}'));

        $mockClient->expects($this->never())->method("personaObtainNewToken");
        $mockClient->expects($this->once())->method("getCacheClient")->will($this->returnValue($mockCache));

        $token = $mockClient->obtainNewToken('client_id','client_secret');
        $this->assertEquals($token['access_token'],"foo");
    }

    function testUseCacheDefaultTrueOnObtainToken() {
        $mockClient = $this->getMock('\personaclient\PersonaClient',array('getCacheClient','personaObtainNewToken'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));

        $mockCache = $this->getMock('\Predis\Client',array("get"),array());
        $mockCache->expects($this->once())->method("get")->will($this->returnValue('{"access_token":"foo","expires":1000,"scopes":"su"}'));

        $mockClient->expects($this->never())->method("personaObtainNewToken");
        $mockClient->expects($this->once())->method("getCacheClient")->will($this->returnValue($mockCache));

        $token = $mockClient->obtainNewToken('client_id','client_secret');
        $this->assertEquals($token['access_token'],"foo");
    }

    function testUseCacheNotInCacheObtainToken() {
        $mockClient = $this->getMock('\personaclient\PersonaClient',array('getCacheClient','personaObtainNewToken','cacheToken'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));

        $mockCache = $this->getMock('\Predis\Client',array("get"),array());
        $mockCache->expects($this->once())->method("get")->will($this->returnValue(''));

        $expectedToken = array("access_token"=>"foo","expires_in"=>"100","scopes"=>"su");
        $cacheKey = "obtain_token:".hash_hmac('sha256','client_id','client_secret');

        $mockClient->expects($this->once())->method("getCacheClient")->will($this->returnValue($mockCache));
        $mockClient->expects($this->once())->method("personaObtainNewToken")->will($this->returnValue($expectedToken));
        $mockClient->expects($this->once())->method("cacheToken")->with($cacheKey,$expectedToken,40);

        $token = $mockClient->obtainNewToken('client_id','client_secret');
        $this->assertEquals($token['access_token'],"foo");
    }

    function testGetUserByGupidEmptyGupidThrowsException(){
        $this->setExpectedException('InvalidArgumentException', 'Invalid gupid');
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->getUserByGupid('', '');
    }
    function testGetUserByGupidEmptyTokenThrowsException(){
        $this->setExpectedException('InvalidArgumentException', 'Invalid token');
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->getUserByGupid('123', '');
    }
    function testGetUserByGupidInvalidTokenThrowsException(){
        $this->setExpectedException('Exception', 'Could not retrieve OAuth response code');
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->getUserByGupid('123', '456');
    }
    function testGetUserByGupidThrowsExceptionWhenGupidNotFound()
    {
        $this->setExpectedException('Exception', 'User profile not found');
        $mockClient = $this->getMock('\personaclient\PersonaClient',array('personaGetUser'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
        $mockClient->expects($this->once())
            ->method('personaGetUser')
            ->will($this->returnValue(false));

        $mockClient->getUserByGupid('123', '456');
    }
    function testGetUserByGupidReturnsUserWhenGupidFound()
    {
        $mockClient = $this->getMock('\personaclient\PersonaClient',array('personaGetUser'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
        $expectedResponse = array(
            '_id' => '123',
            'guid' => '456',
            'gupids' => array('google:789'),
            'created' => array(
                'sec' => 1,
                'u' => 2
            ),
            'profile' => array(
                'email' => 'max@payne.com',
                'name' => 'Max Payne'
            )
        );
        $mockClient->expects($this->once())
            ->method('personaGetUser')
            ->will($this->returnValue($expectedResponse));

        $user = $mockClient->getUserByGupid('123', '456');
        $this->assertEquals('123', $user['_id']);
        $this->assertEquals('456', $user['guid']);
        $this->assertInternalType('array', $user['gupids']);
        $this->assertCount(1, $user['gupids']);
        $this->assertEquals('google:789', $user['gupids'][0]);
        $this->assertInternalType('array', $user['created']);
        $this->assertInternalType('array', $user['profile']);
        $this->assertCount(2, $user['profile']);
        $this->assertEquals('max@payne.com', $user['profile']['email']);
        $this->assertEquals('Max Payne', $user['profile']['name']);
    }

    function testGetUserByGuidsInvalidGuidsThrowsException(){
        $this->setExpectedException('InvalidArgumentException', 'Invalid guids');
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->getUserByGuids('', '');
    }
    function testGetUserByGuidsEmptyTokenThrowsException(){
        $this->setExpectedException('InvalidArgumentException', 'Invalid token');
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->getUserByGuids(array('123'), '');
    }
    function testGetUserByGuidsInvalidTokenThrowsException(){
        $this->setExpectedException('Exception', 'Could not retrieve OAuth response code');
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->getUserByGuids(array('123'), '456');
    }
    function testGetUserByGuidsThrowsExceptionWhenGuidsNotFound()
    {
        $this->setExpectedException('Exception', 'User profiles not found');
        $mockClient = $this->getMock('\personaclient\PersonaClient',array('personaGetUser'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
        $mockClient->expects($this->once())
            ->method('personaGetUser')
            ->will($this->returnValue(false));

        $mockClient->getUserByGuids(array('HK-47'), '456');
    }
    function testGetUserByGuidsReturnsUserWhenGuidsFound()
    {
        $mockClient = $this->getMock('\personaclient\PersonaClient',array('personaGetUser'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
        $expectedResponse = array(array(
            '_id' => '123',
            'guid' => '456',
            'gupids' => array('google:789'),
            'created' => array(
                'sec' => 1,
                'u' => 2
            ),
            'profile' => array(
                'email' => 'max@payne.com',
                'name' => 'Max Payne'
            )
        ));
        $mockClient->expects($this->once())
            ->method('personaGetUser')
            ->will($this->returnValue($expectedResponse));

        $users = $mockClient->getUserByGuids(array('123'), '456');
        $this->assertCount(1, $users);
        $this->assertEquals('123', $users[0]['_id']);
        $this->assertEquals('456', $users[0]['guid']);
        $this->assertInternalType('array', $users[0]['gupids']);
        $this->assertCount(1, $users[0]['gupids']);
        $this->assertEquals('google:789', $users[0]['gupids'][0]);
        $this->assertInternalType('array', $users[0]['created']);
        $this->assertInternalType('array', $users[0]['profile']);
        $this->assertCount(2, $users[0]['profile']);
        $this->assertEquals('max@payne.com', $users[0]['profile']['email']);
        $this->assertEquals('Max Payne', $users[0]['profile']['name']);
    }

    // requireAuth tests
    function testRequireAuthNoProvider()
    {
        $this->setExpectedException('InvalidArgumentException', 'Missing argument 1 for personaclient\PersonaClient::requireAuth()');

        set_error_handler(function ($errno, $errstr, $errfile, $errline)
        {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s %s %s',
                    $errstr,
                    $errfile,
                    $errline
                )
            );
        });

        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->requireAuth();
    }
    function testRequireAuthInvalidProvider()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid provider');

        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->requireAuth(array('test'), 'appid', 'appsecret');
    }
    function testRequireAuthNoAppId()
    {
        $this->setExpectedException('InvalidArgumentException', 'Missing argument 2 for personaclient\PersonaClient::requireAuth()');

        set_error_handler(function ($errno, $errstr, $errfile, $errline)
        {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s %s %s',
                    $errstr,
                    $errfile,
                    $errline
                )
            );
        });

        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->requireAuth('trapdoor');
    }
    function testRequireAuthInvalidAppId()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid appId');

        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->requireAuth('trapdoor', array('appid'), 'appsecret');
    }
    function testRequireAuthNoAppSecret()
    {
        $this->setExpectedException('InvalidArgumentException', 'Missing argument 3 for personaclient\PersonaClient::requireAuth()');

        set_error_handler(function ($errno, $errstr, $errfile, $errline)
        {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s %s %s',
                    $errstr,
                    $errfile,
                    $errline
                )
            );
        });

        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->requireAuth('trapdoor', 'appId');
    }
    function testRequireAuthInvalidAppSecret()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid appSecret');

        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->requireAuth('trapdoor', 'appid', array('appsecret'));
    }
    function testRequireAuthNoRedirectUri()
    {
        $mockClient = $this->getMock('\personaclient\PersonaClient',array('login'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
        $mockClient->expects($this->once())
            ->method('login')
            ->will($this->returnValue(null));

        $mockClient->requireAuth('trapdoor', 'appid', 'appsecret');
        $this->assertEquals('appid', $_SESSION['PERSONA:loginAppId']);
        $this->assertEquals('appsecret', $_SESSION['PERSONA:loginAppSecret']);
        $this->assertEquals('trapdoor', $_SESSION['PERSONA:loginProvider']);
    }
    function testRequireAuthInvalidRedirectUri()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid redirectUri');

        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->requireAuth('trapdoor', 'appid', 'appsecret', array('redirectUri'));
    }
    function testRequireAuthWithRedirectUri()
    {
        $mockClient = $this->getMock('\personaclient\PersonaClient',array('login'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
        $mockClient->expects($this->once())
            ->method('login')
            ->will($this->returnValue(null));

        $mockClient->requireAuth('trapdoor', 'appid', 'appsecret', 'redirecturi');

        $this->assertEquals('appid', $_SESSION['PERSONA:loginAppId']);
        $this->assertEquals('appsecret', $_SESSION['PERSONA:loginAppSecret']);
        $this->assertEquals('trapdoor', $_SESSION['PERSONA:loginProvider']);
    }
    function testRequireAuthAlreadyLoggedIn()
    {
        $mockClient = $this->getMock('\personaclient\PersonaClient',array('isLoggedIn', 'login'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
        $mockClient->expects($this->once())
            ->method('isLoggedIn')
            ->will($this->returnValue(true));
        $mockClient->expects($this->never())
            ->method('login');

        $mockClient->requireAuth('trapdoor', 'appid', 'appsecret');
        $this->assertFalse(isset($_SESSION));
    }
    function testRequireAuthNotAlreadyLoggedIn()
    {
        $mockClient = $this->getMock('\personaclient\PersonaClient',array('isLoggedIn', 'login'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
        $mockClient->expects($this->once())
            ->method('isLoggedIn')
            ->will($this->returnValue(false));
        $mockClient->expects($this->once())
            ->method('login')
            ->will($this->returnValue(true));

        $mockClient->requireAuth('trapdoor', 'appid', 'appsecret', 'redirect');

        $this->assertEquals('appid', $_SESSION['PERSONA:loginAppId']);
        $this->assertEquals('appsecret', $_SESSION['PERSONA:loginAppSecret']);
        $this->assertEquals('trapdoor', $_SESSION['PERSONA:loginProvider']);
    }

    // authenticate tests
    function testAuthenticateNoPayload()
    {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $this->assertFalse($personaClient->authenticate());
    }

    function testAuthenticatePayloadIsAString()
    {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_POST['persona:payload'] = 'YouShallNotPass';
        $this->assertFalse($personaClient->authenticate());
    }
    function testAuthenticatePayloadDoesNotContainState()
    {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION['PERSONA:loginState'] = 'Tennessee';
        $_POST['persona:payload'] = base64_encode(json_encode(array('test' => 'YouShallNotPass')));
        $this->assertFalse($personaClient->authenticate());
    }
    function testAuthenticatePayloadDoesNotContainSignature()
    {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION['PERSONA:loginState'] = 'Tennessee';
        $_POST['persona:payload'] = base64_encode(json_encode(array('state' => 'Tennessee')));
        $this->assertFalse($personaClient->authenticate());
    }
    function testAuthenticatePayloadMismatchingSignature()
    {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION['PERSONA:loginState'] = 'Tennessee';
        $_SESSION['PERSONA:loginAppSecret'] = 'appsecret';
        $payload = array(
            'state' => 'Tennessee'
        );
        $signature = hash_hmac("sha256", json_encode($payload), 'notmyappsecret');
        $payload['signature'] = $signature;

        $_POST['persona:payload'] = base64_encode(json_encode($payload));
        $this->assertFalse($personaClient->authenticate());
    }

    function testAuthenticatePayloadContainsStateAndSignatureNoOtherPayload()
    {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION['PERSONA:loginState'] = 'Tennessee';
        $_SESSION['PERSONA:loginAppSecret'] = 'appsecret';
        $payload = array(
            'state' => 'Tennessee'
        );
        $signature = hash_hmac("sha256", json_encode($payload), 'appsecret');
        $payload['signature'] = $signature;

        $_POST['persona:payload'] = base64_encode(json_encode($payload));
        $this->assertTrue($personaClient->authenticate());

        $this->assertEquals('appsecret', $_SESSION['PERSONA:loginAppSecret']);
        $this->assertArrayHasKey('PERSONA:loginSSO', $_SESSION);
        $this->assertArrayHasKey('token', $_SESSION['PERSONA:loginSSO']);
        $this->assertArrayHasKey('guid', $_SESSION['PERSONA:loginSSO']);
        $this->assertArrayHasKey('gupid', $_SESSION['PERSONA:loginSSO']);
        $this->assertArrayHasKey('profile', $_SESSION['PERSONA:loginSSO']);
        $this->assertArrayHasKey('redirect', $_SESSION['PERSONA:loginSSO']);
    }

    function testAuthenticatePayloadContainsStateAndSignatureFullPayload()
    {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION['PERSONA:loginState'] = 'Tennessee';
        $_SESSION['PERSONA:loginAppSecret'] = 'appsecret';
        $payload = array(
            'token' => array(
                'access_token' => '987',
                'expires_in' => 1800,
                'token_type' => 'bearer',
                'scope' => array(
                    '919191'
                )
            ),
            'guid' => '123',
            'gupid' => array('trapdoor:123'),
            'profile' => array(
                'name' => 'Alex Murphy',
                'email' => 'alexmurphy@detroit.pd'
            ),
            'redirect' => 'http://example.com/wherever',
            'state' => 'Tennessee'
        );
        $signature = hash_hmac("sha256", json_encode($payload), 'appsecret');
        $payload['signature'] = $signature;

        $_POST['persona:payload'] = base64_encode(json_encode($payload));
        $this->assertTrue($personaClient->authenticate());

        $this->assertEquals('appsecret', $_SESSION['PERSONA:loginAppSecret']);
        $this->assertArrayHasKey('PERSONA:loginSSO', $_SESSION);
        $this->assertArrayHasKey('token', $_SESSION['PERSONA:loginSSO']);
        $this->assertEquals('987', $_SESSION['PERSONA:loginSSO']['token']['access_token']);
        $this->assertEquals(1800, $_SESSION['PERSONA:loginSSO']['token']['expires_in']);
        $this->assertEquals('bearer', $_SESSION['PERSONA:loginSSO']['token']['token_type']);
        $this->assertEquals('919191', $_SESSION['PERSONA:loginSSO']['token']['scope'][0]);
        $this->assertEquals('123', $_SESSION['PERSONA:loginSSO']['guid']);
        $this->assertEquals('trapdoor:123', $_SESSION['PERSONA:loginSSO']['gupid'][0]);
        $this->assertArrayHasKey('profile', $_SESSION['PERSONA:loginSSO']);
        $this->assertEquals('Alex Murphy', $_SESSION['PERSONA:loginSSO']['profile']['name']);
        $this->assertEquals('alexmurphy@detroit.pd', $_SESSION['PERSONA:loginSSO']['profile']['email']);
        $this->assertEquals('http://example.com/wherever', $_SESSION['PERSONA:loginSSO']['redirect']);
    }

    // test getPersistentId

    // test getRedirectUrl

    // test isSuperUser



}
