<?php

namespace App\Controller;

use App\Repository\AdviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class AdviceController extends AbstractController
{

    public function __construct(
        private AdviceRepository $adviceRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer)
    {
    }

    /**
     * Récupèrer tous les conseils
     * 
     * /!\ /!\ /!\ Cette méthode n'est pas dans les spécifications techniques mais elle me sert de base /!\ /!\ /!\
     */
    #[Route('/api/advices/all', name: 'app_advice_all', methods: ['GET'])]
    public function getAllAdvices(): JsonResponse
    {
        $adviceList = $this->adviceRepository->findAll();
        $jsonAdviceList = $this->serializer->serialize($adviceList, 'json');

        return new JsonResponse($jsonAdviceList, Response::HTTP_OK, [], true);
    }

    /**
     * Récupérer tous les conseils pour le mois en cours
     */
    #[Route('/api/advices', name: 'app_advices_month', methods: ['GET'])]
    public function getAdvicesForCurrentMonth(): JsonResponse
    {
        $currentMonth = (new \DateTime())->format('m');
    
        $adviceList = $this->adviceRepository->findByMonth($currentMonth);
        $jsonAdviceList = $this->serializer->serialize($adviceList, 'json');
    
        return new JsonResponse($jsonAdviceList, Response::HTTP_OK, [], true);
    }

    /**
     * Récupèrer tous les conseils d'un mois précis
     */
    #[Route('/api/advices/{month}', name: 'app_advice_by_month', methods: ['GET'])]
    public function getAdviceByMonth(int $month): JsonResponse {
        $advice = $this->adviceRepository->findOneBy(['month' => $month]);

        if (!$advice) {
            return new JsonResponse(['message' => 'Aucun conseil trouvé pour ce mois'], Response::HTTP_NOT_FOUND);
        }

        $jsonAdvice = $this->serializer->serialize($advice, 'json');

        return new JsonResponse($jsonAdvice, Response::HTTP_OK, [], true);
    }
}
