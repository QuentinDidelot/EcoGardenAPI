<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\AdviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;
use phpDocumentor\Reflection\Types\Integer;

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
     * Cette méthode permet de récupérer l'ensemble des conseils
     * 
     * /!\ /!\ /!\ Cette méthode n'est pas dans les spécifications techniques mais elle me sert de base /!\ /!\ /!\
     *
     * @return JsonResponse
     */
    #[OA\Tag(name: 'Advices')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: "Retourne la liste de tous les conseils",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Advice::class))
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Requête invalide'
    )]
    #[OA\Response(
        response: 401,
        description: 'Utilisateur non authentifié'
    )]
    #[Route('/api/advices/all', name: 'app_advice_all', methods: ['GET'])]
    public function getAllAdvices(): JsonResponse
    {
        $adviceList = $this->adviceRepository->findAll();
        $jsonAdviceList = $this->serializer->serialize($adviceList, 'json', ['groups' => 'getAdvice']);

        return new JsonResponse($jsonAdviceList, Response::HTTP_OK, [], true);
    }

    
    /**
     * Récupérer un conseil par son ID
     * 
     * /!\ /!\ /!\ Cette méthode n'est pas dans les spécifications techniques mais elle me sert de base /!\ /!\ /!\
     *
     * @param int $id The ID of the advice to retrieve.
     * @return JsonResponse A JSON response containing the requested advice.
     *     If no advice is found for the given ID, a 404 Not Found response is returned.
     *     Otherwise, a 200 OK response with the advice serialized in JSON format is returned.
     *
     */
    #[OA\Tag(name: 'Advices')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: "Retourne un conseil par son ID",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Advice::class))
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Requête invalide'
    )]
    #[OA\Response(
        response: 401,
        description: 'Utilisateur non authentifié'
    )]
    #[Route('/api/advices/{id}', name: 'app_advice_by_id', methods: ['GET'])]
    public function getAdviceById(int $id): JsonResponse
    {
        $advice = $this->adviceRepository->find($id);

        if (!$advice) {
            return new JsonResponse(['message' => 'No advice found for this ID'], Response::HTTP_NOT_FOUND);
        }
        $jsonAdvice = $this->serializer->serialize($advice, 'json', ['groups' => 'getAdvice']);

        return new JsonResponse($jsonAdvice, Response::HTTP_OK, [], true);
    }


    /**
     * Récupérer tous les conseils pour le mois en cours
     *
     * @return JsonResponse A JSON response containing all advices for the current month.
     *     The response includes HTTP status code 200 OK and the advices are serialized in JSON format.
     */
    #[OA\Tag(name: 'Advices')]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des conseils pour le mois en cours',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Advice::class))
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Requête invalide'
    )]
    #[OA\Response(
        response: 401,
        description: 'Utilisateur non authentifié'
    )]
    #[Route('/api/advices', name: 'app_advices_month', methods: ['GET'])]
    public function getAdvicesForCurrentMonth(): JsonResponse
    {
        $currentMonth = (new \DateTime())->format('m');

        $adviceList = $this->adviceRepository->findByMonth($currentMonth);
        $jsonAdviceList = $this->serializer->serialize($adviceList, 'json', ['groups' => 'getAdvice']);

        return new JsonResponse($jsonAdviceList, Response::HTTP_OK, [], true);
    }


    /**
     * Récupèrer tous les conseils d'un mois précis
     * 
     * @param int $month The month for which to retrieve advices.
     * @return JsonResponse A JSON response containing the advices for the specified month.
     *     If no advices are found for the given month, a 404 Not Found response is returned.
     */
    #[OA\Tag(name: 'Advices')]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des conseils pour un mois donné',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Advice::class))
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Requête invalide'
    )]
    #[OA\Response(
        response: 401,
        description: 'Utilisateur non authentifié'
    )]
    #[Route('/api/advices/month/{month}', name: 'app_advice_by_month', methods: ['GET'])]
    public function getAdviceByMonth(int $month): JsonResponse {

        $advice = $this->adviceRepository->findBy(['month' => $month]);

        if (!$advice) {
            return new JsonResponse(['message' => 'Aucun conseil trouvé pour ce mois'], Response::HTTP_NOT_FOUND);
        }

        $jsonAdvice = $this->serializer->serialize($advice, 'json', ['groups' => 'getAdvice']);

        return new JsonResponse($jsonAdvice, Response::HTTP_OK, [], true);
    }

    /**
     * Permet de poster un conseil pour un mois donné.
     *
     * @param Request $request La requête HTTP contenant les données du nouveau conseil.
     * @return JsonResponse La réponse HTTP contenant un message de succès ou d'erreur.
     */

     #[OA\Tag(name: 'Advices')]
     #[OA\RequestBody(
        description: 'Ajouter un conseil', 
        required: true, 
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'adviceText', description: 'Contenu du conseil', type: 'string'),
                new OA\Property(property: 'month', description: 'Mois du conseil (entre 1 et 12)', type: 'integer')
            ], type: 'object'
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Renvoie le conseil nouvellement créé',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Advice::class))
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Requête invalide'
    )]
    #[OA\Response(
        response: 401,
        description: 'Utilisateur non authentifié'
    )]
    #[Route('/api/advices', name: 'app_advice_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour ajouter un conseil')]
    public function postAdvice(Request $request): JsonResponse{
    
        $advice = $this->serializer->deserialize($request->getContent(), Advice::class, 'json');
    
        $errors = $this->validator->validate($advice);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
    
        $this->entityManager->persist($advice);
        $this->entityManager->flush();
    
        return new JsonResponse(['message' => 'Conseil ajouté avec succès'], Response::HTTP_CREATED);
    }
    

    /**
     * Met à jour un conseil existant avec son ID.
     *
     *
     * @param Request $request La requête HTTP contenant les données mises à jour du conseil.
     * @param Advice $currentAdvice Le conseil existant à mettre à jour.
     * @return JsonResponse La réponse HTTP contenant un message de succès ou d'erreur.
     */
    #[OA\Tag(name: 'Advices')]
    #[OA\RequestBody(
        description: 'Ajouter un conseil', 
        required: false, 
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'adviceText', description: 'Contenu du conseil', type: 'string'),
                new OA\Property(property: 'month', description: 'Mois du conseil (entre 1 et 12)', type: 'integer')
            ], type: 'object'
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Created Successfully'
    )]
    #[OA\Response(
        response: 400,
        description: 'Requête invalide'
    )]
    #[OA\Response(
        response: 401,
        description: 'Utilisateur non authentifié'
    )]
    #[Route('/api/advices/{id}', name:"updateAdvice", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un conseil')]
    public function updateAdvice(Request $request, Advice $currentAdvice): JsonResponse {

        $updatedAdvice = $this->serializer->deserialize($request->getContent(), Advice::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAdvice]);

        $content = $request->toArray();

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
     * Supprime un conseil avec son ID
     *
     *
     * @param int $id The ID of the advice to delete.
     * @param AdviceRepository $adviceRepository The repository for managing Advice entities.
     * @param EntityManagerInterface $entityManager The entity manager for database operations.
     *
     * @return JsonResponse A JSON response indicating the success or failure of the operation.
     *     If the advice is not found, a 404 Not Found response is returned.
     *     If the advice is successfully deleted, a 200 OK response with a success message is returned.
     */
    #[OA\Tag(name: 'Advices')]
    #[OA\Response(
        response: 204,
        description: 'No Content'
    )]
    #[Route('/api/advices/{id}', name: "deleteAdvice", methods:['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un conseil')]
    public function deleteAdvice(int $id, AdviceRepository $adviceRepository, EntityManagerInterface $entityManager): JsonResponse {

        $advice = $adviceRepository->find($id);

        if (!$advice) {
            return new JsonResponse(['message' => 'Conseil introuvable'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($advice);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Conseil supprimé avec succès'], Response::HTTP_OK);
    }

}