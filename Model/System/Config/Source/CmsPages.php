<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\Data\PageInterface;

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
     * @var Http
     */
    private $request;
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
     * CmsPages constructor.
     *
     * @param Http                    $request
     * @param PageRepositoryInterface $pageRepository
     * @param SearchCriteriaBuilder   $searchCriteriaBuilder
     * @param FilterBuilder           $filterBuilder
     */
    public function __construct(
        Http $request,
        PageRepositoryInterface $pageRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder
    ) {
        $this->request = $request;
        $this->pageRepository = $pageRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;

    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $searchCriteria = $this->getSearchCriteria();
            $result = $this->pageRepository->getList($searchCriteria);
            foreach ($result->getItems() as $page) {
                $this->options[] = [
                    'value' => $page->getId(),
                    'label' => $page->getTitle() . ' (' . $page->getIdentifier() . ')'
                ];
            }
        }

        return $this->options;
    }

    /**
     * @return \Magento\Framework\Api\SearchCriteria
     */
    private function getSearchCriteria()
    {
        $searchCriteria = $this->searchCriteriaBuilder;

        $storeId = $this->request->getParam('store');
        if ($storeId > 0) {
            $searchCriteria->addFilter('store_id', [$storeId, 0], 'in');
        }

        return $searchCriteria->create();
    }
}
