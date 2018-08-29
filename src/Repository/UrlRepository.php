<?php

namespace UrlShortener\Repository;

use UrlShortener\Model\Url;


class UrlRepository extends AbstractRepository
{
    /**
     * @param $user_id
     * @return array
     */
    public function getUrlsByUserId($user_id)
    {
        $urlsArray = [];

        $urlRows = $this->dbConnection->executeQuery(
            'SELECT `id`, `user_id`, `full_url`, `hash` FROM `url` WHERE `user_id` = ?',
            [$user_id]
        );

        while ($urlRow = $urlRows->fetch(\PDO::FETCH_ASSOC)) {
            $urlsArray[] = new Url(
                $urlRow['id'],
                $urlRow['user_id'],
                $urlRow['full_url'],
                $urlRow['hash']
            );
        }

        return $urlsArray;
    }


    /**
     * @param $id
     * @return null|Url
     */
    public function getUrlById($id)
    {
        $urlRow = $this->dbConnection->fetchAssoc(
            ' SELECT u.`id`, u.`user_id`, u.`full_url`, u.`hash`, COUNT(uv.`id`) AS visits
              FROM `url` AS u
                LEFT JOIN `url_visit` AS uv ON (uv.`url_id` = u.`id`)
              WHERE u.`id` = ?',
            [$id]
        );

        return $urlRow['full_url'] !== null ?
            new Url($urlRow['id'], $urlRow['user_id'], $urlRow['full_url'], $urlRow['hash'], $urlRow['visits']) :
            null;
    }


    /**
     * @param $hash
     * @return null|Url
     */
    public function getUrlByHash($hash)
    {
        $urlRow = $this->dbConnection->fetchAssoc(
            'SELECT `id`, `user_id`, `full_url`, `hash` FROM `url` WHERE `hash` = ?',
            [$hash]
        );

        return $urlRow['id'] !== null ?
            new Url($urlRow['id'], $urlRow['user_id'], $urlRow['full_url'], $urlRow['hash']) :
            null;
    }


    /**
     * @param Url $url
     * @return Url
     */
    public function saveUrl(Url $url)
    {
        if ($url->id !== null) {
            $this->dbConnection->executeQuery(
                'UPDATE `url` SET `full_url` = ?, `hash` = ? WHERE `id` = ?',
                [$url->full_url, $url->hash, $url->id]
            );
        } else {
            $this->dbConnection->executeQuery(
                'INSERT INTO `url` (`user_id`, `full_url`, `hash`) VALUES (?, ?, ?)',
                [$url->user_id, $url->full_url, $url->hash]
            );
            $url->id = $this->dbConnection->lastInsertId();
        }

        return $url;
    }


    /**
     * @param $id
     * @return bool
     */
    public function deleteUrl($id)
    {
        $this->dbConnection->executeQuery(
            'DELETE FROM `url_visit` WHERE `url_id` = ?',
            [$id]
        );
        
        $this->dbConnection->executeQuery(
            'DELETE FROM `url` WHERE `id` = ?',
            [$id]
        );

        return true;
    }


    /**
     * @param $url_id
     * @param \DateTime $from_date
     * @param \DateTime $to_date
     * @param $period
     * @return array
     */
    public function getVisitsCount($url_id, \DateTime $from_date, \DateTime $to_date, $period)
    {
        switch ($period) {
            case 'min':
                $format = '%Y-%m-%d %H:%i:00';
                break;

            case 'hours':
                $format = '%Y-%m-%d %H:00:00';
                break;

            default:
                $format = '%Y-%m-%d 00:00:00';
                break;
        }

        $datesArray = [];

        $dateRows = $this->dbConnection->executeQuery(
            ' SELECT DATE_FORMAT(`visited_at`, ?) AS date, COUNT(`id`) AS visits
              FROM `url_visit`
              WHERE `url_id` = ? AND `visited_at` >= ? AND `visited_at` < ?
              GROUP BY date
              ORDER BY date ASC',
            [$format, $url_id, $from_date->format('Y-m-d H:i:s'), $to_date->format('Y-m-d H:i:s')]
        );

        while ($dateRow = $dateRows->fetch(\PDO::FETCH_ASSOC)) {
            $datesArray[] = [
                $dateRow['date'] => $dateRow['visits']
            ];
        }

        return $datesArray;
    }


    /**
     * @param $url_id
     * @param $referer
     * @return bool
     */
    public function addUrlVisit($url_id, $referer)
    {
        $this->dbConnection->executeQuery(
            'INSERT INTO `url_visit` (`url_id`, `referer`) VALUES (?, ?)',
            [$url_id, $referer]
        );

        return true;
    }


    /**
     * @param $url_id
     * @return array
     */
    public function getTop20Referers($url_id)
    {
        $referersArray = [];

        $refererRows = $this->dbConnection->executeQuery(
            ' SELECT `referer`, COUNT(`referer`) as count
              FROM `url_visit`
              WHERE `url_id` = ?
              GROUP BY referer
              ORDER BY count DESC',
            [$url_id]
        );

        while ($refererRow = $refererRows->fetch(\PDO::FETCH_ASSOC)) {
            $referersArray[] = [
                $refererRow['referer'] => $refererRow['count']
            ];
        }

        return $referersArray;
    }
}