<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\Config\Source;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\OptionSourceInterface;
use Magmodules\Sooqr\Api\ProductData\RepositoryInterface as ProductDataRepository;

/**
 * Attributes Option Source model
 */
class ParentAttributes implements OptionSourceInterface
{

    /**
     * @var array
     */
    public $skipAttributes = [
        'entity_id',
        'sku',
        'visibility',
        'type_id',
        'url',
        'price',
        'image'
    ];

    /**
     * @var Http
     */
    private $request;
    /**
     * @var ProductDataRepository
     */
    private $productDataRepository;

    /**
     * ParentAttributes constructor.
     * @param Http $request
     * @param ProductDataRepository $productDataRepository
     */
    public function __construct(
        Http $request,
        ProductDataRepository $productDataRepository
    ) {
        $this->request = $request;
        $this->productDataRepository = $productDataRepository;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->getAttributes() as $key => $attribute) {
            if (!in_array($key, $this->skipAttributes)) {
                $options[$attribute] = [
                    'value' => $attribute,
                    'label' => $attribute
                ];
            }
        }

        array_multisort(
            array_column($options, 'value'),
            SORT_ASC,
            $options
        );

        return $options;
    }

    /**
     * @return array
     */
    private function getAttributes(): array
    {
        $storeId = (int)$this->request->getParam('store', 0);
        return $this->productDataRepository->getProductAttributes($storeId);
    }
}
