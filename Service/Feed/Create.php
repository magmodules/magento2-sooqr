<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Service\Feed;

use Exception;
use Magento\Framework\Filesystem\Io\File;
use SimpleXMLElement;

/**
 * Feed creator service
 */
class Create
{
    private const NAMESPACE_URL = 'http://base.sooqr.com/ns/1.0';
    private const DEFAULT_ENCODING = 'UTF-8';
    private const EMPTY_NAMESPACE = ['node'];

    private bool $categoryNode = false;

    private File $file;

    /**
     * Create constructor.
     * @param File $file
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * Initialize the base XML structure.
     *
     * @return SimpleXMLElement The initialized XML object.
     * @throws Exception
     */
    private function initializeXml(): SimpleXMLElement
    {
        return new SimpleXMLElement(
            sprintf(
                '<?xml version="1.0" encoding="%s"?><rss xmlns:sqr="%s" version="2.0" encoding="%s"></rss>',
                self::DEFAULT_ENCODING,
                self::NAMESPACE_URL,
                self::DEFAULT_ENCODING
            )
        );
    }

    /**
     * Generate and save the XML feed.
     *
     * @param array $feed
     * @param int $storeId
     * @param string $path
     * @throws Exception
     */
    public function execute(array $feed, int $storeId, string $path): void
    {
        $xml = $this->initializeXml();
        $this->arrayToXml(['config' => $feed['config']], $xml);
        $this->arrayToXml(['product' => $feed['products']], $xml, self::NAMESPACE_URL);

        // Save XML to file
        $fileInfo = $this->file->getPathInfo($path);
        $this->file->mkdir($fileInfo['dirname']);
        $this->file->write($path, $xml->asXML());
    }

    /**
     * Recursively convert array to XML.
     *
     * @param array $data
     * @param SimpleXMLElement $xml
     * @param string|null $namespace
     */
    private function arrayToXml(array $data, SimpleXMLElement $xml, ?string $namespace = null): void
    {
        foreach ($data as $key => $value) {
            if (strpos((string)$key, "sqr:category") === 0) {
                $this->categoryNode = true;
            }

            if (is_numeric($key)) {
                $key = $this->categoryNode  ? 'node' : 'item';
            }

            if (is_array($value)) {
                $subNode = $xml->addChild($key);
                $this->arrayToXml($value, $subNode, $this->getNameSpace($key, $namespace));
            } else {
                $value = htmlspecialchars((string)$value, ENT_XML1, 'UTF-8');
                $xml->addChild($key,$value,$this->getNameSpace($key, $namespace));
            }

            if (strpos((string)$key, "sqr:category") === 0) {
                $this->categoryNode = false;
            }
        }
    }

    /**
     * @param $key
     * @param $namespace
     * @return mixed|null
     */
    private function getNameSpace($key, $namespace): ?string
    {
        return !in_array($key, self::EMPTY_NAMESPACE) ? $namespace : null;
    }
}