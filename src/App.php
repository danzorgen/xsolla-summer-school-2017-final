<?php

use UrlShortener\Controller\UserController;
use UrlShortener\Service\UserService;
use UrlShortener\Repository\UserRepository;

use UrlShortener\Controller\UrlController;
use UrlShortener\Service\UrlService;
use UrlShortener\Repository\UrlRepository;

use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Symfony\Component\HttpFoundation\Request;


$app->register(new DoctrineServiceProvider(), [
    'db.options' => [
        'driver' => 'pdo_mysql',
        'host' => '127.0.0.1',
        'dbname' => 'url_shortener',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8'
    ]
]);




$app->register(new ServiceControllerServiceProvider());


$app->before(function(Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});


// users services

$app['users.controller'] = function ($app) {
    return new UserController($app['users.service']);
};
$app['users.service'] = function ($app) {
    return new UserService($app['users.repository']);
};
$app['users.repository'] = function ($app) {
    return new UserRepository($app['db']);
};


// urls services

$app['urls.controller'] = function ($app) {
    return new UrlController($app['users.service'], $app['urls.service']);
};
$app['urls.service'] = function ($app) {
    return new UrlService($app['urls.repository']);
};
$app['urls.repository'] = function ($app) {
    return new UrlRepository($app['db']);
};


// routes for users

$users = $app['controllers_factory'];

$users->post('/users', 'users.controller:register');
$users->get('/users/me', 'users.controller:getUser');
$users->put('/users/me', 'users.controller:updateUser');

$app->mount('/api/v1', $users);


// routes for urls

$urls = $app['controllers_factory'];

$urls->post('/users/me/shorten_urls', 'urls.controller:createUrl');
$urls->get('/users/me/shorten_urls', 'urls.controller:getUrls');
$urls->get('/users/me/shorten_urls/{id}', 'urls.controller:getUrl');
$urls->delete('/users/me/shorten_urls/{id}', 'urls.controller:deleteUrl');

$urls
    ->get('/users/me/shorten_urls/{id}/{period}', 'urls.controller:getVisitsCount')
    ->assert('period', '^(days|hours|min)$');

$urls->get('/users/me/shorten_urls/{id}/referers', 'urls.controller:getTop20Referers');

$urls->get('/shorten_urls/{hash}', 'urls.controller:redirectToUrl');

$app->mount('/api/v1', $urls);
