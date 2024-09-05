<?php

namespace CardPrinterService\Controller;

use CardPrinterService\Entity\Asset;
use CardPrinterService\Service\GCSFileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
final class CreateAssetAction extends AbstractController
{
    public function __construct(private GCSFileUploader $fileUploader)
    {
    }

    public function __invoke(Request $request): Asset
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('file');

        if ($uploadedFile == null) {
            throw new BadRequestHttpException('"file" is required');
        }

        $storageObject = $this->fileUploader->upload($uploadedFile);

        $asset = new Asset();
        $asset->file = $uploadedFile;
        $asset->filePath = $storageObject->name();

        return $asset;
    }
}
