<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\SecurityBlockerBackofficeGui\Communication\Plugin;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaRequirementRequestTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Spryker\Zed\SecurityBlockerBackofficeGui\Communication\Plugin\Captcha\SecurityBlockerBackofficeUserCaptchaRequirementPlugin;
use Spryker\Zed\SecurityBlockerBackofficeGui\Communication\SecurityBlockerBackofficeGuiCommunicationFactory;
use Spryker\Zed\SecurityBlockerBackofficeGui\Dependency\Client\SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface;
use Spryker\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiConfig;
use Spryker\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiDependencyProvider;
use SprykerTest\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group SecurityBlockerBackofficeGui
 * @group Communication
 * @group Plugin
 * @group SecurityBlockerBackofficeUserCaptchaRequirementPluginTest
 * Add your own group annotations below this line
 */
class SecurityBlockerBackofficeUserCaptchaRequirementPluginTest extends Unit
{
    /**
     * @uses \Spryker\Shared\Captcha\CaptchaConfig::CONTEXT_MERCHANT_PORTAL_USER_LOGIN
     */
    protected const string OTHER_CAPTCHA_CONTEXT = 'merchant_portal_user_login';

    protected const string IP = '10.0.0.1';

    protected const string LOGIN_IDENTIFIER = 'admin@spryker.com';

    protected SecurityBlockerBackofficeGuiCommunicationTester $tester;

    public function testCaptchaIsRequiredWhileTheSecurityBlockerRequiresAChallenge(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createSecurityBlockerClientMock(true);

        // Act
        $isCaptchaRequired = $this->createPlugin($securityBlockerClientMock)->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer());

        // Assert
        $this->assertTrue($isCaptchaRequired);
    }

    public function testCaptchaIsNotRequiredWithoutAChallenge(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createSecurityBlockerClientMock(false);

        // Act
        $isCaptchaRequired = $this->createPlugin($securityBlockerClientMock)->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer());

        // Assert
        $this->assertFalse($isCaptchaRequired);
    }

    public function testPluginDoesNotServeAnotherContext(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface::class);
        $securityBlockerClientMock->expects($this->never())->method('isLoginChallengeRequired');

        // Act
        $isCaptchaRequired = $this->createPlugin($securityBlockerClientMock)
            ->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer()->setContext(static::OTHER_CAPTCHA_CONTEXT));

        // Assert
        $this->assertFalse($isCaptchaRequired);
    }

    public function testChallengeIsReadFromThePerIpCounterTheFailedLoginsAreCountedOn(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface::class);

        // Assert
        $securityBlockerClientMock->expects($this->once())
            ->method('isLoginChallengeRequired')
            ->with($this->callback(fn (SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer): bool => $securityCheckAuthContextTransfer->getIp() === static::IP
                && $securityCheckAuthContextTransfer->getLoginIdentifier() === static::LOGIN_IDENTIFIER
                && $securityCheckAuthContextTransfer->getIpCounterKeyStrategy() === SecurityBlockerBackofficeGuiConfig::IP_COUNTER_KEY_STRATEGY_IP_AND_USERNAME))
            ->willReturn(false);

        // Act
        $this->createPlugin($securityBlockerClientMock, SecurityBlockerBackofficeGuiConfig::IP_COUNTER_KEY_STRATEGY_IP_AND_USERNAME)
            ->isCaptchaRequired($this->createCaptchaRequirementRequestTransfer());
    }

    protected function createPlugin(
        SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface $securityBlockerClient,
        string $ipCounterKeyStrategy = SecurityBlockerBackofficeGuiConfig::IP_COUNTER_KEY_STRATEGY_IP
    ): SecurityBlockerBackofficeUserCaptchaRequirementPlugin {
        $this->tester->setDependency(SecurityBlockerBackofficeGuiDependencyProvider::CLIENT_SECURITY_BLOCKER, $securityBlockerClient);

        $securityBlockerBackofficeGuiConfig = new SecurityBlockerBackofficeGuiConfig();
        $securityBlockerBackofficeGuiCommunicationFactory = new SecurityBlockerBackofficeGuiCommunicationFactory();
        $securityBlockerBackofficeGuiCommunicationFactory->setConfig($this->createConfiguredMock(SecurityBlockerBackofficeGuiConfig::class, [
            'getCaptchaContext' => $securityBlockerBackofficeGuiConfig->getCaptchaContext(),
            'getIpCounterKeyStrategy' => $ipCounterKeyStrategy,
            'getSecurityBlockerBackofficeUserEntityType' => $securityBlockerBackofficeGuiConfig->getSecurityBlockerBackofficeUserEntityType(),
            'getSecurityBlockerLoginRealm' => $securityBlockerBackofficeGuiConfig->getSecurityBlockerLoginRealm(),
        ]));

        $securityBlockerBackofficeUserCaptchaRequirementPlugin = new SecurityBlockerBackofficeUserCaptchaRequirementPlugin();
        $securityBlockerBackofficeUserCaptchaRequirementPlugin->setFactory($securityBlockerBackofficeGuiCommunicationFactory);

        return $securityBlockerBackofficeUserCaptchaRequirementPlugin;
    }

    protected function createSecurityBlockerClientMock(bool $isLoginChallengeRequired): SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface
    {
        $securityBlockerClientMock = $this->createMock(SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface::class);
        $securityBlockerClientMock->method('isLoginChallengeRequired')->willReturn($isLoginChallengeRequired);

        return $securityBlockerClientMock;
    }

    protected function createCaptchaRequirementRequestTransfer(): CaptchaRequirementRequestTransfer
    {
        return (new CaptchaRequirementRequestTransfer())
            ->setContext((new SecurityBlockerBackofficeGuiConfig())->getCaptchaContext())
            ->setIp(static::IP)
            ->setLoginIdentifier(static::LOGIN_IDENTIFIER);
    }
}
