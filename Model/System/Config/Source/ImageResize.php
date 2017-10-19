<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ImageResize implements ArrayInterface
{

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * ImageResize constructor.
     *
     * @param Http                 $request
     * @param DirectoryList        $directoryList
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Http $request,
        DirectoryList $directoryList,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->directoryList = $directoryList;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {

        $storeId = (int)$this->request->getParam('store', 0);
        $websiteId = (int)$this->request->getParam('website', 0);
        $imageSourcePath = 'magmodules_sooqr/data/image_source';

        if ($websiteId > 0) {
            $scope = ScopeInterface::SCOPE_WEBSITE;
            $source = $this->scopeConfig->getValue($imageSourcePath, $scope, $websiteId);
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $source = $this->scopeConfig->getValue($imageSourcePath, $scope, $storeId);
        }

        if (empty($source)) {
            $source = 'image';
        }

        $options = [];
        $dir = $this->directoryList->getPath('pub');
        $dir .= '/media/catalog/product/cache/' . $source . '/';

        if (file_exists($dir)) {
            $dirs = array_filter(glob($dir . '*'), 'is_dir');
            if (!empty($dirs)) {
                foreach ($dirs as $imgOption) {
                    $imgOption = str_replace($dir, '', $imgOption);
                    if (strlen($imgOption) < 8) {
                        $options[] = ['value' => $imgOption, 'label' => $imgOption];
                    }
                }
            }
        }

        return $options;
    }
}
