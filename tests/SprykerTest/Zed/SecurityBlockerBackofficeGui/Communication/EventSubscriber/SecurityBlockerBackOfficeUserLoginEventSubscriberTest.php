<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\SecurityBlockerBackofficeGui\Communication\EventSubscriber;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;
use Spryker\Zed\SecurityBlockerBackofficeGui\Communication\Builder\MessageBuilder;
use Spryker\Zed\SecurityBlockerBackofficeGui\Communication\Builder\SecurityCheckAuthContextBuilder;
use Spryker\Zed\SecurityBlockerBackofficeGui\Communication\EventSubscriber\SecurityBlockerBackofficeUserLoginEventSubscriber;
use Spryker\Zed\SecurityBlockerBackofficeGui\Dependency\Client\SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface;
use Spryker\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiConfig;
use SprykerTest\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiCommunicationTester;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group SecurityBlockerBackofficeGui
 * @group Communication
 * @group EventSubscriber
 * @group SecurityBlockerBackOfficeUserLoginEventSubscriberTest
 * Add your own group annotations below this line
 */
class SecurityBlockerBackOfficeUserLoginEventSubscriberTest extends Unit
{
    protected const string ROUTE_OTHER = 'dashboard';

    /**
     * @uses \Spryker\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiConfig::SECURITY_BLOCKER_BACK_OFFICE_USER_ENTITY_TYPE
     *
     * @var string
     */
    protected const BACK_OFFICE_USER_TYPE = 'back-office-user';

    /**
     * @uses \Spryker\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiConfig::BACK_OFFICE_LOGIN_CHECK_URL
     *
     * @var string
     */
    protected const LOGIN_CHECK_URL = '/login_check';

    /**
     * @uses \Spryker\Zed\Captcha\Communication\EventSubscriber\LoginCaptchaEventSubscriber::KERNEL_REQUEST_SUBSCRIBER_PRIORITY
     */
    protected const int CAPTCHA_SUBSCRIBER_PRIORITY = 10;

    protected const string CAPTCHA_REJECTION_URL = '/captcha-rejected';

    protected SecurityBlockerBackofficeGuiCommunicationTester $tester;

    protected SecurityBlockerBackofficeUserLoginEventSubscriber $subscriber;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\SecurityBlockerBackofficeGui\Dependency\Client\SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface
     */
    protected SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface $securityBlockerClientMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\HttpFoundation\RequestStack
     */
    protected RequestStack $requestStackMock;

    public function testBlockedLoginAttemptIsRejectedBeforeItsCaptchaIsVerified(): void
    {
        // Arrange
        $securityCheckAuthContextTransfer = $this->tester->createLoginSecurityCheckAuthContextTransfer(static::BACK_OFFICE_USER_TYPE);
        $this->securityBlockerClientMock->method('checkLoginAttempt')->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(true)->setBlockedFor(360));
        $eventDispatcher = $this->createEventDispatcherWithCaptchaListener($this->createFailingCaptchaListener());

        // Assert
        $this->expectException(HttpException::class);

        // Act
        $eventDispatcher->dispatch($this->createRequestEventForMethod($securityCheckAuthContextTransfer, Request::METHOD_POST), KernelEvents::REQUEST);
    }

    public function testLoginAttemptRejectedByTheCaptchaIsNotReserved(): void
    {
        // Arrange
        $securityCheckAuthContextTransfer = $this->tester->createLoginSecurityCheckAuthContextTransfer(static::BACK_OFFICE_USER_TYPE);
        $this->securityBlockerClientMock->method('checkLoginAttempt')->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(false));
        $event = $this->createRequestEventForMethod($securityCheckAuthContextTransfer, Request::METHOD_POST);

        // Assert
        $this->securityBlockerClientMock->expects($this->never())->method('reserveLoginAttempt');

        // Act
        $this->createEventDispatcherWithCaptchaListener($this->createRejectingCaptchaListener())->dispatch($event, KernelEvents::REQUEST);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestStackMock = $this->createRequestStackMock();
        $this->securityBlockerClientMock = $this->createSecurityBlockerClientMock();
        $this->subscriber = $this->createSecurityBlockerBackofficeUserLoginEventSubscriber();
    }

    public function testSecurityBlockerBackOfficeUserEventSubscriberShouldCallSecurityBlockerClientOnKernelRequestWhileSendingValidLoginAttempt(): void
    {
        // Arrange
        $securityCheckAuthContextTransfer = $this->tester->createLoginSecurityCheckAuthContextTransfer(static::BACK_OFFICE_USER_TYPE);
        $securityCheckAuthResponseTransfer = (new SecurityCheckAuthResponseTransfer())->setIsBlocked(false);

        // Assert
        $this->securityBlockerClientMock->expects($this->once())
            ->method('checkLoginAttempt')
            ->with($securityCheckAuthContextTransfer)
            ->willReturn($securityCheckAuthResponseTransfer);

        // Act
        $event = $this->createRequestEventForMethod($securityCheckAuthContextTransfer, Request::METHOD_POST);
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber($this->subscriber);
        $eventDispatcher->dispatch($event, KernelEvents::REQUEST);
    }

    public function testSecurityBlockerBackOfficeUserEventSubscriberShouldThrowHttpExceptionOnKernelRequestWhileSendingBlockedLoginAttempt(): void
    {
        // Arrange
        $securityCheckAuthContextTransfer = $this->tester->createLoginSecurityCheckAuthContextTransfer(static::BACK_OFFICE_USER_TYPE);
        $securityCheckAuthResponseTransfer = (new SecurityCheckAuthResponseTransfer())->setIsBlocked(true);

        // Assert
        $this->securityBlockerClientMock->expects($this->once())
            ->method('checkLoginAttempt')
            ->with($securityCheckAuthContextTransfer)
            ->willReturn($securityCheckAuthResponseTransfer);
        $this->expectException(HttpException::class);

        // Act
        $event = $this->createRequestEventForMethod($securityCheckAuthContextTransfer, Request::METHOD_POST);
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber($this->subscriber);
        $eventDispatcher->dispatch($event, KernelEvents::REQUEST);
    }

    public function testSecurityBlockerBackOfficeUserEventSubscriberShouldNotCallSecurityBlockerClientWhileSendingWrongRequest(): void
    {
        // Arrange
        $securityCheckAuthContextTransfer = $this->tester->createLoginSecurityCheckAuthContextTransfer(static::BACK_OFFICE_USER_TYPE);
        $securityCheckAuthResponseTransfer = (new SecurityCheckAuthResponseTransfer())->setIsBlocked(false);

        // Assert
        $this->securityBlockerClientMock->expects($this->never())
            ->method('checkLoginAttempt')
            ->with($securityCheckAuthContextTransfer)
            ->willReturn($securityCheckAuthResponseTransfer);

        // Act
        $event = $this->createRequestEventForMethod($securityCheckAuthContextTransfer, Request::METHOD_GET);
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber($this->subscriber);
        $eventDispatcher->dispatch($event, KernelEvents::REQUEST);
    }

    public function testSecurityBlockerBackOfficeUserEventSubscriberShouldReturnSecurityBlockerClientExceptionWhileFailedLoginAttempt(): void
    {
        // Arrange
        $securityCheckAuthContextTransfer = $this->tester->createLoginSecurityCheckAuthContextTransfer(static::BACK_OFFICE_USER_TYPE);

        // Assert
        $this->securityBlockerClientMock->expects($this->once())
            ->method('registerFailedLoginAttempt')
            ->with($securityCheckAuthContextTransfer);
        $this->requestStackMock->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->tester->createLoginRequest(Request::METHOD_POST, $securityCheckAuthContextTransfer));

        // Act
        $event = $this->createRequestEventForMethod($securityCheckAuthContextTransfer, Request::METHOD_POST);
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber($this->subscriber);
        $eventDispatcher->dispatch($event, LoginFailureEvent::class);
    }

    public function testSecurityBlockerBackOfficeUserEventSubscriberShouldNotCallSecurityBlockerClientWhileSendingValidGetRequest(): void
    {
        // Arrange
        $securityCheckAuthContextTransfer = $this->tester->createLoginSecurityCheckAuthContextTransfer(static::BACK_OFFICE_USER_TYPE);

        // Assert
        $this->securityBlockerClientMock->expects($this->never())
            ->method('registerFailedLoginAttempt')
            ->with($securityCheckAuthContextTransfer);
        $this->requestStackMock->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->tester->createLoginRequest(Request::METHOD_GET, $securityCheckAuthContextTransfer));

        // Act
        $event = $this->createRequestEventForMethod($securityCheckAuthContextTransfer, Request::METHOD_POST);
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber($this->subscriber);
        $eventDispatcher->dispatch($event, LoginFailureEvent::class);
    }

    public function testLoginAttemptBeyondTheReservedAccountLimitIsRejectedWithTooManyRequests(): void
    {
        // Arrange
        $securityCheckAuthContextTransfer = $this->tester->createLoginSecurityCheckAuthContextTransfer(static::BACK_OFFICE_USER_TYPE);
        $this->securityBlockerClientMock->method('checkLoginAttempt')->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(false));

        // Assert
        $this->securityBlockerClientMock->expects($this->once())
            ->method('reserveLoginAttempt')
            ->with($securityCheckAuthContextTransfer)
            ->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(true)->setBlockedFor(360));
        $this->expectException(HttpException::class);

        // Act
        $this->dispatchKernelRequest($this->createRequestEventForMethod($securityCheckAuthContextTransfer, Request::METHOD_POST));
    }

    /**
     * The authenticator reads the POST body only, so the blocker must never count the identifier of a query-string decoy.
     */
    public function testIdentifierOfAQueryStringDecoyIsIgnored(): void
    {
        // Arrange
        $securityCheckAuthContextTransfer = $this->tester->createLoginSecurityCheckAuthContextTransfer(static::BACK_OFFICE_USER_TYPE);
        $request = $this->tester->createLoginRequest(Request::METHOD_POST, $securityCheckAuthContextTransfer);
        $request->query->set('username', 'decoy@example.com');
        $this->securityBlockerClientMock->method('checkLoginAttempt')->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(false));

        // Assert
        $this->securityBlockerClientMock->expects($this->once())
            ->method('reserveLoginAttempt')
            ->with($this->callback(fn (SecurityCheckAuthContextTransfer $contextTransfer): bool => $contextTransfer->getLoginIdentifier() === $securityCheckAuthContextTransfer->getLoginIdentifier()
                && $contextTransfer->getRealm() === SecurityBlockerBackofficeGuiCommunicationTester::LOGIN_REALM_USER))
            ->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(false));

        // Act
        $this->dispatchKernelRequest(new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST));
    }

    public function testFailedLoginRemembersTheIdentifierForTheNextForm(): void
    {
        // Arrange
        $securityCheckAuthContextTransfer = $this->tester->createLoginSecurityCheckAuthContextTransfer(static::BACK_OFFICE_USER_TYPE);
        $request = $this->tester->createLoginRequest(Request::METHOD_POST, $securityCheckAuthContextTransfer);
        $request->setSession(new Session(new MockArraySessionStorage()));
        $this->requestStackMock->method('getCurrentRequest')->willReturn($request);

        // Act
        $this->subscriber->onAuthenticationFailure();

        // Assert
        $this->assertSame($securityCheckAuthContextTransfer->getLoginIdentifier(), $request->getSession()->get(SecurityRequestAttributes::LAST_USERNAME));
    }

    public function testSuccessfulLoginResetsTheAccountCounter(): void
    {
        // Arrange
        $securityCheckAuthContextTransfer = $this->tester->createLoginSecurityCheckAuthContextTransfer(static::BACK_OFFICE_USER_TYPE);
        $request = $this->tester->createLoginRequest(Request::METHOD_POST, $securityCheckAuthContextTransfer);

        // Assert
        $this->securityBlockerClientMock->expects($this->once())
            ->method('registerSuccessfulLoginAttempt')
            ->with($securityCheckAuthContextTransfer);

        // Act
        $this->subscriber->onAuthenticationSuccess(new LoginSuccessEvent(
            $this->createMock(AuthenticatorInterface::class),
            new SelfValidatingPassport(new UserBadge((string)$securityCheckAuthContextTransfer->getLoginIdentifier())),
            $this->createMock(TokenInterface::class),
            $request,
            null,
            'main',
        ));
    }

    /**
     * The login form authenticator accepts the form on every route of its firewall, so the attempt is throttled wherever
     * the form is posted, not only on the login-check route.
     */
    public function testLoginFormPostedToAnotherRouteIsCheckedAndReserved(): void
    {
        // Arrange
        $securityCheckAuthContextTransfer = $this->tester->createLoginSecurityCheckAuthContextTransfer(static::BACK_OFFICE_USER_TYPE);
        $request = $this->tester->createLoginRequest(Request::METHOD_POST, $securityCheckAuthContextTransfer);
        $request->attributes->set('_route', static::ROUTE_OTHER);

        // Assert
        $this->securityBlockerClientMock->expects($this->once())
            ->method('checkLoginAttempt')
            ->with($securityCheckAuthContextTransfer)
            ->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(false));
        $this->securityBlockerClientMock->expects($this->once())
            ->method('reserveLoginAttempt')
            ->with($securityCheckAuthContextTransfer)
            ->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(false));

        // Act
        $this->dispatchKernelRequest(new RequestEvent($this->createMock(HttpKernelInterface::class), $request, $this->getMainRequestType()));
    }

    public function testLoginCheckRequestWithoutTheLoginFormIsIgnored(): void
    {
        // Arrange
        $request = $this->tester->createLoginRequest(Request::METHOD_POST, $this->tester->createLoginSecurityCheckAuthContextTransfer(static::BACK_OFFICE_USER_TYPE));
        $request->request->replace([]);

        // Assert
        $this->securityBlockerClientMock->expects($this->never())->method('checkLoginAttempt');

        // Act
        $this->dispatchKernelRequest(new RequestEvent($this->createMock(HttpKernelInterface::class), $request, $this->getMainRequestType()));
    }

    protected function createRequestEventForMethod(
        SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer,
        string $requestMethod
    ): RequestEvent {
        $kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();
        $request = $this->tester->createLoginRequest($requestMethod, $securityCheckAuthContextTransfer);

        return new RequestEvent($kernel, $request, $this->getMainRequestType());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\HttpFoundation\RequestStack
     */
    protected function createRequestStackMock(): RequestStack
    {
        return $this->createMock(RequestStack::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\SecurityBlockerBackofficeGui\Dependency\Client\SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface
     */
    protected function createSecurityBlockerClientMock(): SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface
    {
        return $this->createMock(SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface::class);
    }

    protected function createSecurityBlockerBackofficeUserLoginEventSubscriber(): SecurityBlockerBackofficeUserLoginEventSubscriber
    {
        $configMock = $this->createConfiguredMock(
            SecurityBlockerBackofficeGuiConfig::class,
            [
                'getSecurityBlockerLoginRealm' => SecurityBlockerBackofficeGuiCommunicationTester::LOGIN_REALM_USER,
                'getBackofficeUserLoginCheckUrl' => static::LOGIN_CHECK_URL,
                'getSecurityBlockerBackofficeUserEntityType' => static::BACK_OFFICE_USER_TYPE,
                'getIpCounterKeyStrategy' => SecurityBlockerBackofficeGuiConfig::IP_COUNTER_KEY_STRATEGY_IP,
            ],
        );

        return new SecurityBlockerBackofficeUserLoginEventSubscriber(
            $this->requestStackMock,
            $this->securityBlockerClientMock,
            $this->getMockBuilder(MessageBuilder::class)->disableOriginalConstructor()->getMock(),
            $configMock,
            new SecurityCheckAuthContextBuilder($configMock),
        );
    }

    protected function getMainRequestType(): int
    {
        return HttpKernelInterface::MAIN_REQUEST;
    }

    protected function dispatchKernelRequest(RequestEvent $event): void
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber($this->subscriber);
        $eventDispatcher->dispatch($event, KernelEvents::REQUEST);
    }

    protected function createEventDispatcherWithCaptchaListener(callable $captchaListener): EventDispatcher
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber($this->subscriber);
        $eventDispatcher->addListener(KernelEvents::REQUEST, $captchaListener, static::CAPTCHA_SUBSCRIBER_PRIORITY);

        return $eventDispatcher;
    }

    protected function createFailingCaptchaListener(): callable
    {
        return function (): void {
            $this->fail('The CAPTCHA was verified before the blocked login attempt was rejected.');
        };
    }

    protected function createRejectingCaptchaListener(): callable
    {
        return static function (RequestEvent $event): void {
            $event->setResponse(new RedirectResponse(static::CAPTCHA_REJECTION_URL));
        };
    }
}
