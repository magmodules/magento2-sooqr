<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Sooqr\Model\WebApi;

use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Url\DecoderInterface;
use Magmodules\Sooqr\Api\Feed\RepositoryInterface as FeedRepository;
use Magmodules\Sooqr\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Sooqr\Api\WebApi\RepositoryInterface;

/**
 * Web API repository class
 */
class Repository implements RepositoryInterface
{

    /**
     * @var FeedRepository
     */
    private $feedRepository;
    /**
     * @var Encryptor
     */
    private $encryptor;
    /**
     * @var File
     */
    private $file;
    /**
     * @var DateTime
     */
    private $date;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var DecoderInterface
     */
    private $decoder;

    /**
     * @param FeedRepository $feedRepository
     * @param LogRepository $logRepository
     * @param File $file
     * @param DateTime $date
     * @param Encryptor $encryptor
     * @param DecoderInterface $decoder
     */
    public function __construct(
        FeedRepository $feedRepository,
        LogRepository $logRepository,
        File $file,
        DateTime $date,
        Encryptor $encryptor,
        DecoderInterface $decoder
    ) {
        $this->feedRepository = $feedRepository;
        $this->logRepository = $logRepository;
        $this->encryptor = $encryptor;
        $this->file = $file;
        $this->date = $date;
        $this->decoder = $decoder;
    }

    /**
     * @inheritDoc
     */
    public function getFeed(string $code): array
    {
        try {
            $feedId = (int)$this->encryptor->decrypt(
                $this->decoder->decode($code)
            );
            $feed = $this->feedRepository->get($feedId);
            $feed->setDownloadAt($this->date->gmtDate());
            $this->feedRepository->save($feed);
            $content = $feed->getFilename() ? $this->file->fileGetContents($feed->getFilename()) : '';
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Feed WebApi', $exception->getMessage());
            return [$exception->getMessage()];
        }

        return ['sooqr_plain_xml', $content];
    }
}
