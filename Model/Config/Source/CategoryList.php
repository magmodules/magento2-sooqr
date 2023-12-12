<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Sooqr\Model\Config\Source;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Category List Option Source model
 */
class CategoryList implements OptionSourceInterface
{

    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * CategoryList constructor.
     *
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        CollectionFactory $categoryCollectionFactory
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->toArray() as $key => $value) {
            $options[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $options;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $categoryList = [];
        foreach ($this->getCategoryCollection() as $category) {
            $categoryList[$category->getEntityId()] = [
                'name' => $category->getName(),
                'path' => $category->getPath()
            ];
        }

        $categoryArray = [];
        foreach ($categoryList as $k => $v) {
            if ($path = $this->getCategoryPath($v['path'], $categoryList)) {
                $categoryArray[$k] = $path;
            }
        }

        asort($categoryArray);

        return $categoryArray;
    }

    /**
     * @return Collection
     */
    public function getCategoryCollection(): Collection
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['path', 'name']);

        return $collection;
    }

    /**
     * @param string $path
     * @param array $categoryList
     *
     * @return string
     */
    public function getCategoryPath(string $path, array $categoryList): string
    {
        $categoryPath = [];
        $rootCats = [1, 2];
        $path = explode('/', $path);

        foreach ($path as $catId) {
            if (!in_array($catId, $rootCats)) {
                if (!empty($categoryList[$catId]['name'])) {
                    $categoryPath[] = $categoryList[$catId]['name'];
                }
            }
        }

        if (!empty($categoryPath)) {
            return implode(' » ', $categoryPath);
        }

        return '';
    }
}
