<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Cms\Helper\Page as PageHelper;
use Magmodules\Sooqr\Helper\General as GeneralHelper;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;

/**
 * Class Cms
 *
 * @package Magmodules\Sooqr\Helper
 */
class Cms extends AbstractHelper
{

    const XPATH_CMS_PAGES = 'magmodules_sooqr/cms/enable';
    const XPATH_CMS_SELECTION = 'magmodules_sooqr/cms/cms_selection';

    /**
     * @var General
     */
    private $generalHelper;
    /**
     * @var PageCollectionFactory
     */
    private $pageCollectionFactory;
    /**
     * @var PageHelper
     */
    private $cmsPage;

    /**
     * Cms constructor.
     *
     * @param Context               $context
     * @param General               $generalHelper
     * @param PageHelper            $cmsPage
     * @param PageCollectionFactory $pageCollectionFactory
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        PageHelper $cmsPage,
        PageCollectionFactory $pageCollectionFactory
    ) {
        $this->generalHelper = $generalHelper;
        $this->cmsPage = $cmsPage;
        $this->pageCollectionFactory = $pageCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @return array
     */
    public function getCmsPages()
    {
        $cmspages = [];
        $selection = null;
        $enabled = $this->generalHelper->getStoreValue(self::XPATH_CMS_PAGES);

        if (!$enabled) {
            return $cmspages;
        }

        $pages = $this->pageCollectionFactory->create();

        if ($enabled == 2) {
            $selection = explode(',', $this->generalHelper->getStoreValue(self::XPATH_CMS_SELECTION));
        }

        foreach ($pages as $page) {
            if (isset($page['is_active']) && $page['is_active'] != 1) {
                continue;
            }

            if (isset($page['active']) && $page['active'] != 1) {
                continue;
            }

            if (!empty($selection) && (!in_array($page['page_id'], $selection))) {
                continue;
            }

            $url = $this->cmsPage->getPageUrl($page['identifier']);
            $cmspages[] = [
                'sqr:content_type' => 'cms',
                'sqr:id'           => $page['identifier'],
                'sqr:title'        => $page['title'],
                'sqr:description'  => $this->cleanData($page['content']),
                'sqr:link'         => strtok($url, '?'),
            ];
        }

        return $cmspages;
    }

    /**
     * @param $value
     *
     * @return mixed|string
     */
    public function cleanData($value)
    {
        $value = str_replace(["\r", "\n"], "", $value);
        $value = strip_tags($value);
        return $value;
    }
}
