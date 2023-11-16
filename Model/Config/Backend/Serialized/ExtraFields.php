<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\Config\Backend\Serialized;

use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;

/**
 * Class ExtraFields
 *
 */
class ExtraFields extends ArraySerialized
{

    /**
     * Unset unused fields.
     *
     * @return $this
     */
    public function beforeSave()
    {
        $data = $this->getValue();
        if (is_array($data)) {
            foreach ($data as $key => $row) {
                if (empty($row['attribute'])) {
                    unset($data[$key]);
                }
            }
        }
        $this->setValue($data);
        return parent::beforeSave();
    }
}
