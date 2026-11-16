<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SecurityBlockerBackofficeGui\Communication\Builder;

use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Spryker\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiConfig;
use Symfony\Component\HttpFoundation\Request;

class SecurityCheckAuthContextBuilder implements SecurityCheckAuthContextBuilderInterface
{
    public function __construct(
        protected SecurityBlockerBackofficeGuiConfig $securityBlockerBackofficeGuiConfig
    ) {
    }

    public function createSecurityCheckAuthContextTransfer(Request $request, ?string $loginIdentifier): SecurityCheckAuthContextTransfer
    {
        return $this->createSecurityCheckAuthContextTransferForIp($request->getClientIp(), $loginIdentifier);
    }

    public function createSecurityCheckAuthContextTransferForIp(?string $ip, ?string $loginIdentifier): SecurityCheckAuthContextTransfer
    {
        return (new SecurityCheckAuthContextTransfer())
            ->setType($this->securityBlockerBackofficeGuiConfig->getSecurityBlockerBackofficeUserEntityType())
            ->setIp($ip)
            ->setRealm($this->securityBlockerBackofficeGuiConfig->getSecurityBlockerLoginRealm())
            ->setLoginIdentifier($loginIdentifier)
            ->setIpCounterKeyStrategy($this->securityBlockerBackofficeGuiConfig->getIpCounterKeyStrategy());
    }
}
