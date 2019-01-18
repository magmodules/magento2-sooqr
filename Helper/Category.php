<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
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

/**
 * Class Category
 *
 * @package Magmodules\Sooqr\Helper
 */
class Category extends AbstractHelper
{

    /**
     * @var General
     */
    private $generalHelper;
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;
    /**
     * @var CategoryHelper
     */
    private $category;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var CategoryFactory
     */
    private $categoryFactory;
    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * Category constructor.
     *
     * @param Context                   $context
     * @param General                   $generalHelper
     * @param CategoryHelper            $category
     * @param StoreManagerInterface     $storeManager
     * @param CategoryRepository        $categoryRepository
     * @param CategoryFactory           $categoryFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        CategoryHelper $category,
        StoreManagerInterface $storeManager,
        CategoryRepository $categoryRepository,
        CategoryFactory $categoryFactory,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->category = $category;
        $this->categoryRepository = $categoryRepository;
        $this->generalHelper = $generalHelper;
        $this->storeManager = $storeManager;
        $this->categoryFactory = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;

        parent::__construct($context);
    }

    /**
     * @param        $storeId
     * @param string $field
     * @param string $default
     * @param string $exclude
     *
     * @return array
     */
    public function getCollection($storeId, $field = '', $default = '', $exclude = '')
    {
        $data = [];
        $parent = $this->storeManager->getStore($storeId)->getRootCategoryId();
        $attributes = ['name', 'level', 'path', 'is_active'];

        if (!empty($field)) {
            $attributes[] = $field;
        }

        if (!empty($exclude)) {
            $attributes[] = $exclude;
        }

        $collection = $this->categoryCollectionFactory->create()
            ->setStoreId($storeId)
            ->addAttributeToSelect($attributes)
            ->addFieldToFilter('is_active', ['eq' => 1])
            ->addFieldToFilter('path', ['like' => '%/' . $parent . '/%'])
            ->load();

        foreach ($collection as $category) {
            $data[$category->getId()] = [
                'name'    => $category->getName(),
                'level'   => $category->getLevel(),
                'path'    => $category->getPath(),
                'custom'  => (!empty($field) ? $category->getData($field) : ''),
                'exclude' => (!empty($exclude) ? $category->getData($exclude) : 0),
            ];
        }

        $categories = [];
        foreach ($data as $key => $category) {
            $paths = explode('/', $category['path']);
            $pathText = [];
            $custom = $default;
            $level = 0;
            $exclude = 0;
            foreach ($paths as $path) {
                if (!empty($data[$path]['name']) && ($path != $parent)) {
                    $pathText[] = $data[$path]['name'];
                    if (!empty($data[$path]['custom'])) {
                        $custom = $data[$path]['custom'];
                    }
                    if (!empty($data[$path]['exclude'])) {
                        $exclude = 1;
                    }
                    $level++;
                }
            }
            if (!$exclude) {
                $categories[$key] = [
                    'name'   => $category['name'],
                    'level'  => $level,
                    'path'   => $pathText,
                    'custom' => $custom
                ];
            }
        }

        return $categories;
    }
}
