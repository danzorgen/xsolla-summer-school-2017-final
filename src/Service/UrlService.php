<?php

namespace UrlShortener\Service;

use UrlShortener\Model\Url;
use UrlShortener\Repository\UrlRepository;


class UrlService
{
    protected $urlRepository;


    public function __construct(UrlRepository $urlRepository)
    {
        $this->urlRepository = $urlRepository;
    }


    /**
     * @param $full_url
     * @return string
     */
    public function generateUrlHash($full_url)
    {
        $length = 7;

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);

        do {
            $hash = '';

            for ($i = 0; $i < $length; $i++) {
                $hash .= $characters [rand(0, $charactersLength - 1)];
            }
        } while ($this->getUrlByHash($hash));

        return $hash;
    }


    /**
     * @param $user_id
     * @param $full_url
     * @return Url
     */
    public function createUrl($user_id, $full_url)
    {
        $hash = $this->generateUrlHash($full_url);

        $url = new Url(null, $user_id, $full_url, $hash);
        $url = $this->urlRepository->saveUrl($url);

        return $url;
    }


    /**
     * @param $user_id
     * @return array
     */
    public function getUrlsByUserId($user_id)
    {
        return $this->urlRepository->getUrlsByUserId($user_id);
    }


    /**
     * @param $id
     * @return null|Url
     */
    public function getUrlById($id)
    {
        return $this->urlRepository->getUrlById($id);
    }


    /**
     * @param $hash
     * @return null|Url
     */
    public function getUrlByHash($hash)
    {
        return $this->urlRepository->getUrlByHash($hash);
    }


    /**
     * @param $id
     * @return bool
     */
    public function deleteUrl($id)
    {
        return $this->urlRepository->deleteUrl($id);
    }


    /**
     * @param $date
     * @param string $format
     * @return bool
     */
    public function checkDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }


    /**
     * @param $url_id
     * @param $from_date
     * @param $to_date
     * @param $period
     * @return array
     */
    public function getVisitsCount($url_id, $from_date, $to_date, $period)
    {
        // dates validation
        // default period - 7 days
        $from_date_is_correct = $this->checkDate($from_date);
        $to_date_is_correct = $this->checkDate($to_date);

        if ($from_date_is_correct) {
            $from_date = new \DateTime($from_date);

            if ($to_date_is_correct) {
                $to_date = new \DateTime($to_date);
            } else {
                $to_date = clone $from_date;
                $to_date->modify('+ 7 days');
            }

        } else {
            if ($to_date_is_correct) {
                $to_date = new \DateTime($to_date);
            } else {
                $to_date = new \DateTime('midnight today');
            }

            $from_date = clone $to_date;
            $from_date->modify('- 7 days');
        }

        $to_date->modify('+ 1 day');

        return $this->urlRepository->getVisitsCount($url_id, $from_date, $to_date, $period);
    }


    /**
     * @param $url_id
     * @param $referer
     * @return bool
     */
    public function addUrlVisit($url_id, $referer)
    {
        return $this->urlRepository->addUrlVisit($url_id, $referer);
    }


    /**
     * @param $url_id
     * @return array
     */
    public function getTop20Referers($url_id)
    {
        return $this->urlRepository->getTop20Referers($url_id);
    }
}