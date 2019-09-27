<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AdobeStockImage\Model;

use Magento\AdobeStockImageApi\Api\GetImageListInterface;
use Magento\Framework\Api\Search\Document;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Exception\SerializationException;
use Psr\Log\LoggerInterface;

/**
 * Class GetRelatedImages
 */
class GetRelatedImages
{
    /**
     * @var GetImageListInterface
     */
    private $getImageList;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string[]
     */
    private $fields;

    /**
     * GetRelatedImages constructor.
     * @param GetImageListInterface $getImageList
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param LoggerInterface $logger
     * @param array $fields
     */
    public function __construct(
        GetImageListInterface $getImageList,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        LoggerInterface $logger,
        array $fields = []
    ) {
        $this->getImageList = $getImageList;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->logger = $logger;
        $this->fields = $fields;
    }

    /**
     * Get related images
     *
     * @param int $imageId
     * @param int $limit
     *
     * @return array
     * @throws IntegrationException
     */
    public function execute(int $imageId, int $limit): array
    {
        $relatedImageGroups = [];
        try {
            foreach ($this->fields as $key => $field) {
                $filter = $this->filterBuilder->setField($field)->setValue($imageId)->create();
                $searchCriteria = $this->searchCriteriaBuilder->addFilter($filter)
                    ->setPageSize($limit)
                    ->create();
                $relatedImageGroups[$key] = $this->serializeRelatedImages(
                    $this->getImageList->execute($searchCriteria)->getItems()
                );
            }
            return $relatedImageGroups;
        } catch (\Exception $exception) {
            $message = __('Get related images list failed: %s', $exception->getMessage());
            throw new IntegrationException($message, $exception);
        }
    }

    /**
     * Serialize related image data.
     *
     * @param Document[] $images
     * @return array
     * @throws SerializationException
     */
    private function serializeRelatedImages(array $images): array
    {
        $data = [];
        try {
            /** @var Document $image */
            foreach ($images as $image) {
                $data[] = [
                    'id' => $image->getId(),
                    'title' => $image->getCustomAttribute('title')->getValue(),
                    'thumbnail_url' => $image->getCustomAttribute('thumbnail_240_url')->getValue(),
                    'thumbnail_500_url' => $image->getCustomAttribute('thumbnail_500_url')->getValue(),
                    'creator_name' => $image->getCustomAttribute('creator_name')->getValue(),
                    'content_type' => $image->getCustomAttribute('content_type')->getValue(),
                    'width' => $image->getCustomAttribute('width')->getValue(),
                    'height' => $image->getCustomAttribute('height')->getValue(),
                    'category' => $image->getCustomAttribute('category')->getValue(),
                    'keywords' => $image->getCustomAttribute('keywords')->getValue(),
                ];
            }
            return $data;
        } catch (\Exception $exception) {
            throw new SerializationException(
                __(
                    'An error occurred during related images serialization: %s',
                    $exception->getMessage()
                )
            );
        }
    }
}
