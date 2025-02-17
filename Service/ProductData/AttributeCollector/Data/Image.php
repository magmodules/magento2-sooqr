<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\ProductData\AttributeCollector\Data;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\StoreRepositoryInterface;

class Image
{

    public const REQUIRE = ['entity_ids', 'store_id'];

    private ResourceConnection $resource;
    private StoreRepositoryInterface $storeRepository;

    private array $entityIds;
    private int $storeId;
    private bool $includeHidden;
    private ?string $mediaUrl = null;
    private string $linkField;

    public function __construct(
        ResourceConnection $resource,
        StoreRepositoryInterface $storeRepository,
        MetadataPool $metadataPool
    ) {
        $this->resource = $resource;
        $this->storeRepository = $storeRepository;
        $this->linkField = $metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }

    /**
     * Get array of all product images with types
     *
     * Structure of response
     * [product_id] = [path1, path2, ..., pathN]
     *
     * @param array[] $entityIds array of product IDs
     * @param int $storeId store ID
     * @param bool $includeHidden collect hidden images
     * @return array[]
     */
    public function execute(array $entityIds = [], int $storeId = 0, bool $includeHidden = false): array
    {
        $this->setData('entity_ids', $entityIds);
        $this->setData('store_id', $storeId);
        $this->setData('include_hidden', $includeHidden);

        $combinedData = $this->combineData(
            $this->collectImages(),
            $this->collectTypes()
        );

        $this->setData('media_url', null);

        return $combinedData;
    }

    /**
     * @param string $type
     * @param mixed $data
     */
    public function setData(string $type, $data)
    {
        switch ($type) {
            case 'entity_ids':
                $this->entityIds = $data;
                break;
            case 'store_id':
                $this->storeId = $data;
                break;
            case 'include_hidden':
                $this->includeHidden = $data;
                break;
            case 'media_url':
                $this->mediaUrl = $data;
                break;
        }
    }

    /**
     * Collect product images efficiently.
     *
     * @return array
     */
    private function collectImages(): array
    {
        if (empty($this->entityIds)) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $mediaGalleryTable = $this->resource->getTableName('catalog_product_entity_media_gallery');
        $mediaGalleryValueTable = $this->resource->getTableName('catalog_product_entity_media_gallery_value');

        $select = $connection->select()
            ->from(['mg' => $mediaGalleryTable], ['value'])
            ->join(
                ['mgv' => $mediaGalleryValueTable],
                'mg.value_id = mgv.value_id',
                ['entity_id' => $this->linkField, 'store_id', 'position']
            )
            ->where('mgv.store_id IN (?)', [0, $this->storeId])
            ->where('mgv.' . $this->linkField . ' IN (?)', $this->entityIds);

        if (!$this->includeHidden) {
            $select->where('mgv.disabled = 0');
        }

        return $connection->fetchAll($select);
    }

    /**
     * Collect product types efficiently.
     *
     * @return array
     */
    private function collectTypes(): array
    {
        if (empty($this->entityIds)) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $eavTable = $this->resource->getTableName('eav_attribute');
        $cpevTable = $this->resource->getTableName('catalog_product_entity_varchar');

        // Define only required fields
        $fields = ['entity_id' => $this->linkField, 'store_id', 'value'];

        $select = $connection->select()
            ->from(['ea' => $eavTable], ['attribute_code'])
            ->join(
                ['cpev' => $cpevTable],
                'cpev.attribute_id = ea.attribute_id',
                $fields
            )
            ->where('ea.frontend_input = ?', 'media_image')
            ->where('cpev.store_id IN (?)', [0, $this->storeId])
            ->where('cpev.' . $this->linkField . ' IN (?)', $this->entityIds);

        $data = [];
        foreach ($connection->fetchAll($select) as $item) {
            $data[$item['entity_id']][$item['value']][] = $item['attribute_code'];
        }

        return $data;
    }

    /**
     * Combine image and type data efficiently.
     *
     * @param array $imagesData
     * @param array $typesData
     * @return array
     */
    private function combineData(array $imagesData, array $typesData): array
    {
        $result = [];

        foreach ($imagesData as $imageData) {
            $entityId = $imageData['entity_id'];
            $storeId = $imageData['store_id'];
            $position = $imageData['position'];
            $imageValue = $imageData['value'];

            $result[$entityId][$storeId][$position] = [
                'file' => $this->getMediaUrl('catalog/product' . $imageValue),
                'position' => $position,
                'types' => $typesData[$entityId][$imageValue] ?? []
            ];
        }

        return $result;
    }
    /**
     * @param string $path
     * @return string
     */
    private function getMediaUrl(string $path): string
    {
        if ($this->mediaUrl == null) {
            try {
                $this->mediaUrl = $this->storeRepository
                    ->getById((int)$this->storeId)
                    ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            } catch (Exception $exception) {
                $this->mediaUrl = '';
            }
        }

        return $this->mediaUrl . $path;
    }

    /**
     * @return string[]
     */
    public function getRequiredParameters(): array
    {
        return self::REQUIRE;
    }

    /**
     * @param string $type
     */
    public function resetData($type = 'all')
    {
        if ($type == 'all') {
            unset($this->entityIds);
            unset($this->storeId);
            unset($this->includeHidden);
        }
        switch ($type) {
            case 'entity_ids':
                unset($this->entityIds);
                break;
            case 'store_id':
                unset($this->storeId);
                break;
            case 'include_hidden':
                unset($this->includeHidden);
                break;
        }
    }
}
