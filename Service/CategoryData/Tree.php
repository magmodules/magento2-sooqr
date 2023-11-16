<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\CategoryData;

use Magento\Catalog\Api\CategoryRepositoryInterface as CategoryRepository;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Block\Adminhtml\Category\Tree as CategoryTree;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Data class
 */
class Tree
{

    public const TREE_MAP = ['id', 'text', 'name', 'path', 'children'];

    /**
     * @var CategoryTree
     */
    private $categoryTree;
    /**
     * @var StoreManagerInterface
     */
    private $storeManagerInterface;
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;
    /**
     * @var int
     */
    private $rootCatId;

    /**
     * Tree constructor.
     * @param CategoryTree $categoryTree
     * @param StoreManagerInterface $storeManagerInterface
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        CategoryTree $categoryTree,
        StoreManagerInterface $storeManagerInterface,
        CategoryRepository $categoryRepository
    ) {
        $this->categoryTree = $categoryTree;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Returns category tree array
     *
     * @param int $storeId
     * @return array
     * @throws NoSuchEntityException
     */
    public function execute(int $storeId): array
    {
        $parenNodeCategory = $this->getRootCategory($storeId);
        $tree = $this->categoryTree->getTree($parenNodeCategory);
        $this->cleanup($tree);
        return $tree;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getRootCategory(int $storeId): CategoryInterface
    {
        $categoryId = $this->storeManagerInterface->getStore($storeId)->getRootCategoryId();
        $this->rootCatId = $categoryId;
        return $this->categoryRepository->get($categoryId);
    }

    /**
     * @param $tree
     * @param int $level
     * @return void
     */
    private function cleanup(&$tree, int $level = 0): void
    {
        $level++;
        foreach ($tree as $index => $node) {
            $tree['level'] = $level;
            if (is_array($tree[$index])) {
                $this->cleanup($tree[$index], $level);
                continue;
            }
            $tree['name'] = explode(' (ID:', $tree['text'])[0];
            if (strpos($tree['path'], '/' . $this->rootCatId . '/') !== false) {
                $position = strpos($tree['path'], '/' . $this->rootCatId . '/');
                $tree['path'] = substr($tree['path'], $position + strlen((string)$this->rootCatId) + 2);
            }
            if (!in_array($index, self::TREE_MAP)) {
                unset($tree[$index]);
            }
        }
    }
}
