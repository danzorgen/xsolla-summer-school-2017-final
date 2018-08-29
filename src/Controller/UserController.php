<?php

namespace UrlShortener\Controller;

use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class UserController extends AbstractController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request)
    {
        // input data validation
        $name = $request->get('name');
        $email = $request->get('email');
        $password = $request->get('password');

        if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$password) {
            return $this->createErrorResponse('Incorrect data');
        }

        // creating new user
        try {
            $user = $this->userService->createUser($name, $email, $password);
        } catch (DBALException $e) {
            return $this->createErrorResponse('Email already exists');
        }

        return new JsonResponse(
            [
                'email' => $user->email,
                'name' => $user->name
            ],
            Response::HTTP_CREATED
        );
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getUser(Request $request)
    {
        $user = $this->getUserByAuthorization($request);

        if ($user === false) {
            return $this->createUnathorizedResponse();
        }

        return new JsonResponse([
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateUser(Request $request)
    {
        // user verification
        $user = $this->getUserByAuthorization($request);

        if ($user === false) {
            return $this->createUnathorizedResponse();
        }

        // input data validation
        $name = $request->get('name');
        $email = $request->get('email');
        $password = $request->get('password');

        if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$password) {
            return $this->createErrorResponse('Incorrect data');
        }

        // updating user data
        try {
            $user = $this->userService->updateUser($user, $name, $email, $password);
        } catch (DBALException $e) {
            return $this->createErrorResponse('email already exists');
        }

        return new JsonResponse(
            [
            'email' => $user->email,
            'name' => $user->name,
            ]
        );
    }
}