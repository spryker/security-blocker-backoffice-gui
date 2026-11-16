<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\SecurityBlockerBackofficeGui;

use Codeception\Actor;
use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Spryker\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiConfig;
use Symfony\Component\HttpFoundation\Request;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(\SprykerTest\Zed\SecurityBlockerBackofficeGui\PHPMD)
 */
class SecurityBlockerBackofficeGuiCommunicationTester extends Actor
{
    use _generated\SecurityBlockerBackofficeGuiCommunicationTesterActions;

    /**
     * @uses \Spryker\Zed\SecurityBlockerBackofficeGui\Communication\EventSubscriber\SecurityBlockerBackofficeUserEventSubscriber::FORM_NAME
     *
     * @var string
     */
    protected const FORM_NAME = 'auth';

    /**
     * @uses \Spryker\Zed\SecurityBlockerBackofficeGui\Communication\EventSubscriber\SecurityBlockerBackofficeUserEventSubscriber::FIELD_USERNAME
     *
     * @var string
     */
    protected const FIELD_USERNAME = 'username';

    /**
     * @uses \Spryker\Shared\SecurityBlockerBackoffice\SecurityBlockerBackofficeConfig::LOGIN_REALM_USER
     */
    public const string LOGIN_REALM_USER = 'user';

    public const string LOGIN_IDENTIFIER = 'admin@spryker.com';

    /**
     * @uses \Spryker\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiConfig::BACK_OFFICE_LOGIN_CHECK_URL
     *
     * @var string
     */
    protected const BACK_OFFICE_LOGIN_CHECK_URL = 'login_check';

    public function createSecurityCheckAuthContextTransfer(string $type): SecurityCheckAuthContextTransfer
    {
        // The subscribers always set the submitted username, null when the request has none.
        return (new SecurityCheckAuthContextTransfer())
            ->setType($type)
            ->setIp('')
            ->setLoginIdentifier(null)
            ->setIpCounterKeyStrategy(SecurityBlockerBackofficeGuiConfig::IP_COUNTER_KEY_STRATEGY_IP);
    }

    public function createLoginSecurityCheckAuthContextTransfer(string $type): SecurityCheckAuthContextTransfer
    {
        return $this->createSecurityCheckAuthContextTransfer($type)
            ->setRealm(static::LOGIN_REALM_USER)
            ->setLoginIdentifier(static::LOGIN_IDENTIFIER);
    }

    public function createRequest(string $method, SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer): Request
    {
        return $this->createLoginCheckRequest($method, $securityCheckAuthContextTransfer->getAccount(), $securityCheckAuthContextTransfer->getIp());
    }

    public function createLoginRequest(
        string $method,
        SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer
    ): Request {
        return $this->createLoginCheckRequest($method, $securityCheckAuthContextTransfer->getLoginIdentifier(), $securityCheckAuthContextTransfer->getIp());
    }

    protected function createLoginCheckRequest(string $method, ?string $username, ?string $ip): Request
    {
        $loginForm = [static::FIELD_USERNAME => $username];

        $request = Request::create(
            '/' . static::BACK_OFFICE_LOGIN_CHECK_URL,
            $method,
            [
                static::FORM_NAME => $loginForm,
            ],
            [],
            [],
            [
                'REMOTE_ADDR' => $ip,
            ],
        );
        $request->attributes->add(['_route' => '/' . static::BACK_OFFICE_LOGIN_CHECK_URL]);

        return $request;
    }
}
