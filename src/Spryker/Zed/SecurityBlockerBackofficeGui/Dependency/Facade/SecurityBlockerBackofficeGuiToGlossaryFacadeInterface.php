<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SecurityBlockerBackofficeGui\Dependency\Facade;

use Generated\Shared\Transfer\LocaleTransfer;

interface SecurityBlockerBackofficeGuiToGlossaryFacadeInterface
{
    /**
     * @param array<string, mixed> $data
     *
     * @throws \Spryker\Zed\Glossary\Business\Exception\MissingTranslationException
     */
    public function translate(string $keyName, array $data = [], ?LocaleTransfer $localeTransfer = null): string;
}
