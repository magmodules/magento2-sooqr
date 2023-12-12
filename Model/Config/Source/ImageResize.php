<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Glob;
use Magento\Framework\Filesystem\Io\File;
use Magento\Store\Model\ScopeInterface;
use Magmodules\Sooqr\Api\Config\System\DataInterface;

/**
 * Category Source Option Source model
 */
class ImageResize implements OptionSourceInterface
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
     * @var Glob
     */
    private $glob;
    /**
     * @var File
     */
    private $ioFilesystem;

    /**
     * ImageResize constructor.
     *
     * @param Http $request
     * @param DirectoryList $directoryList
     * @param ScopeConfigInterface $scopeConfig
     * @param Glob $glob
     * @param File $ioFilesystem
     */
    public function __construct(
        Http $request,
        DirectoryList $directoryList,
        ScopeConfigInterface $scopeConfig,
        Glob $glob,
        File $ioFilesystem
    ) {
        $this->directoryList = $directoryList;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->glob = $glob;
        $this->ioFilesystem = $ioFilesystem;
    }

    /**
     * @return array
     * @throws FileSystemException
     */
    public function toOptionArray(): array
    {
        $storeId = (int)$this->request->getParam('store', 0);
        $websiteId = (int)$this->request->getParam('website', 0);

        if ($websiteId > 0) {
            $scope = ScopeInterface::SCOPE_WEBSITE;
            $source = $this->scopeConfig->getValue(DataInterface::XML_PATH_IMAGE_SOURCE, $scope, $websiteId);
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
            $source = $this->scopeConfig->getValue(DataInterface::XML_PATH_IMAGE_SOURCE, $scope, $storeId);
        }

        if (empty($source)) {
            $source = 'image';
        }

        $options = [];
        $dir = $this->directoryList->getPath('pub') . '/media/catalog/product/cache/' . $source . '/';
        if ($this->ioFilesystem->fileExists($dir)) {
            $dirs = array_filter(
                $this->glob->glob($dir . '*'),
                'is_dir'
            );
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
