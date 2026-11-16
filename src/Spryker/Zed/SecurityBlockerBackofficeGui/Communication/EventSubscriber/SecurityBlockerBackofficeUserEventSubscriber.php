<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SecurityBlockerBackofficeGui\Communication\EventSubscriber;

use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Spryker\Zed\SecurityBlockerBackofficeGui\Communication\Builder\MessageBuilderInterface;
use Spryker\Zed\SecurityBlockerBackofficeGui\Dependency\Client\SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface;
use Spryker\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class SecurityBlockerBackofficeUserEventSubscriber implements EventSubscriberInterface
{
    /**
     * @uses \Spryker\Zed\SecurityGui\Communication\Form\LoginForm::FORM_NAME
     *
     * @var string
     */
    protected const FORM_NAME = 'auth';

    /**
     * @uses \Spryker\Zed\SecurityGui\Communication\Form\LoginForm::FIELD_USERNAME
     *
     * @var string
     */
    protected const FIELD_USERNAME = 'username';

    /**
     * Runs before the CAPTCHA check of the Captcha module (priority 10), so a blocked login is answered before its CAPTCHA is used.
     *
     * @var int
     */
    protected const KERNEL_REQUEST_SUBSCRIBER_PRIORITY = 11;

    /**
     * @var string
     */
    protected const ROUTE_KEY = '_route';

    /**
     * @var string
     */
    protected const AUTHENTICATION_FAILURE_EVENT = 'onAuthenticationFailure';

    protected RequestStack $requestStack;

    protected SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface $securityBlockerClient;

    protected SecurityBlockerBackofficeGuiConfig $securityBlockerBackofficeGuiConfig;

    protected MessageBuilderInterface $messageBuilder;

    public function __construct(
        RequestStack $requestStack,
        SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface $securityBlockerClient,
        MessageBuilderInterface $messageBuilder,
        SecurityBlockerBackofficeGuiConfig $securityBlockerBackofficeGuiConfig
    ) {
        $this->requestStack = $requestStack;
        $this->securityBlockerClient = $securityBlockerClient;
        $this->messageBuilder = $messageBuilder;
        $this->securityBlockerBackofficeGuiConfig = $securityBlockerBackofficeGuiConfig;
    }

    /**
     * @return array<string, array<int, int|string>|string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => static::AUTHENTICATION_FAILURE_EVENT,
            KernelEvents::REQUEST => ['onKernelRequest', static::KERNEL_REQUEST_SUBSCRIBER_PRIORITY],
        ];
    }

    public function onAuthenticationFailure(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request || !$this->isLoginAttempt($request)) {
            return;
        }

        $securityCheckAuthContextTransfer = $this->createSecurityCheckAuthContextTransfer($request);
        $this->securityBlockerClient->incrementLoginAttemptCount($securityCheckAuthContextTransfer);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isLoginAttempt($request)) {
            return;
        }

        $securityCheckAuthContextTransfer = $this->createSecurityCheckAuthContextTransfer($request);
        $securityCheckAuthResponseTransfer = $this->securityBlockerClient->isAccountBlocked($securityCheckAuthContextTransfer);

        if (!$securityCheckAuthResponseTransfer->getIsBlocked()) {
            return;
        }

        $exceptionMessage = $this->messageBuilder->getExceptionMessage($securityCheckAuthResponseTransfer);

        throw new HttpException(
            Response::HTTP_TOO_MANY_REQUESTS,
            $exceptionMessage,
            null,
            [],
            Response::HTTP_TOO_MANY_REQUESTS,
        );
    }

    protected function isLoginAttempt(Request $request): bool
    {
        $currentRoute = $request->attributes->get(static::ROUTE_KEY);
        $configuredRoute = $this->securityBlockerBackofficeGuiConfig->getBackofficeUserLoginCheckUrl();

        return $currentRoute === $configuredRoute
            && $request->getMethod() === Request::METHOD_POST;
    }

    protected function createSecurityCheckAuthContextTransfer(Request $request): SecurityCheckAuthContextTransfer
    {
        return (new SecurityCheckAuthContextTransfer())
            ->setType($this->securityBlockerBackofficeGuiConfig->getSecurityBlockerBackofficeUserEntityType())
            ->setIp($request->getClientIp())
            ->setLoginIdentifier($this->findSubmittedUsername($request))
            ->setIpCounterKeyStrategy($this->securityBlockerBackofficeGuiConfig->getIpCounterKeyStrategy());
    }

    protected function findSubmittedUsername(Request $request): ?string
    {
        $loginForm = $request->request->all()[static::FORM_NAME] ?? null;
        $username = is_array($loginForm) ? ($loginForm[static::FIELD_USERNAME] ?? null) : null;

        return is_string($username) ? $username : null;
    }
}
