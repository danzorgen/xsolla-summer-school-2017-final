<?php

namespace UrlShortener\Service;

use UrlShortener\Model\User;
use UrlShortener\Repository\UserRepository;

class UserService
{
    protected $userRepository;


    /**
     * UserService constructor.
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }


    /**
     * @param $password
     * @return string
     */
    public function createPassword($password)
    {
        $salt = 'xsolla_school_';
        return sha1($salt.$password);
    }


    /**
     * @param $name
     * @param $email
     * @param $password
     * @return User
     */
    public function createUser($name, $email, $password)
    {
        $passwordHash = $this->createPassword($password);

        $user = new User(null, $name, $email, $passwordHash);
        $user = $this->userRepository->saveUser($user);

        return $user;
    }


    /**
     * @param $email
     * @return null|User
     */
    public function getUserByEmail($email)
    {
        return $this->userRepository->getUserByEmail($email);
    }


    /**
     * @param User $user
     * @param $name
     * @param $email
     * @param $password
     * @return User
     */
    public function updateUser(User $user, $name, $email, $password)
    {
        $passwordHash = $this->createPassword($password);

        $user->name = $name;
        $user->email = $email;
        $user->password = $passwordHash;

        $user = $this->userRepository->saveUser($user);

        return $user;
    }
}