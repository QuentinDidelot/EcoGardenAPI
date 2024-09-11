<?php


namespace App\Controller;

use App\Entity\User;
use App\Repository\AdviceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use OpenApi\Attributes as OA;

class UserController extends AbstractController
{
    public function __construct(
        private AdviceRepository $adviceRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private TagAwareCacheInterface $cache,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * Permet de créer un nouveal utilisateur
     *
     * @param Request $request The request object containing the user data.
     *
     * @return JsonResponse The response containing the created user data or an error message.
     */
    #[OA\Tag(name: 'Users')]
    #[OA\RequestBody(
        description: 'Permets de créer un nouvel utilisateur', 
        required: true, 
        content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'email', description: 'Adresse email', type: 'string'),
            new OA\Property(property: 'password', description: 'Mot de passe', type: 'string'),
            new OA\Property(property: 'postcode', description: 'Code postal', type: 'integer'),
        ], type: 'object'
    )
    )]
    #[OA\Response(
        response: 204,
        description: 'No Content'
    )]
    #[Route('/api/user', name: 'app_user_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un profil utilisateur')]
    public function createUser(Request $request): JsonResponse {

        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');


        $errors = $this->validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Vérifie si le code postal est présent et s'il est valide
        if (!preg_match('/^\d{5}$/', $user->getPostCode())) {
            return new JsonResponse(['message' => 'Le code postal est invalide, il doit être sous le format : 10000'], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPassword);

        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->cache->invalidateTags(["usersCache"]);

        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'getUser']);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }


    /**
     * Permet de modifier un profil utilisateur
     *
     * @param User $user The user entity to update.
     * @param Request $request The request object containing the updated user data.
     * @param int $id The unique identifier of the user to update.
     *
     * @return JsonResponse The response containing the updated user data or an error message.
     */
    #[OA\Tag(name: 'Users')]
    #[OA\RequestBody(
        description: 'Modifier un profil utilisateur', 
        required: false, 
        content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'email', description: 'Adresse email', type: 'string'),
            new OA\Property(property: 'roles', description: 'Role ["ROLE_USER"] ou ["ROLE_ADMIN"]', type: 'array', items: new OA\Items(type: 'string')),
            new OA\Property(property: 'password', description: 'Mot de passe', type: 'string'),
            new OA\Property(property: 'postcode', description: 'Code Postal', type: 'integer'),
        ], type: 'object'
    )
    )]
    #[OA\Response(
        response: 204,
        description: 'No Content'
    )]
    #[OA\Response(
        response: 400,
        description: 'Requête invalide'
    )]
    #[OA\Response(
        response: 401,
        description: 'Utilisateur non authentifié'
    )]
    #[Route('/api/user/{id}', name: 'app_user_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un profil utilisateur')]
    public function updateUser(User $user, Request $request, int $id): JsonResponse {

        $user = $this->userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $this->serializer->deserialize($request->getContent(), User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);

        $errors = $this->validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Vérifie si le code postal est bien présent et valide
        if (!preg_match('/^\d{5}$/', $user->getPostCode())) {
            return new JsonResponse(['message' => 'Le code postal est invalide, il doit être sous le format : XXXXX'], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        $this->cache->invalidateTags(["usersCache"]);

        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'getUser']);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);

        }

    /**
     * Permet de supprimer un utilisateur
     *
     * @Route("/api/user/{id}", name="app_user_delete", methods={"DELETE"})
     * @IsGranted("ROLE_ADMIN", message="Vous n'avez pas les droits suffisants pour supprimer un utilisateur")
     *
     * @param User $user The user entity to delete.
     * @param int $id The unique identifier of the user to delete.
     *
     * @return JsonResponse The response containing a success message or an error message if the user is not found.
     */
    #[OA\Tag(name: 'Users')]
    #[OA\Response(
        response: 204,
        description: 'Utilisateur supprimé'
    )]
    #[OA\Response(
        response: 400,
        description: 'Requête invalide'
    )]
    #[OA\Response(
        response: 401,
        description: 'Utilisateur non authentifié'
    )]
    #[Route('/api/user/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un utilisateur')]
    public function deleteUser(User $user, int $id): JsonResponse {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->cache->invalidateTags(["usersCache"]);

        return new JsonResponse(['message' => 'Utilisateur supprimé'], Response::HTTP_OK);
    }
}
