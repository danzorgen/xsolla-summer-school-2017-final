<?php

namespace UrlShortener\Repository;

use UrlShortener\Model\User;


class UserRepository extends AbstractRepository
{
    /**
     * @param $email
     * @return null|User
     */
    public function getUserByEmail($email)
    {
        $userRow = $this->dbConnection->fetchAssoc(
            'SELECT `id`, `name`, `email`, `password` FROM `user` WHERE `email` = ?',
            [$email]
        );

        return $userRow['id'] !== null ?
            new User($userRow['id'], $userRow['name'], $userRow['email'], $userRow['password']) :
            null;
    }


    /**
     * @param User $user
     * @return User
     */
    public function saveUser(User $user)
    {
        if ($user->id !== null) {
            $this->dbConnection->executeQuery(
                'UPDATE `user` SET `name` = ?, `email` = ?, `password` = ? WHERE `id` = ?',
                [$user->name, $user->email, $user->password, $user->id]
            );
        } else {
            $this->dbConnection->executeQuery(
                'INSERT INTO `user` (`name`, `email`, `password`) VALUES (?, ?, ?)',
                [$user->name, $user->email, $user->password]
            );
            $user->id = $this->dbConnection->lastInsertId();
        }

        return $user;
    }
}