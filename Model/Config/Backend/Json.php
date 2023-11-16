<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

class Json extends Value
{

    /**
     * @return Value
     * @throws LocalizedException
     */
    public function afterSave(): Value
    {
        if ($value = $this->getValue()) {
            json_decode($value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new LocalizedException(__('CustomJS value is not valid JSON'));
            }
        }

        return parent::afterSave();
    }
}
