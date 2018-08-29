<?php

namespace UrlShortener\Controller;

use UrlShortener\Model\Url;
use UrlShortener\Service\UserService;
use UrlShortener\Service\UrlService;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class UrlController extends AbstractController
{
    protected $urlService;


    /**
     * UrlController constructor.
     * @param UserService $userService
     * @param UrlService $urlService
     */
    public function __construct(UserService $userService, UrlService $urlService)
    {
        parent::__construct($userService);
        $this->urlService = $urlService;
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createUrl(Request $request)
    {
        // user verification
        $user = $this->getUserByAuthorization($request);

        if ($user === false) {
            return $this->createUnathorizedResponse();
        }

        // input data validation
        $full_url = $request->get('url');

        if (!$full_url) {
            return $this->createErrorResponse('Incorrect data');
        }

        // creating new url
        try {
            $url = $this->urlService->createUrl($user->id, $full_url);
        } catch (\Exception $e) {
            return $this->createErrorResponse($e->getMessage());
        }

        return new JsonResponse(
            [
                'id' => $url->id,
                'url' => $url->full_url,
                'hash' => $url->hash
            ],
            Response::HTTP_CREATED
        );
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getUrls(Request $request)
    {
        // user verification
        $user = $this->getUserByAuthorization($request);

        if ($user === false) {
            return $this->createUnathorizedResponse();
        }

        // get urls data
        $urlsArray = $this->urlService->getUrlsByUserId($user->id);

        // creating json response
        $jsonResponse = [];

        /** @var Url $url */
        foreach ($urlsArray as $url) {
            $jsonResponse[] = ['id' => $url->id, 'url' => $url->full_url, 'hash' => $url->hash];
        }

        return new JsonResponse(
            $jsonResponse
        );
    }


    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function getUrl(Request $request, $id)
    {
        // user verification
        $user = $this->getUserByAuthorization($request);

        if ($user === false) {
            return $this->createUnathorizedResponse();
        }

        // get url data
        $url = $this->urlService->getUrlById($id);

        if (!$url || $url->user_id != $user->id) {
            return $this->createErrorResponse('Url has not been found.');
        }

        return new JsonResponse(
            [
                'id' => $url->id,
                'url' => $url->full_url,
                'hash' => $url->hash,
                'visits' => $url->visits
            ]
        );
    }


    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function deleteUrl(Request $request, $id)
    {
        // user verification
        $user = $this->getUserByAuthorization($request);

        if ($user === false) {
            return $this->createUnathorizedResponse();
        }

        // get url data
        $url = $this->urlService->getUrlById($id);

        if (!$url || $url->user_id != $user->id) {
            return $this->createErrorResponse('Url has not been found.');
        }

        // delete url
        $this->urlService->deleteUrl($id);

        return new JsonResponse(
            [],
            Response::HTTP_NO_CONTENT
        );
    }


    /**
     * @param Request $request
     * @param $id
     * @param $period
     * @return JsonResponse
     */
    public function getVisitsCount(Request $request, $id, $period)
    {
        // user verification
        $user = $this->getUserByAuthorization($request);

        if ($user === false) {
            return $this->createUnathorizedResponse();
        }

        // get url data
        $url = $this->urlService->getUrlById($id);

        if (!$url || $url->user_id != $user->id) {
            return $this->createErrorResponse('Url has not been found.');
        }

        // get visits data
        $from_date = $request->get('from_date');
        $to_date = $request->get('to_date');

        $datesArray = $this->urlService->getVisitsCount($url->id, $from_date, $to_date, $period);

        return new JsonResponse([
            $datesArray
        ]);
    }


    /**
     * @param Request $request
     * @param $hash
     * @return JsonResponse|RedirectResponse
     */
    public function redirectToUrl(Request $request, $hash)
    {
        // input data validation
        if (!$hash) {
            return $this->createErrorResponse('Incorrect hash');
        }

        // get url data
        $url = $this->urlService->getUrlByHash($hash);

        if (!$url) {
            return $this->createErrorResponse('Url has not been found.');
        }

        // get referer
        $referer = parse_url($request->headers->get('Referer'), PHP_URL_HOST);

        if (!$referer) {
            $referer = 'unknown';
        }

        // add url visit
        try {
            $this->urlService->addUrlVisit($url->id, $referer);
        } catch (\Exception $e) {
            return $this->createErrorResponse($e->getMessage());
        }

        return new RedirectResponse(
            $url->full_url
        );
    }


    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function getTop20Referers(Request $request, $id)
    {
        // user verification
        $user = $this->getUserByAuthorization($request);

        if ($user === false) {
            return $this->createUnathorizedResponse();
        }

        // get url data
        $url = $this->urlService->getUrlById($id);

        if (!$url || $url->user_id != $user->id) {
            return $this->createErrorResponse('Url has not been found.');
        }

        // get referers top
        $referersArray = $this->urlService->getTop20Referers($url->id);

        return new JsonResponse([
            $referersArray
        ]);
    }
}