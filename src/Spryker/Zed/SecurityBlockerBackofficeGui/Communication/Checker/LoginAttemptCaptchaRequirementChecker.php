<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SecurityBlockerBackofficeGui\Communication\Checker;

use Generated\Shared\Transfer\CaptchaRequirementRequestTransfer;
use Spryker\Zed\SecurityBlockerBackofficeGui\Communication\Builder\SecurityCheckAuthContextBuilderInterface;
use Spryker\Zed\SecurityBlockerBackofficeGui\Dependency\Client\SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface;
use Spryker\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiConfig;

class LoginAttemptCaptchaRequirementChecker implements LoginAttemptCaptchaRequirementCheckerInterface
{
    public function __construct(
        protected SecurityBlockerBackofficeGuiToSecurityBlockerClientInterface $securityBlockerClient,
        protected SecurityCheckAuthContextBuilderInterface $securityCheckAuthContextBuilder,
        protected SecurityBlockerBackofficeGuiConfig $securityBlockerBackofficeGuiConfig
    ) {
    }

    public function isCaptchaRequired(CaptchaRequirementRequestTransfer $captchaRequirementRequestTransfer): bool
    {
        if ($captchaRequirementRequestTransfer->getContextOrFail() !== $this->securityBlockerBackofficeGuiConfig->getCaptchaContext()) {
            return false;
        }

        return $this->securityBlockerClient->isLoginChallengeRequired(
            $this->securityCheckAuthContextBuilder->createSecurityCheckAuthContextTransferForIp(
                $captchaRequirementRequestTransfer->getIp(),
                $captchaRequirementRequestTransfer->getLoginIdentifier(),
            ),
        );
    }
}
