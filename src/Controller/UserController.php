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
     */
    #[Route('/api/user', name: 'app_user_create', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse {

        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
    

        $errors = $this->validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
    
        // Vérifie si le code postal est bien présent et valide
        if (!preg_match('/^\d{5}$/', $user->getPostCode())) {
            return new JsonResponse(['message' => 'Le code postal est invalide, il doit être sous le format : 10000'], Response::HTTP_BAD_REQUEST);
        }
    
        $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPassword);

        $user->setRoles(['ROLE_USER']);
    
        // Sauvegarder l'utilisateur
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    
        $this->cache->invalidateTags(["usersCache"]);
    
        // Sérialiser l'utilisateur créé
        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'getUser']);
    
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }

    /**
     * Permet de modifier un profil utilisateur
     */
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

        // Vérifier si le code postal est bien présent et valide
        if (!preg_match('/^\d{5}$/', $user->getPostCode())) {
            return new JsonResponse(['message' => 'Le code postal est invalide, il doit être sous le format : 10000'], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        $this->cache->invalidateTags(["usersCache"]);

        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'getUser']);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);

        }

    /**
     * Permet de supprimer un utilisateur
     */
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
