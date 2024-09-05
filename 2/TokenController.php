<?php

namespace App\Controller;

use App\Component\OAuth2;
use App\Service\Logger\GelfLogger;
use App\Service\Logger\Messages\ExceptionLogMessage;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class TokenController
{
    /**
     * @Route("/oauth/v2/token", name="oauth_token")
     * @param Request $request
     * @param OAuth2 $OAuth2
     * @param GelfLogger $logger
     * @return Response
     * @throws Exception
     */
    public function tokenAction(Request $request, OAuth2 $OAuth2, GelfLogger $logger)
    {
        try {
            return $OAuth2->grantAccessToken($request);
        } catch (Exception $e) {
            $loggerMessage = new ExceptionLogMessage($e);
            $loggerMessage->setMessage('OAuth Token action failed');
            $loggerMessage->setType(GelfLogger::INTERFACE_ABANINJA);
            $logger->error($loggerMessage);
            throw $e;
        }
    }
}
