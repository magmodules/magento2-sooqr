<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\Feed;

use Magento\Framework\Filesystem\Io\File;

/**
 * Feed creator service
 */
class Create
{

    /**
     * @var File
     */
    private $file;
    /**
     * @var bool
     */
    private $categoryTree = false;
    /**
     * @var bool
     */
    private $categoryNode = false;

    /**
     * Create constructor.
     * @param File $file
     */
    public function __construct(
        File $file
    ) {
        $this->file = $file;
    }

    /**
     * @param array $feed
     * @param int $storeId
     * @param string $path
     */
    public function execute(array $feed, int $storeId, string $path)
    {
        $xmlStr = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<rss xmlns:sqr="http://base.sooqr.com/ns/1.0" version="2.0" encoding="utf-8">
XML;
        $xmlStr .= $this->createXml($feed);
        $xmlStr .= <<<XML
</rss>
XML;
        $fileInfo = $this->file->getPathInfo($path);
        $this->file->mkdir($fileInfo['dirname']);
        $this->file->write($path, $xmlStr);
    }

    /**
     * @param array $data
     * @return string
     */
    public function createXml(array $data): string
    {
        $xmlStr = '';
        foreach ($data as $key => $value) {
            if ($key === 'category_tree') {
                $this->categoryTree = true;
            }
            if ($key === 'sqr:categories') {
                $this->categoryNode = true;
            }

            if (is_numeric($key) && $this->categoryTree) {
                $key = 'category_item';
            } elseif (is_numeric($key) && $this->categoryNode) {
                $key = 'node';
            } elseif (is_numeric($key) && !$this->categoryNode && !$this->categoryTree) {
                $key = 'item';
            }
            if (!is_array($value)) {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                $value = htmlspecialchars((string)$value, ENT_XML1, 'UTF-8');
                $xmlStr .= <<<XML
<$key>$value
XML;
            } else {
                $subData = $this->createXml($value);
                $xmlStr .= <<<XML
<$key>$subData
XML;
            }
            $xmlStr .= <<<XML
</$key>
XML;
            if ($key == 'category_tree') {
                $this->categoryTree = false;
            }
            if ($key == 'sqr:categories') {
                $this->categoryNode = false;
            }
        }
        return $xmlStr;
    }
}
