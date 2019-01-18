<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Backend\Serialized;

use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;

/**
 * Class Filters
 *
 * @package Magmodules\Sooqr\Model\Config\Backend\Serialized
 */
class Filters extends ArraySerialized
{

    /**
     * Uset unused fields.
     *
     * @return $this
     */
    public function beforeSave()
    {
        $data = $this->getValue();
        if (is_array($data)) {
            foreach ($data as $key => $row) {
                if (empty($row['attribute']) || empty($row['condition'])) {
                    unset($data[$key]);
                    continue;
                }
                $data[$key]['value'] = trim($row['value']);
                if (($row['condition'] != 'empty') && ($row['condition'] != 'not-empty')) {
                    if (empty($row['value'])) {
                        unset($data[$key]);
                        continue;
                    }
                }
            }
        }
        $this->setValue($data);
        return parent::beforeSave();
    }
}
