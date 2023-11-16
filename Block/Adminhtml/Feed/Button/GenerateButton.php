<?php
/**
 * Copyright © MundoRecreatie. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Block\Adminhtml\Feed\Button;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magmodules\Sooqr\Model\Config\Source\FeedType;

class GenerateButton implements ButtonProviderInterface
{
    /**
     * @var Context
     */
    private $context;

    /**
     * GenericButton constructor.
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Generate Feeds')->render(),
            'on_click' => sprintf(
                "location.href = '%s';",
                $this->context->getUrlBuilder()->getUrl(
                    '*/*/generate',
                    ['type' => FeedType::FULL]
                )
            ),
            'class' => 'generate',
            'sort_order' => 10
        ];
    }
}
