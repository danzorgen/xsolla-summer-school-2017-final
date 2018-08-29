<?php

namespace UrlShortener\Tests;

use Doctrine\DBAL\Connection;

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;


class UrlTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        /** @var Connection $dbConnection */
        $dbConnection = $this->app['db'];

        // fixture
        $dbConnection->executeQuery(
            ' DELETE FROM `url`
              WHERE `user_id` IN (\'3\', \'4\') OR `id` IN (\'5\', \'6\', \'7\')'
        );

        $dbConnection->executeQuery(
            ' DELETE FROM `user`
              WHERE `id` IN (\'3\', \'4\')'
        );

        $dbConnection->executeQuery(
            ' INSERT INTO `user` (`id`, `name`, `email`, `password`)
              VALUES (\'3\', \'Sergey\', \'test21@yandex.ru\', \'6f70f4ee649214b7c291ef06af18b524b0ab8e69\'),
                     (\'4\', \'Sergey\', \'test22@yandex.ru\', \'6f70f4ee649214b7c291ef06af18b524b0ab8e69\')'
        );

        $dbConnection->executeQuery(
            ' INSERT INTO `url` (`id`, `user_id`, `full_url`, `hash`)
              VALUES (\'5\', \'3\', \'https://yandex.ru\', \'b44a954b950\'),
                     (\'6\', \'4\', \'https://yandex.ru\', \'b44a954b951\'),
                     (\'7\', \'4\', \'https://google.com\', \'b44a954b952\')'
        );
    }


    public function createApplication()
    {
        $app = new \Silex\Application();

        require __DIR__ . '/../src/app.php';
        return $app;
    }


    public function testCreateUrl()
    {
        $client = $this->createClient();

        // test 1: user data not valid
        $client->request('POST', '/api/v1/users/me/shorten_urls', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test21@yandex.ru', 'PHP_AUTH_PW' => '123456'], '{"url":"https://yandex.ru"}');
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());

        // test 2: url parameter is empty
        $client->request('POST', '/api/v1/users/me/shorten_urls', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test21@yandex.ru', 'PHP_AUTH_PW' => '12345'], '{"url":""}');
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);

        // test 3: url parameter is missing
        $client->request('POST', '/api/v1/users/me/shorten_urls', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test21@yandex.ru', 'PHP_AUTH_PW' => '12345'], '{}');
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);

        // test 4: url parameter is correct
        $client->request('POST', '/api/v1/users/me/shorten_urls', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test21@yandex.ru', 'PHP_AUTH_PW' => '12345'], '{"url":"https://yandex.ru"}');
        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('hash', $data);
        $this->assertEquals('https://yandex.ru', $data['url']);
    }


    public function testGetUrls()
    {
        $client = $this->createClient();

        // test 1: user data not valid
        $client->request('GET', '/api/v1/users/me/shorten_urls', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test22@yandex.ru', 'PHP_AUTH_PW' => '123456'], '');
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());

        // test 2: user data is correct
        $client->request('GET', '/api/v1/users/me/shorten_urls', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test22@yandex.ru', 'PHP_AUTH_PW' => '12345'], '');
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([
            ['id' => '6', 'url' => 'https://yandex.ru', 'hash' => 'b44a954b951'],
            ['id' => '7', 'url' => 'https://google.com', 'hash' => 'b44a954b952']
        ], $data);
    }


    public function testGetUrl()
    {
        $client = $this->createClient();

        // test 1: user data not valid
        $client->request('GET', '/api/v1/users/me/shorten_urls/6', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test22@yandex.ru', 'PHP_AUTH_PW' => '123456'], '');
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());

        // test 2: url not exists
        $client->request('GET', '/api/v1/users/me/shorten_urls/3', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test22@yandex.ru', 'PHP_AUTH_PW' => '12345'], '');
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);

        // test 3: url belongs to another user
        $client->request('GET', '/api/v1/users/me/shorten_urls/5', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test22@yandex.ru', 'PHP_AUTH_PW' => '12345'], '');
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);

        // test 4: user data is correct
        $client->request('GET', '/api/v1/users/me/shorten_urls/6', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test22@yandex.ru', 'PHP_AUTH_PW' => '12345'], '');
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['id' => '6', 'url' => 'https://yandex.ru', 'hash' => 'b44a954b951', 'visits' => '0'], $data);
    }


    public function testDeleteUrl()
    {
        $client = $this->createClient();

        // test 1: user data not valid
        $client->request('DELETE', '/api/v1/users/me/shorten_urls/7', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test21@yandex.ru', 'PHP_AUTH_PW' => '123456'], '');
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());

        // test 2: url not exists
        $client->request('DELETE', '/api/v1/users/me/shorten_urls/3', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test22@yandex.ru', 'PHP_AUTH_PW' => '12345'], '');
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);

        // test 3: url belongs to another user
        $client->request('DELETE', '/api/v1/users/me/shorten_urls/5', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test22@yandex.ru', 'PHP_AUTH_PW' => '12345'], '');
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);

        // test 4: user data is correct
        $client->request('DELETE', '/api/v1/users/me/shorten_urls/7', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test22@yandex.ru', 'PHP_AUTH_PW' => '12345'], '');
        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

        // test 5: url not exists
        $client->request('DELETE', '/api/v1/users/me/shorten_urls/7', [], [], ['CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'test22@yandex.ru', 'PHP_AUTH_PW' => '12345'], '');
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }
}