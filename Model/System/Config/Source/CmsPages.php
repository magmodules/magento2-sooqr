<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\App\Emulation;
use Magento\Framework\App\Area;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;

/**
 * Class Country
 *
 * @package Magmodules\Sooqr\Model\System\Config\Source
 */
class CmsPages implements ArrayInterface
{

    /**
     * @var array
     */
    public $options = [];
    /**
     * @var PageCollectionFactory
     */
    private $pageCollectionFactory;
    /**
     * @var Http
     */
    private $request;
    /**
     * @var Emulation
     */
    private $appEmulation;

    /**
     * CmsPages constructor.
     *
     * @param PageCollectionFactory $pageCollectionFactory
     * @param Http                  $request
     * @param Emulation             $appEmulation
     */
    public function __construct(
        PageCollectionFactory $pageCollectionFactory,
        Http $request,
        Emulation $appEmulation
    ) {
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->request = $request;
        $this->appEmulation = $appEmulation;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $storeId = $this->request->getParam('store');

            if ($storeId > 0) {
                $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
                $pages = $this->pageCollectionFactory->create();
                $this->appEmulation->stopEnvironmentEmulation();
            } else {
                $pages = $this->pageCollectionFactory->create();
            }

            foreach ($pages as $page) {
                $this->options[] = [
                    'value' => $page->getPageId(),
                    'label' => $page->getTitle() . ' (' . $page->getIdentifier() . ')'
                ];
            }
        }

        return $this->options;
    }
}
