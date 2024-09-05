<?php

namespace CardPrinterService\Service;

use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Storage\StorageObject;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Security;
use Ubitransport\KeycloakSecurityBundle\Security\UserDto;

class GCSFileUploader
{
    public function __construct(private readonly Security $security, private string $gcsProjectId, private string $gcsBucketName)
    {
    }

    public function upload(UploadedFile $uploadedFile): StorageObject
    {
        $bucket = (new StorageClient(['projectId' => $this->gcsProjectId]))->bucket($this->gcsBucketName);
        /** @var StreamInterface|resource|string|null $file */
        $file = fopen($uploadedFile->getRealPath(), 'r');
        /** @var UserDto $customer */
        $customer = $this->security->getUser();
        $fileName = sprintf(
            '%s-%s.%s',
            strtolower($customer->getCustomer()),
            time(),
            pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_EXTENSION)
        );

        /* @var StorageObject $storageObject */
        return $bucket->upload($file, [
            'name' => $fileName,
        ]);
    }
}
