<?php

namespace UrlShortener\Controller;

use UrlShortener\Model\User;
use UrlShortener\Service\UserService;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


abstract class AbstractController
{
    protected $userService;


    /**
     * AbstractController constructor.
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }


    /**
     * @param Request $request
     * @return User|bool
     */
    protected function getUserByAuthorization(Request $request)
    {
        $email = $request->server->get('PHP_AUTH_USER');
        $password = $request->server->get('PHP_AUTH_PW');

        $user = $this->userService->getUserByEmail($email);
        $passwordHash = $this->userService->createPassword($password);

        return ($user !== null && $user->password === $passwordHash) ? $user : false;
    }


    /**
     * @return JsonResponse
     */
    protected function createUnathorizedResponse()
    {
        return new JsonResponse(
            ['error' => 'not authorized'],
            Response::HTTP_UNAUTHORIZED,
            [
                'WWW-Authenticate' => 'Basic realm="Finance API"'
            ]
        );
    }


    /**
     * @param $message
     * @return JsonResponse
     */
    protected function createErrorResponse($message)
    {
        return new JsonResponse(
            ['error' => $message],
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}