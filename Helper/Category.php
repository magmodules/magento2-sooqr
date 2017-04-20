<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Catalog\Model\CategoryRepository as CategoryRepository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

use Magmodules\Sooqr\Helper\General as GeneralHelper;

class Category extends AbstractHelper
{

    private $general;
    private $categoryRepository;
    private $category;
    private $storeManager;
    private $categoryFactory;
    private $categoryCollectionFactory;

    /**
     * Category constructor.
     * @param Context $context
     * @param General $general
     * @param CategoryHelper $category
     * @param StoreManagerInterface $storeManager
     * @param CategoryRepository $categoryRepository
     * @param CategoryFactory $categoryFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        Context $context,
        GeneralHelper $general,
        CategoryHelper $category,
        StoreManagerInterface $storeManager,
        CategoryRepository $categoryRepository,
        CategoryFactory $categoryFactory,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->category = $category;
        $this->categoryRepository = $categoryRepository;
        $this->general = $general;
        $this->storeManager = $storeManager;
        $this->categoryFactory = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        
        parent::__construct($context);
    }

    /**
     * @param $field
     * @param $default
     *
     * @return array
     */
    public function getCollection($field, $default)
    {
        $data = [];
        $parent = $this->storeManager->getStore()->getRootCategoryId();
        $attributes = ['name', 'level', 'path', 'is_active', $field];

        $collection = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect($attributes)
            ->addFieldToFilter('is_active', ['eq' => 1])
            ->load();
        
        foreach ($collection as $category) {
            $custom = $category->getData($field);
            $data[$category->getId()] = [
                    'name' => $category->getName(),
                    'level' => $category->getLevel(),
                    'path' => $category->getPath(),
                    'custom' => $custom
                ];
        }
                
        foreach ($data as $key => $category) {
            $paths = explode('/', $category['path']);
            $path_text = [];
            $custom = $default;
            $level = 0;
            foreach ($paths as $path) {
                if (!empty($data[$path]['name']) && ($path != $parent)) {
                    $path_text[] = $data[$path]['name'];
                    if (!empty($data[$path]['custom'])) {
                        $custom = $data[$path]['custom'];
                    }
                    $level++;
                }
            }
            $data[$key] = [
                'name' => $category['name'],
                'level' => $level,
                'path' => $path_text,
                'custom' => $custom
            ];
        }
        
        return $data;
    }
}
