<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\SecurityBlockerBackofficeGui\Communication\EventSubscriber;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;
use Spryker\Zed\SecurityBlockerBackofficeGui\Communication\Builder\MessageBuilderInterface;
use Spryker\Zed\SecurityBlockerBackofficeGui\Communication\EventSubscriber\SecurityBlockerBackofficeUserEventSubscriber;
use Spryker\Zed\SecurityBlockerBackofficeGui\Communication\SecurityBlockerBackofficeGuiCommunicationFactory;
use Spryker\Zed\SecurityBlockerBackofficeGui\Dependency\Client\SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface;
use Spryker\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiConfig;
use SprykerTest\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiCommunicationTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group SecurityBlockerBackofficeGui
 * @group Communication
 * @group EventSubscriber
 * @group IpCounterKeyStrategyTest
 * Add your own group annotations below this line
 */
class IpCounterKeyStrategyTest extends Unit
{
    /**
     * @uses \Spryker\Zed\SecurityGui\Communication\Form\LoginForm::FORM_NAME
     */
    protected const string FORM_NAME = 'auth';

    /**
     * @uses \Spryker\Zed\SecurityGui\Communication\Form\LoginForm::FIELD_USERNAME
     */
    protected const string FIELD_USERNAME = 'username';

    protected const string IP = '10.0.0.1';

    protected const string USERNAME = 'admin@spryker.com';

    protected const string OTHER_USERNAME = 'other@spryker.com';

    protected SecurityBlockerBackofficeGuiCommunicationTester $tester;

    public function testDefaultStrategyKeepsTheReleasedPerIpKeyWithoutTheUsername(): void
    {
        // Arrange
        $securityBlockerBackofficeGuiConfig = new SecurityBlockerBackofficeGuiConfig();

        // Act
        $securityCheckAuthContextTransfers = $this->dispatchLoginAttempts($securityBlockerBackofficeGuiConfig, [static::USERNAME]);

        // Assert
        $this->assertSame(SecurityBlockerBackofficeGuiConfig::IP_COUNTER_KEY_STRATEGY_IP, $securityCheckAuthContextTransfers[0]->getIpCounterKeyStrategy());
        $this->assertNull($securityCheckAuthContextTransfers[0]->getAccount());
        $this->assertSame(static::IP, $securityCheckAuthContextTransfers[0]->getIp());
    }

    public function testIpAndUsernameStrategyPassesTheStrategyAndTheSubmittedUsernameToTheSecurityBlocker(): void
    {
        // Arrange
        $securityBlockerBackofficeGuiConfig = $this->createIpAndUsernameConfig();

        // Act
        $securityCheckAuthContextTransfers = $this->dispatchLoginAttempts($securityBlockerBackofficeGuiConfig, [static::USERNAME]);

        // Assert
        $this->assertSame(SecurityBlockerBackofficeGuiConfig::IP_COUNTER_KEY_STRATEGY_IP_AND_USERNAME, $securityCheckAuthContextTransfers[0]->getIpCounterKeyStrategy());
        $this->assertSame(static::USERNAME, $securityCheckAuthContextTransfers[0]->getLoginIdentifier());
    }

    public function testIpAndUsernameStrategyAppliesToTheAccountLoginProtectionContext(): void
    {
        // Arrange
        $securityBlockerBackofficeGuiCommunicationFactory = $this->createFactory($this->createIpAndUsernameConfig());

        // Act
        $securityCheckAuthContextTransfer = $securityBlockerBackofficeGuiCommunicationFactory
            ->createSecurityCheckAuthContextBuilder()
            ->createSecurityCheckAuthContextTransfer($this->createLoginRequest(static::USERNAME), static::USERNAME);

        // Assert
        $this->assertSame(SecurityBlockerBackofficeGuiConfig::IP_COUNTER_KEY_STRATEGY_IP_AND_USERNAME, $securityCheckAuthContextTransfer->getIpCounterKeyStrategy());
        $this->assertSame(static::USERNAME, $securityCheckAuthContextTransfer->getLoginIdentifier());
    }

    public function testDefaultStrategyLeavesTheAccountLoginProtectionContextWithoutAnAccount(): void
    {
        // Arrange
        $securityBlockerBackofficeGuiCommunicationFactory = $this->createFactory(new SecurityBlockerBackofficeGuiConfig());

        // Act
        $securityCheckAuthContextTransfer = $securityBlockerBackofficeGuiCommunicationFactory
            ->createSecurityCheckAuthContextBuilder()
            ->createSecurityCheckAuthContextTransfer($this->createLoginRequest(static::USERNAME), static::USERNAME);

        // Assert
        $this->assertSame(SecurityBlockerBackofficeGuiConfig::IP_COUNTER_KEY_STRATEGY_IP, $securityCheckAuthContextTransfer->getIpCounterKeyStrategy());
        $this->assertNull($securityCheckAuthContextTransfer->getAccount());
    }

    public function testIpAndUsernameStrategyPassesNoUsernameWhenTheUsernameIsNotAString(): void
    {
        // Arrange
        $securityBlockerBackofficeGuiConfig = $this->createIpAndUsernameConfig();
        $request = $this->createLoginRequestWithLoginForm([static::FIELD_USERNAME => [static::USERNAME]]);

        // Act
        $securityCheckAuthContextTransfers = $this->dispatchLoginRequests($securityBlockerBackofficeGuiConfig, [$request]);

        // Assert
        $this->assertNull($securityCheckAuthContextTransfers[0]->getLoginIdentifier());
        $this->assertNull($securityCheckAuthContextTransfers[0]->getAccount());
    }

    public function testIpAndUsernameStrategyCountsAFailedLoginOnTheKeyTheLoginIsCheckedOn(): void
    {
        // Arrange
        $securityBlockerBackofficeGuiConfig = $this->createIpAndUsernameConfig();
        $checkedSecurityCheckAuthContextTransfers = $this->dispatchLoginAttempts($securityBlockerBackofficeGuiConfig, [static::USERNAME]);

        // Act
        $countedSecurityCheckAuthContextTransfers = $this->dispatchLoginFailure($securityBlockerBackofficeGuiConfig, $this->createLoginRequest(static::USERNAME));

        // Assert
        $this->assertCount(1, $countedSecurityCheckAuthContextTransfers);
        $this->assertSame(SecurityBlockerBackofficeGuiConfig::IP_COUNTER_KEY_STRATEGY_IP_AND_USERNAME, $countedSecurityCheckAuthContextTransfers[0]->getIpCounterKeyStrategy());
        $this->assertSame($this->getIpCounterKeyParts($checkedSecurityCheckAuthContextTransfers[0]), $this->getIpCounterKeyParts($countedSecurityCheckAuthContextTransfers[0]));
    }

    /**
     * @param list<string> $usernames
     *
     * @return list<\Generated\Shared\Transfer\SecurityCheckAuthContextTransfer>
     */
    protected function dispatchLoginAttempts(SecurityBlockerBackofficeGuiConfig $securityBlockerBackofficeGuiConfig, array $usernames): array
    {
        return $this->dispatchLoginRequests($securityBlockerBackofficeGuiConfig, array_map(fn (string $username): Request => $this->createLoginRequest($username), $usernames));
    }

    /**
     * @param list<\Symfony\Component\HttpFoundation\Request> $requests
     *
     * @return list<\Generated\Shared\Transfer\SecurityCheckAuthContextTransfer>
     */
    protected function dispatchLoginRequests(SecurityBlockerBackofficeGuiConfig $securityBlockerBackofficeGuiConfig, array $requests): array
    {
        $securityCheckAuthContextTransfers = [];
        $securityBlockerClientMock = $this->createMock(SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface::class);
        $securityBlockerClientMock->method('isAccountBlocked')->willReturnCallback(
            function (SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer) use (&$securityCheckAuthContextTransfers): SecurityCheckAuthResponseTransfer {
                $securityCheckAuthContextTransfers[] = $securityCheckAuthContextTransfer;

                return (new SecurityCheckAuthResponseTransfer())->setIsBlocked(false);
            },
        );
        $securityBlockerBackofficeUserEventSubscriber = $this->createSubscriber($securityBlockerBackofficeGuiConfig, $securityBlockerClientMock, new RequestStack());

        foreach ($requests as $request) {
            $securityBlockerBackofficeUserEventSubscriber->onKernelRequest(new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST));
        }

        return $securityCheckAuthContextTransfers;
    }

    /**
     * @return list<\Generated\Shared\Transfer\SecurityCheckAuthContextTransfer>
     */
    protected function dispatchLoginFailure(SecurityBlockerBackofficeGuiConfig $securityBlockerBackofficeGuiConfig, Request $request): array
    {
        $securityCheckAuthContextTransfers = [];
        $securityBlockerClientMock = $this->createMock(SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface::class);
        $securityBlockerClientMock->method('incrementLoginAttemptCount')->willReturnCallback(
            function (SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer) use (&$securityCheckAuthContextTransfers): SecurityCheckAuthResponseTransfer {
                $securityCheckAuthContextTransfers[] = $securityCheckAuthContextTransfer;

                return new SecurityCheckAuthResponseTransfer();
            },
        );
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $this->createSubscriber($securityBlockerBackofficeGuiConfig, $securityBlockerClientMock, $requestStack)->onAuthenticationFailure();

        return $securityCheckAuthContextTransfers;
    }

    protected function createSubscriber(
        SecurityBlockerBackofficeGuiConfig $securityBlockerBackofficeGuiConfig,
        SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface $securityBlockerClient,
        RequestStack $requestStack
    ): SecurityBlockerBackofficeUserEventSubscriber {
        return new SecurityBlockerBackofficeUserEventSubscriber(
            $requestStack,
            $securityBlockerClient,
            $this->createMock(MessageBuilderInterface::class),
            $securityBlockerBackofficeGuiConfig,
        );
    }

    /**
     * @return array<string, string|null>
     */
    protected function getIpCounterKeyParts(SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer): array
    {
        return [
            SecurityCheckAuthContextTransfer::TYPE => $securityCheckAuthContextTransfer->getType(),
            SecurityCheckAuthContextTransfer::IP => $securityCheckAuthContextTransfer->getIp(),
            SecurityCheckAuthContextTransfer::ACCOUNT => $securityCheckAuthContextTransfer->getAccount(),
            SecurityCheckAuthContextTransfer::LOGIN_IDENTIFIER => $securityCheckAuthContextTransfer->getLoginIdentifier(),
            SecurityCheckAuthContextTransfer::IP_COUNTER_KEY_STRATEGY => $securityCheckAuthContextTransfer->getIpCounterKeyStrategy(),
        ];
    }

    protected function createIpAndUsernameConfig(): SecurityBlockerBackofficeGuiConfig
    {
        $securityBlockerBackofficeGuiConfig = new SecurityBlockerBackofficeGuiConfig();

        return $this->createConfiguredMock(SecurityBlockerBackofficeGuiConfig::class, [
            'getIpCounterKeyStrategy' => SecurityBlockerBackofficeGuiConfig::IP_COUNTER_KEY_STRATEGY_IP_AND_USERNAME,
            'getBackofficeUserLoginCheckUrl' => $securityBlockerBackofficeGuiConfig->getBackofficeUserLoginCheckUrl(),
            'getSecurityBlockerBackofficeUserEntityType' => $securityBlockerBackofficeGuiConfig->getSecurityBlockerBackofficeUserEntityType(),
            'getSecurityBlockerLoginRealm' => $securityBlockerBackofficeGuiConfig->getSecurityBlockerLoginRealm(),
        ]);
    }

    protected function createFactory(SecurityBlockerBackofficeGuiConfig $securityBlockerBackofficeGuiConfig): SecurityBlockerBackofficeGuiCommunicationFactory
    {
        $securityBlockerBackofficeGuiCommunicationFactory = new SecurityBlockerBackofficeGuiCommunicationFactory();
        $securityBlockerBackofficeGuiCommunicationFactory->setConfig($securityBlockerBackofficeGuiConfig);

        return $securityBlockerBackofficeGuiCommunicationFactory;
    }

    protected function createLoginRequest(string $username): Request
    {
        return $this->createLoginRequestWithLoginForm([static::FIELD_USERNAME => $username]);
    }

    /**
     * @param array<string, mixed> $loginForm
     */
    protected function createLoginRequestWithLoginForm(array $loginForm): Request
    {
        $request = Request::create(
            '/login_check',
            Request::METHOD_POST,
            [static::FORM_NAME => $loginForm],
            [],
            [],
            ['REMOTE_ADDR' => static::IP],
        );
        $request->attributes->set('_route', (new SecurityBlockerBackofficeGuiConfig())->getBackofficeUserLoginCheckUrl());

        return $request;
    }
}
