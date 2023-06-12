<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source;

use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Category Source Option Source model
 */
class CmsPages implements OptionSourceInterface
{

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
     * CmsPages constructor.
     *
     * @param Http $request
     * @param PageRepositoryInterface $pageRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Http $request,
        PageRepositoryInterface $pageRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->request = $request;
        $this->pageRepository = $pageRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function toOptionArray(): array
    {
        $options = [];

        $searchCriteria = $this->getSearchCriteria();
        $result = $this->pageRepository->getList($searchCriteria);

        foreach ($result->getItems() as $page) {
            $options[] = [
                'value' => $page->getId(),
                'label' => $page->getTitle() . ' (' . $page->getIdentifier() . ')'
            ];
        }

        return $options;
    }

    /**
     * @return SearchCriteria
     */
    private function getSearchCriteria(): SearchCriteria
    {
        $searchCriteria = $this->searchCriteriaBuilder;
        $storeId = $this->request->getParam('store');
        if ($storeId > 0) {
            $searchCriteria->addFilter('store_id', [$storeId, 0], 'in');
        }

        return $searchCriteria->create();
    }
}
