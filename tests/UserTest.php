<?php

namespace UrlShortener\Tests;

use Doctrine\DBAL\Connection;

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;


class UserTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        /** @var Connection $dbConnection */
        $dbConnection = $this->app['db'];

        // fixture
        $dbConnection->executeQuery(
            ' DELETE FROM `user`
              WHERE `id` IN (\'1\', \'2\') OR `email` IN (\'test11@yandex.ru\', \'test12@yandex.ru\', \'test13@yandex.ru\', \'test14@yandex.ru\')'
        );

        $dbConnection->executeQuery(
            ' INSERT INTO `user` (`id`, `name`, `email`, `password`)
              VALUES (\'1\', \'Sergey\', \'test12@yandex.ru\', \'6f70f4ee649214b7c291ef06af18b524b0ab8e69\'),
                     (\'2\', \'Sergey\', \'test13@yandex.ru\', \'6f70f4ee649214b7c291ef06af18b524b0ab8e69\')'
        );
    }


    public function createApplication()
    {
        $app = new \Silex\Application();

        require __DIR__ . '/../src/app.php';
        return $app;
    }


    public function testRegister()
    {
        $client = $this->createClient();

        // test 1: user email is empty
        $client->request('POST', '/api/v1/users', [], [], ['CONTENT_TYPE' => 'application/json'], '{"email":"", "name":"Sergey", "password":"12345"}');
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);

        // test 2: user email is missing
        $client->request('POST', '/api/v1/users', [], [], ['CONTENT_TYPE' => 'application/json'], '{"name":"Sergey", "password":"12345"}');
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);

        // test 3: user password is empty
        $client->request('POST', '/api/v1/users', [], [], ['CONTENT_TYPE' => 'application/json'], '{"email":"test11@yandex.ru", "name":"Sergey", "password":""}');
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);

        // test 4: user password is missing
        $client->request('POST', '/api/v1/users', [], [], ['CONTENT_TYPE' => 'application/json'], '{"email":"test11@yandex.ru", "name":"Sergey""}');
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);

        // test 5: user data is correct
        $client->request('POST', '/api/v1/users', [], [], ['CONTENT_TYPE' => 'application/json'], '{"email":"test11@yandex.ru", "name":"Sergey", "password":"12345"}');
        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['email' => 'test11@yandex.ru', 'name' => 'Sergey'], $data);

        // test 6: user email already exists
        $client->request('POST', '/api/v1/users', [], [], ['CONTENT_TYPE' => 'application/json'], '{"email":"test11@yandex.ru", "name":"Sergey", "password":"12345"}');
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }


    public function testGetUser()
    {
        $client = $this->createClient();

        // test 1: password is incorrect
        $client->request('GET', '/api/v1/users/me', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test12@yandex.ru', 'PHP_AUTH_PW' => '1234']);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);

        // test 2: email is not exists
        $client->request('GET', '/api/v1/users/me', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => '12test@yandex.ru', 'PHP_AUTH_PW' => '12345']);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);

        // test 3: user data is correct
        $client->request('GET', '/api/v1/users/me', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test12@yandex.ru', 'PHP_AUTH_PW' => '12345']);
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['email' => 'test12@yandex.ru', 'name' => 'Sergey'], $data);
    }


    public function testUpdateUser()
    {
        $client = $this->createClient();

        // test 1: user data is correct
        $client->request('PUT', '/api/v1/users/me', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test13@yandex.ru', 'PHP_AUTH_PW' => '12345'], '{"email":"test14@yandex.ru", "name":"Sergei", "password":"54321"}');
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['email' => 'test14@yandex.ru', 'name' => 'Sergei'], $data);

        // test 2: user auth data is outdated
        $client->request('GET', '/api/v1/users/me', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test13@yandex.ru', 'PHP_AUTH_PW' => '12345']);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);

        // test 3: user auth data is correct
        $client->request('GET', '/api/v1/users/me', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test14@yandex.ru', 'PHP_AUTH_PW' => '54321']);
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['email' => 'test14@yandex.ru', 'name' => 'Sergei'], $data);
    }
}