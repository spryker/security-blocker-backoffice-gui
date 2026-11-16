<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\SecurityBlockerBackofficeGui\Communication\Plugin;

use Codeception\Test\Unit;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\EventDispatcher\EventDispatcherInterface;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\SecurityBlockerBackofficeGui\Communication\Plugin\EventDispatcher\SecurityBlockerBackofficeUserEventDispatcherPlugin;
use Spryker\Zed\SecurityBlockerBackofficeGui\Communication\SecurityBlockerBackofficeGuiCommunicationFactory;
use Spryker\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiConfig;
use Spryker\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiDependencyProvider;
use SprykerTest\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiCommunicationTester;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group SecurityBlockerBackofficeGui
 * @group Communication
 * @group Plugin
 * @group SecurityBlockerBackOfficeUserEventDispatcherPluginTest
 * Add your own group annotations below this line
 */
class SecurityBlockerBackOfficeUserEventDispatcherPluginTest extends Unit
{
    protected const string UNKNOWN_LOGIN_PROTECTION_STRATEGY = 'unknown';

    protected SecurityBlockerBackofficeGuiCommunicationTester $tester;

    protected SecurityBlockerBackofficeUserEventDispatcherPlugin $securityBlockerBackOfficeUserEventDispatcherPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container();
        $securityBlockerBackofficeGuiDependencyProvider = new SecurityBlockerBackofficeGuiDependencyProvider();
        $securityBlockerBackofficeGuiDependencyProvider->provideCommunicationLayerDependencies($container);

        $securityBlockerBackofficeGuiFactoryMock = $this->createMock(SecurityBlockerBackofficeGuiCommunicationFactory::class);
        $securityBlockerBackofficeGuiFactoryMock->setContainer($container);

        $this->securityBlockerBackOfficeUserEventDispatcherPlugin = new SecurityBlockerBackofficeUserEventDispatcherPlugin();
        $this->securityBlockerBackOfficeUserEventDispatcherPlugin->setFactory($securityBlockerBackofficeGuiFactoryMock);
    }

    public function testSecurityBlockerBackOfficeUserEventDispatcherPluginShouldCallAddSubscriberOnEventDispatcher(): void
    {
        // Arrange
        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        // Assert
        $eventDispatcherMock->expects($this->once())
            ->method('addSubscriber');

        // Act
        $this->securityBlockerBackOfficeUserEventDispatcherPlugin->extend(
            $eventDispatcherMock,
            $this->createMock(ContainerInterface::class),
        );
    }

    public function testPluginAddsTheIpOnlySubscriberForTheIpLoginProtectionStrategy(): void
    {
        // Arrange
        $eventSubscriber = $this->createMock(EventSubscriberInterface::class);
        $securityBlockerBackofficeGuiFactoryMock = $this->createFactoryMock(SecurityBlockerBackofficeGuiConfig::LOGIN_PROTECTION_STRATEGY_IP);
        $securityBlockerBackofficeGuiFactoryMock->method('createSecurityBlockerBackOfficeUserEventSubscriber')->willReturn($eventSubscriber);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);

        // Assert
        $securityBlockerBackofficeGuiFactoryMock->expects($this->never())->method('createSecurityBlockerBackofficeUserLoginEventSubscriber');
        $eventDispatcherMock->expects($this->once())->method('addSubscriber')->with($this->identicalTo($eventSubscriber));

        // Act
        $this->createPlugin($securityBlockerBackofficeGuiFactoryMock)->extend($eventDispatcherMock, $this->createMock(ContainerInterface::class));
    }

    public function testPluginAddsTheLoginSubscriberForTheIpAndAccountLoginProtectionStrategy(): void
    {
        // Arrange
        $eventSubscriber = $this->createMock(EventSubscriberInterface::class);
        $securityBlockerBackofficeGuiFactoryMock = $this->createFactoryMock(SecurityBlockerBackofficeGuiConfig::LOGIN_PROTECTION_STRATEGY_IP_AND_ACCOUNT);
        $securityBlockerBackofficeGuiFactoryMock->method('createSecurityBlockerBackofficeUserLoginEventSubscriber')->willReturn($eventSubscriber);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);

        // Assert
        $securityBlockerBackofficeGuiFactoryMock->expects($this->never())->method('createSecurityBlockerBackOfficeUserEventSubscriber');
        $eventDispatcherMock->expects($this->once())->method('addSubscriber')->with($this->identicalTo($eventSubscriber));

        // Act
        $this->createPlugin($securityBlockerBackofficeGuiFactoryMock)->extend($eventDispatcherMock, $this->createMock(ContainerInterface::class));
    }

    public function testPluginFailsForALoginProtectionStrategyWithoutARegisteredSubscriber(): void
    {
        // Arrange
        $securityBlockerBackofficeGuiFactoryMock = $this->createFactoryMock(static::UNKNOWN_LOGIN_PROTECTION_STRATEGY);

        // Expect
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->createPlugin($securityBlockerBackofficeGuiFactoryMock)->extend($this->createMock(EventDispatcherInterface::class), $this->createMock(ContainerInterface::class));
    }

    protected function createFactoryMock(string $loginProtectionStrategy): SecurityBlockerBackofficeGuiCommunicationFactory&MockObject
    {
        $securityBlockerBackofficeGuiConfigMock = $this->createPartialMock(SecurityBlockerBackofficeGuiConfig::class, ['getLoginProtectionStrategy']);
        $securityBlockerBackofficeGuiConfigMock->method('getLoginProtectionStrategy')->willReturn($loginProtectionStrategy);

        $securityBlockerBackofficeGuiFactoryMock = $this->createPartialMock(SecurityBlockerBackofficeGuiCommunicationFactory::class, [
            'createSecurityBlockerBackOfficeUserEventSubscriber',
            'createSecurityBlockerBackofficeUserLoginEventSubscriber',
        ]);
        $securityBlockerBackofficeGuiFactoryMock->setConfig($securityBlockerBackofficeGuiConfigMock);

        return $securityBlockerBackofficeGuiFactoryMock;
    }

    protected function createPlugin(
        SecurityBlockerBackofficeGuiCommunicationFactory $securityBlockerBackofficeGuiCommunicationFactory
    ): SecurityBlockerBackofficeUserEventDispatcherPlugin {
        $securityBlockerBackofficeUserEventDispatcherPlugin = new SecurityBlockerBackofficeUserEventDispatcherPlugin();
        $securityBlockerBackofficeUserEventDispatcherPlugin->setFactory($securityBlockerBackofficeGuiCommunicationFactory);

        return $securityBlockerBackofficeUserEventDispatcherPlugin;
    }
}
