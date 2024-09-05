<?php

namespace CardPrinterService\Controller;

use CardPrinterService\Entity\Template;
use CardPrinterService\Repository\TemplateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
final class ToggleTemplateAction extends AbstractController
{
    public function __construct(
        protected TemplateRepository $templateRepository
    ) {
    }

    public function __invoke(Request $request): Template
    {
        $template = $this->templateRepository->find($request->get('id'));

        if (!$template) {
            throw new NotFoundHttpException('Template not found.');
        }

        if ($template->getIsEnabled()) {
            $template->setIsEnabled(false);
        } else {
            $this->templateRepository->disableTemplates(
                $template->getType(),
                $template->getProduct()
            );
            $template->setIsEnabled(true);
        }
        $this->templateRepository->save($template, true);

        return $template;
    }
}
