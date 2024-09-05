<?php

namespace App\Component;

use App\Entity\Account;
use App\Entity\OAuth\AccessToken;
use App\Entity\OAuth\AuthCode;
use App\Entity\OAuth\Client;
use App\Entity\OAuth\RefreshToken;
use App\Form\AuthorizeFormType;
use App\Form\Model\Authorize;
use App\Service\Logger\GelfLogger;
use App\Service\Logger\Messages\LogMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;
use Twig_Environment;


class OAuth2
{
    const DEFAULT_ACCESS_TOKEN_LIFETIME = 2592000;
    const DEFAULT_REFRESH_TOKEN_LIFETIME = 1209600;
    const DEFAULT_AUTH_CODE_LIFETIME = 30;

    const TOKEN_TYPE_BEARER = 'bearer';

    const GRANT_TYPE_AUTH_CODE = 'authorization_code';
    const GRANT_TYPE_IMPLICIT = 'token';
    const GRANT_TYPE_USER_CREDENTIALS = 'password';
    const GRANT_TYPE_CLIENT_CREDENTIALS = 'client_credentials';
    const GRANT_TYPE_REFRESH_TOKEN = 'refresh_token';
    const GRANT_TYPE_EXTENSIONS = 'extensions';

    public const TRANSPORT_QUERY = 'query';
    public const TRANSPORT_FRAGMENT = 'fragment';

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var Twig_Environment
     */
    protected $templatingEngine;

    /**
     * @var GelfLogger
     */
    protected  $logger;

    /**
     * @var ParameterBagInterface
     */
    protected $params;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * OAuth2 constructor.
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenStorage
     * @param FormFactoryInterface $formFactory
     * @param Twig_Environment $templatingEngine
     * @param GelfLogger $logger
     * @param ParameterBagInterface $params
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param RouterInterface $router
     */
    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        FormFactoryInterface $formFactory,
        Twig_Environment $templatingEngine,
        GelfLogger $logger,
        ParameterBagInterface $params,
        AuthorizationCheckerInterface $authorizationChecker,
        RouterInterface $router
    )
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->formFactory = $formFactory;
        $this->templatingEngine = $templatingEngine;
        $this->logger = $logger;
        $this->params = $params;
        $this->authorizationChecker = $authorizationChecker;
        $this->router = $router;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function authorizeClient(Request $request)
    {
        $clientId = $request->get('client_id');
        $redirectUri = $request->get('redirect_uri');

        if (!$redirectUri) {
            throw new BadRequestHttpException("Parameter 'redirect_uri' is missing");
        }

        if (!$clientId) {
            throw new BadRequestHttpException("Parameter 'client_id' is missing");
        }

        /** @var Client $client */
        $client = $this->getClient($clientId);

        if (!$client) {
            throw new NotFoundHttpException('Client not found');
        }

        $account = $this->tokenStorage->getToken()->getUser();
        $authorize = new Authorize($request->query->all());
        $form = $this->formFactory->create(AuthorizeFormType::class, $authorize);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $code = $this->createAuthCode($authorize, $account);
            $params[self::TRANSPORT_QUERY]["code"] = $code->getToken();

            return $this->createRedirectUriCallbackResponse($authorize->getRedirectUri(), $params);
        }

        $content = $this->templatingEngine->render(
            'oauth/authorize/authorize.html.twig',
            [
                'form' => $form->createView(),
                'redirectUri' => $redirectUri
            ]
        );

        return new Response($content);
    }

    /**
     * @param $redirectUri
     * @param $params
     * @return Response
     */
    private function createRedirectUriCallbackResponse($redirectUri, $params)
    {
        return new Response('', 302, array(
            'Location' => $this->buildUri($redirectUri, $params),
        ));
    }

    /**
     * @param $uri
     * @param $params
     * @return string
     */
    private function buildUri($uri, $params)
    {
        $parse_url = parse_url($uri);

        // Add our params to the parsed uri
        foreach ($params as $k => $v) {
            if (isset($parse_url[$k])) {
                $parse_url[$k] .= "&" . http_build_query($v);
            } else {
                $parse_url[$k] = http_build_query($v);
            }
        }

        // Put humpty dumpty back together
        return
            ((isset($parse_url["scheme"])) ? $parse_url["scheme"] . "://" : "")
            . ((isset($parse_url["user"])) ? $parse_url["user"] . ((isset($parse_url["pass"])) ? ":" . $parse_url["pass"] : "") . "@" : "")
            . ((isset($parse_url["host"])) ? $parse_url["host"] : "")
            . ((isset($parse_url["port"])) ? ":" . $parse_url["port"] : "")
            . ((isset($parse_url["path"])) ? $parse_url["path"] : "")
            . ((isset($parse_url["query"])) ? "?" . $parse_url["query"] : "")
            . ((isset($parse_url["fragment"])) ? "#" . $parse_url["fragment"] : "");
    }

    /**
     * @return string
     */
    protected function genAccessToken()
    {
        if (@file_exists('/dev/urandom')) {
            $randomData = file_get_contents('/dev/urandom', false, null, 0, 100);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes(100, $strong);
            if (true === $strong && false !== $bytes) {
                $randomData = $bytes;
            }
        }

        if (empty($randomData)) {
            $randomData = mt_rand() . mt_rand() . mt_rand() . uniqid(mt_rand(), true) . microtime(true) . uniqid(
                    mt_rand(),
                    true
                );
        }

        return rtrim(strtr(base64_encode(hash('sha256', $randomData)), '+/', '-_'), '=');
    }

    /**
     * @return string
     */
    protected function genAuthCode()
    {
        return $this->genAccessToken();
    }

    /**
     * @param Authorize $authorize
     * @param Account $account
     * @return AuthCode
     */
    public function createAuthCode(Authorize $authorize, Account $account)
    {
        $code = $this->genAuthCode();
        /** @var Client $client */
        $client = $this->getClient($authorize->getClientId());

        $authCode = new AuthCode();
        $authCode->setToken($code);
        $authCode->setClient($client);
        $authCode->setAccount($account);
        $authCode->setRedirectUri($authorize->getRedirectUri());
        $authCode->setExpiresAt(time() + self::DEFAULT_AUTH_CODE_LIFETIME);
        $authCode->setFacility($authorize->getFacility());
        $this->em->persist($authCode);
        $this->em->flush();

        $msg = sprintf('Facility #%s %s was authorized for Client %s', $authorize->getFacility()->getId(), $authorize->getFacility()->getName(), $client->getId());
        $this->logger->info(new LogMessage($msg, GelfLogger::INTERFACE_ABANINJA));

        return $authCode;
    }

    /**
     * @param string $clientId
     * @return null|object
     */
    public function getClient(string $clientId)
    {
        $client = $this->em->getRepository(Client::class)->findOneBy(['randomId' => $clientId]);

        if (!$client) {
            throw new NotFoundHttpException('Client not found.');
        }

        return $client;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function grantAccessToken(Request $request)
    {
        $grantType = $request->get('grant_type');
        $clientId = $request->get('client_id');
        $clientSecret = $request->get('client_secret');
        $redirectUri = $request->get('redirect_uri');

        if (!$grantType) {
            throw new BadRequestHttpException('Invalid grant_type parameter or parameter missing');
        }

        if (!$redirectUri) {
            throw new BadRequestHttpException("Parameter 'redirect_uri' is missing");
        }

        if (!$clientId) {
            throw new BadRequestHttpException("Parameter 'client_id' is missing");
        }

        if (!$clientSecret) {
            throw new BadRequestHttpException("Parameter 'client_secret' is missing");
        }

        /** @var Client $client */
        $client = $this->getClient($clientId);

        if (!$client) {
            throw new NotFoundHttpException('Client not found');
        }

        if ($clientSecret !== $client->getSecret()) {
            throw new BadRequestHttpException('The client credentials are invalid');
        }

        if (!in_array($grantType, $client->getAllowedGrantTypes(), true)) {
            throw new BadRequestHttpException('The grant type is unauthorized for this client_id');
        }

        $validator = Validation::createValidator();
        $violations = $validator->validate($redirectUri, new Url());
        if (count($violations) > 0) {
            throw new BadRequestHttpException('redirect_uri format is not correct url format');
        }

        $token = [];

        switch ($grantType) {
            case self::GRANT_TYPE_AUTH_CODE:
                $token = $this->grantAccessTokenAuthCode($client, $request);
                break;
            case self::GRANT_TYPE_REFRESH_TOKEN:
                $token = $this->grantAccessTokenRefreshToken($client, $request);
                break;
        }

        return new Response(json_encode($token), 200);
    }

    /**
     * @param Client $client
     * @param Request $request
     * @return RedirectResponse|array
     */
    protected function grantAccessTokenAuthCode(Client $client,Request $request)
    {
        $code = $request->get('code');

        if (!$code) {
            throw new BadRequestHttpException("Parameter 'code' is missing");
        }

        /** @var AuthCode $authCode */
        $authCode = $this->em->getRepository(AuthCode::class)->findOneBy(['token' => $code]);

        if (!$authCode) {
            throw new NotFoundHttpException('Auth code not found');
        }

        if ($authCode->hasExpired()) {
            $msg = sprintf('The authorization code for facility #%s %s has expired', $authCode->getFacility()->getId(), $authCode->getFacility()->getName());
            $this->logger->info(new LogMessage($msg, GelfLogger::INTERFACE_ABANINJA));
            throw new BadRequestHttpException($msg);
        }

        $accessToken = $this->createAccessToken($client, $authCode->getAccount(), $authCode);
        $refreshToken = $this->createRefreshToken($accessToken, $client, $authCode->getAccount(), $authCode);

        $token = [
            'access_token' => $accessToken->getToken(),
            'refresh_token' => $refreshToken->getToken(),
            'expires_in' => $accessToken->getExpiresAt(),
            'token_type' => self::TOKEN_TYPE_BEARER,
        ];

        $this->em->persist($accessToken);
        $this->em->persist($refreshToken);
        $this->em->remove($authCode);
        $this->em->flush();

        return $token;
    }

    /**
     * @param Client $client
     * @param Request $request
     * @return array
     */
    protected function grantAccessTokenRefreshToken(Client $client, Request $request)
    {
        $oldRefreshToken = $request->get('refresh_token');

        if (!$oldRefreshToken) {
            throw new BadRequestHttpException("Parameter 'refresh_token' is missing");
        }

        /** @var RefreshToken $oldRefreshToken */
        $oldRefreshToken = $this->em->getRepository(RefreshToken::class)->findOneBy(['token' => $oldRefreshToken]);

        if (!$oldRefreshToken) {
            throw new NotFoundHttpException('Refresh token not found');
        }

        if ($oldRefreshToken->hasExpired()) {
            throw new BadRequestHttpException(sprintf('The refresh token for facility #%s %s has expired', $oldRefreshToken->getFacility()->getId(), $oldRefreshToken->getFacility()->getName()));
        }

        $accessToken = $this->createAccessToken($client, $oldRefreshToken->getAccount(), null, $oldRefreshToken);
        $refreshToken = $this->createRefreshToken($accessToken,  $client, $oldRefreshToken->getAccount(), null, $oldRefreshToken);

        $token = [
            'access_token' => $accessToken->getToken(),
            'refresh_token' => $refreshToken->getToken(),
            'expires_in' => $accessToken->getExpiresAt(),
            'token_type' => self::TOKEN_TYPE_BEARER,
        ];

        $this->em->persist($accessToken);
        $this->em->persist($refreshToken);
        $this->em->remove($oldRefreshToken);
        $this->em->flush();

        return $token;
    }

    /**
     * @param Client $client
     * @param Account $account
     * @param AuthCode|null $authCode
     * @param RefreshToken|null $refreshToken
     * @return AccessToken
     */
    public function createAccessToken(Client $client, Account $account, AuthCode $authCode = null, RefreshToken $refreshToken = null)
    {
        $accessToken = new AccessToken();
        $accessToken->setToken($this->genAccessToken());
        $accessToken->setClient($client);
        $accessToken->setExpiresAt(time() + $this->params->get('access_token_lifetime'));
        $accessToken->setAccount($account);
        if ($authCode) {
            $accessToken->setFacility($authCode->getFacility());
        }

        if ($refreshToken) {
            $accessToken->setFacility($refreshToken->getFacility());
        }

        $this->logger->info(new LogMessage(
                sprintf('Token was granted for facility #%s %s', $accessToken->getFacility()->getId(), $accessToken->getFacility()->getName()),
                GelfLogger::INTERFACE_ABANINJA
        ));

        return $accessToken;
    }

    /**
     * @param AccessToken $accessToken
     * @param Client $client
     * @param Account $account
     * @param AuthCode|null $authCode
     * @param RefreshToken|null $oldRefreshToken
     * @return RefreshToken
     */
    public function createRefreshToken(AccessToken $accessToken, Client $client, Account $account, AuthCode $authCode = null, RefreshToken $oldRefreshToken = null)
    {
        $refreshToken = new RefreshToken();
        $refreshToken->setAccessToken($accessToken);
        $refreshToken->setToken($this->genAccessToken());
        $refreshToken->setClient($client);
        $refreshToken->setExpiresAt(time() + self::DEFAULT_REFRESH_TOKEN_LIFETIME);
        $refreshToken->setAccount($account);

        if ($authCode) {
            $refreshToken->setFacility($authCode->getFacility());
        }

        if ($oldRefreshToken) {
            $refreshToken->setFacility($oldRefreshToken->getFacility());
        }

        $this->logger->info(new LogMessage(
            sprintf('Token was refreshed for facility #%s %s', $refreshToken->getFacility()->getId(), $refreshToken->getFacility()->getName()),
            GelfLogger::INTERFACE_ABANINJA
        ));

        return $refreshToken;
    }
}
