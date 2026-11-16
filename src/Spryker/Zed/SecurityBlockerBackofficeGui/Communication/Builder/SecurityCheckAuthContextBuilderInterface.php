<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SecurityBlockerBackofficeGui\Communication\Builder;

use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Symfony\Component\HttpFoundation\Request;

interface SecurityCheckAuthContextBuilderInterface
{
    public function createSecurityCheckAuthContextTransfer(Request $request, ?string $loginIdentifier): SecurityCheckAuthContextTransfer;

    public function createSecurityCheckAuthContextTransferForIp(?string $ip, ?string $loginIdentifier): SecurityCheckAuthContextTransfer;
}
