<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Helper\Page as PageHelper;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magmodules\Sooqr\Helper\General as GeneralHelper;

class Cms extends AbstractHelper
{

    private $general;
    private $pageRepository;
    private $page;
    private $searchCriteriaBuilder;
    private $filterBuilder;
    private $cmsPage;

    /**
     * Cms constructor.
     *
     * @param Context                 $context
     * @param General                 $general
     * @param PageRepositoryInterface $pageRepositoryInterface
     * @param PageInterface           $pageInterface
     * @param PageHelper              $cmsPage
     * @param SearchCriteriaBuilder   $searchCriteriaBuilder
     * @param FilterBuilder           $filterBuilder
     */
    public function __construct(
        Context $context,
        GeneralHelper $general,
        PageRepositoryInterface $pageRepositoryInterface,
        PageInterface $pageInterface,
        PageHelper $cmsPage,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder
    ) {
        $this->general = $general;
        $this->pageRepository = $pageRepositoryInterface;
        $this->page = $pageInterface;
        $this->cmsPage = $cmsPage;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        parent::__construct($context);
    }

    /**
     * @return array
     */
    public function getCmsPages()
    {
        $cmspages = [];
        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder
                    ->setField('identifier')
                    ->setValue('no-route')
                    ->setConditionType('neq')
                    ->create()
            ]
        );
        $items = $this->pageRepository->getList($this->searchCriteriaBuilder->create())->getItems();

        foreach ($items as $item) {
            if ($item['active']) {
                $url = $this->cmsPage->getPageUrl($item['identifier']);
                $cmspages[] = [
                    'sqr:content_type' => 'cms',
                    'sqr:id'           => $item['identifier'],
                    'sqr:title'        => $item['title'],
                    'sqr:content'      => $this->cleanData($item['content']),
                    'sqr:url'          => strtok($url, '?'),
                ];
            }
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
