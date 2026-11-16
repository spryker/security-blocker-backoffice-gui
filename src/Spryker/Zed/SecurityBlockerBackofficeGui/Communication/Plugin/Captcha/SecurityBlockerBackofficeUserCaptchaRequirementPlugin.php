<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SecurityBlockerBackofficeGui\Communication\Plugin\Captcha;

use Generated\Shared\Transfer\CaptchaRequirementRequestTransfer;
use Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Spryker\Zed\SecurityBlockerBackofficeGui\Communication\SecurityBlockerBackofficeGuiCommunicationFactory getFactory()
 * @method \Spryker\Zed\SecurityBlockerBackofficeGui\SecurityBlockerBackofficeGuiConfig getConfig()
 */
class SecurityBlockerBackofficeUserCaptchaRequirementPlugin extends AbstractPlugin implements CaptchaRequirementPluginInterface
{
    /**
     * {@inheritDoc}
     * - Serves the `backoffice_user_login` context only; returns `false` for any other context.
     * - Returns `true` while the security blocker requires a challenge for the next Back Office login attempt of the client IP
     *   and login identifier: the per-IP, the account or the realm-wide failure counter of the `user` realm reached its challenge threshold.
     * - Uses the per-IP counter key of `SecurityBlockerBackofficeGuiConfig::getIpCounterKeyStrategy()`, the same key the failed logins are counted on.
     * - Returns `true` and logs an error when the security blocker cannot read the counters.
     *
     * @api
     */
    public function isCaptchaRequired(CaptchaRequirementRequestTransfer $captchaRequirementRequestTransfer): bool
    {
        return $this->getFactory()
            ->createLoginAttemptCaptchaRequirementChecker()
            ->isCaptchaRequired($captchaRequirementRequestTransfer);
    }
}
