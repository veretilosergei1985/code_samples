<?php

namespace CardPrinterService\Service\Provider;

use CardPrinterService\Repository\AssetRepository;
use Google\Cloud\Storage\StorageClient;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AssetMediaProvider
{
    public function __construct(
        private string $gcsProjectId,
        private string $gcsBucketName,
        private AssetRepository $assetRepository
    ) {
    }

    public function getAssetMediaSignUrl(string $assetId): string
    {
        $asset = $this->assetRepository->find($assetId);

        if (!$asset) {
            throw new NotFoundHttpException(sprintf('Asset #%s not found', $assetId));
        }

        $storage = new StorageClient(['projectId' => $this->gcsProjectId]);
        $bucket = $storage->bucket($this->gcsBucketName);
        /** @var string $assetDocument */
        $assetDocument = $asset->getFilePath();
        $object = $bucket->object($assetDocument);

        return $object->signedUrl(new \DateTime('5 min'), [
            'version' => 'v4',
        ]);
    }
}
