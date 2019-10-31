<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\Data\PageInterface;
use Magmodules\Sooqr\Helper\General as GeneralHelper;

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
     * @var PageRepositoryInterface
     */
    private $pageRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * Cms constructor.
     *
     * @param Context                 $context
     * @param General                 $generalHelper
     * @param PageRepositoryInterface $pageRepository
     * @param SearchCriteriaBuilder   $searchCriteriaBuilder
     * @param FilterBuilder           $filterBuilder
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        PageRepositoryInterface $pageRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder
    ) {
        $this->generalHelper = $generalHelper;
        $this->pageRepository = $pageRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        parent::__construct($context);
    }

    /**
     * @param $storeId
     * @param $baseUrl
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCmsPages($storeId, $baseUrl)
    {
        $cmspages = [];
        $selection = null;
        $enabled = $this->generalHelper->getStoreValue(self::XPATH_CMS_PAGES);

        if (!$enabled) {
            return $cmspages;
        }

        $selection = $enabled == 2 ? $this->generalHelper->getStoreValue(self::XPATH_CMS_SELECTION) : null;
        $searchCriteria = $this->getSearchCriteria($storeId, $selection);
        $result = $this->pageRepository->getList($searchCriteria);

        /** @var \Magento\Cms\Model\Page $page */
        foreach ($result->getItems() as $page) {
            $cmspages[] = [
                'sqr:content_type' => 'cms',
                'sqr:id'           => $page->getIdentifier(),
                'sqr:title'        => $page->getTitle(),
                'sqr:description'  => $this->cleanData($page->getContent()),
                'sqr:link'         => $baseUrl . $page->getIdentifier()
            ];
        }

        return $cmspages;
    }

    /**
     * @param int    $storeId
     * @param string $selection
     *
     * @return \Magento\Framework\Api\SearchCriteria
     */
    private function getSearchCriteria($storeId, $selection)
    {
        $searchCriteria = $this->searchCriteriaBuilder;
        $searchCriteria->addFilter(PageInterface::IS_ACTIVE, 1);
        $searchCriteria->addFilter('store_id', [$storeId, 0], 'in');

        if ($selection !== null) {
            $searchCriteria->addFilter(PageInterface::PAGE_ID, $selection, 'in');
        }

        return $searchCriteria->create();
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
