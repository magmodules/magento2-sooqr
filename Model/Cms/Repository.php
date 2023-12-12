<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\Cms;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\Page as CmsPage;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Sooqr\Api\Cms\RepositoryInterface as ConnectCmsRepository;
use Magmodules\Sooqr\Api\Config\RepositoryInterface as DataConfigRepository;

/**
 * Connect CMS Repository Class
 */
class Repository implements ConnectCmsRepository
{

    /**
     * @var DataConfigRepository
     */
    private $configRepository;
    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Repository constructor.
     *
     * @param DataConfigRepository $configRepository
     * @param PageRepositoryInterface $pageRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        DataConfigRepository $configRepository,
        PageRepositoryInterface $pageRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager
    ) {
        $this->configRepository = $configRepository;
        $this->pageRepository = $pageRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getCmsPages(int $storeId): array
    {
        $enabled = $this->configRepository->getCmsEnableType($storeId);
        if (!$enabled) {
            return [];
        }

        $cmsPages = [];
        $selection = ($enabled == 2)
            ? $this->configRepository->getCmsSelection($storeId)
            : null;
        $searchCriteria = $this->getSearchCriteria((int)$storeId, (string)$selection);
        $result = $this->pageRepository->getList($searchCriteria);
        $baseUrl = $this->getBaseUrl($storeId);

        /** @var CmsPage $page */
        foreach ($result->getItems() as $page) {
            $cmsPages[] = [
                'sqr:content_type' => 'cms',
                'sqr:id' => $page->getIdentifier(),
                'sqr:assoc_id' => $page->getIdentifier(),
                'sqr:title' => $page->getTitle(),
                'sqr:description' => $this->cleanData((string)$page->getContent()),
                'sqr:link' => sprintf('%s%s', $baseUrl, $page->getIdentifier())
            ];
        }
        return $cmsPages;
    }

    /**
     * @param int $storeId
     * @param string $selection
     *
     * @return SearchCriteria
     */
    private function getSearchCriteria(
        int $storeId,
        string $selection
    ): SearchCriteria {
        $searchCriteria = $this->searchCriteriaBuilder;
        $searchCriteria->addFilter(PageInterface::IS_ACTIVE, 1)
            ->addFilter('store_id', [$storeId, 0], 'in');
        if ($selection) {
            $searchCriteria->addFilter(PageInterface::PAGE_ID, $selection, 'in');
        }

        return $searchCriteria->create();
    }

    /**
     * @param int $storeId
     * @return string
     */
    private function getBaseUrl(int $storeId): string
    {
        try {
            return $this->storeManager->getStore($storeId)->getBaseUrl();
        } catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * @param string $value
     *
     * @return string
     */
    private function cleanData(string $value): string
    {
        $data = trim(strip_tags(
            str_replace(["\r", "\n"], "", $value)
        ));
        return preg_replace('/{{[^}]+}}/', '', $data);
    }
}
