<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\AdviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AdviceController extends AbstractController
{

    public function __construct(
        private AdviceRepository $adviceRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator)
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
     * Récupérer un conseil par son ID
     * 
     * /!\ /!\ /!\ Cette méthode n'est pas dans les spécifications techniques mais elle me sert de base /!\ /!\ /!\
     */
    #[Route('/api/advices/{id}', name: 'app_advice_by_id', methods: ['GET'])]
    public function getAdviceById(int $id): JsonResponse
    {
        $advice = $this->adviceRepository->find($id);

        if (!$advice) {
            return new JsonResponse(['message' => 'Aucun conseil trouvé pour cet ID'], Response::HTTP_NOT_FOUND);
        }
        $jsonAdvice = $this->serializer->serialize($advice, 'json');

        return new JsonResponse($jsonAdvice, Response::HTTP_OK, [], true);
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
    #[Route('/api/advices/month/{month}', name: 'app_advice_by_month', methods: ['GET'])]
    public function getAdviceByMonth(int $month): JsonResponse {

        $advice = $this->adviceRepository->findBy(['month' => $month]);

        if (!$advice) {
            return new JsonResponse(['message' => 'Aucun conseil trouvé pour ce mois'], Response::HTTP_NOT_FOUND);
        }

        $jsonAdvice = $this->serializer->serialize($advice, 'json');

        return new JsonResponse($jsonAdvice, Response::HTTP_OK, [], true);
    }

    /**
     * Permet de poster un conseil pour un mois donné
     */
    #[Route('/api/advices', name: 'app_advice_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un conseil')]
    public function postAdvice(Request $request): JsonResponse{

        $advice = $this->serializer->deserialize($request->getContent(), Advice::class, 'json');

        $errors = $this->validator->validate($advice);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $this->entityManager->persist($advice);
        $this->entityManager->flush();

        return new JsonResponse($this->serializer->serialize(['message' => 'Conseil ajouté avec succès'], 'json'), Response::HTTP_CREATED, [], true);

    }

    /**
     * Permet de modifier un conseil avec son ID 
     */
    #[Route('/api/advices/{id}', name:"updateAdvice", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un conseil')]
    public function updateAdvice(Request $request, Advice $currentAdvice): JsonResponse {

        $updatedAdvice = $this->serializer->deserialize($request->getContent(), Advice::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAdvice]);

        $content = $request->toArray();

        // Mise à jour conditionnelle des champs en fonction de leur présence dans la requête
        if (isset($content['adviceText'])) {
            $updatedAdvice->setAdviceText($content['adviceText']);
        }

        if (isset($content['month'])) {
            $updatedAdvice->setMonth($content['month']);
        }

        // Validation des erreurs de l'entité modifiée
        $errors = $this->validator->validate($updatedAdvice);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $this->entityManager->persist($updatedAdvice);
        $this->entityManager->flush();

         return new JsonResponse($this->serializer->serialize(['message' => 'Conseil modifié avec succès'], 'json'), Response::HTTP_CREATED, [], true);
    }

    /**
     * Permet de supprimer un conseil avec son ID
     */
    #[Route('/api/advices/{id}', name: "deleteAdvice", methods:['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un conseil')]
    public function deleteAdvice(int $id, AdviceRepository $adviceRepository, EntityManagerInterface $entityManager): JsonResponse {

        $advice = $adviceRepository->find($id);


        if (!$advice) {
            return new JsonResponse(['message' => 'Conseil introuvable'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($advice);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Conseil supprimé avec succès'], Response::HTTP_OK, [], true);
    }

}