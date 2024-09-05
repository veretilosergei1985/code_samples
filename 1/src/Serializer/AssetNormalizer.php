<?php

namespace CardPrinterService\Serializer;

use CardPrinterService\Entity\Asset;
use Google\Cloud\Storage\StorageClient;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AssetNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'ASSET_NORMALIZER_ALREADY_CALLED';

    public function __construct(private string $gcsProjectId, private string $gcsBucketName)
    {
    }

    public function normalize($object, string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        /** @var Asset $asset */
        $asset = $object;
        $context[self::ALREADY_CALLED] = true;

        $storage = new StorageClient(['projectId' => $this->gcsProjectId]);
        $bucket = $storage->bucket($this->gcsBucketName);
        /** @var string $assetDocument */
        $assetDocument = $asset->getFilePath();
        $object = $bucket->object($assetDocument);

        $asset->signedUrl = $object->signedUrl(new \DateTime('5 min'), [
            'version' => 'v4',
        ]);

        return $this->normalizer->normalize($asset, $format, $context);
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Asset;
    }
}
