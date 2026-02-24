<?php
namespace App\MessageHandler;

use App\Message\GenerateEmbeddingMessage;
use App\Repository\OeuvreRepository;
use App\Service\EmbeddingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class GenerateEmbeddingHandler implements MessageHandlerInterface
{
    public function __construct(
        private OeuvreRepository $repository,
        private EntityManagerInterface $em,
        private EmbeddingService $embeddingService
    ) {}

    public function __invoke(GenerateEmbeddingMessage $message)
    {
        $oeuvre = $this->repository->find($message->getOeuvreId());
        if (!$oeuvre) return;

        $text = trim($oeuvre->getTitre() . ' ' . $oeuvre->getDescription());
        $embedding = $this->embeddingService->embed($text);

        $oeuvre->setEmbedding($embedding);
        $this->em->persist($oeuvre);
        $this->em->flush();
    }
}