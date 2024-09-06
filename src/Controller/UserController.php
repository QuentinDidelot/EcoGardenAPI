<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AdviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class UserController extends AbstractController
{
    public function __construct(
        private AdviceRepository $adviceRepository,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private TagAwareCacheInterface $cache,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/user', name: 'app_user_create', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse {

        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
    

        $errors = $this->validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
    
        // Vérifie si le code postal est bien présent et valide
        if (!preg_match('/^\d{5}$/', $user->getPostCode())) {
            return new JsonResponse(['message' => 'Invalid postal code.'], Response::HTTP_BAD_REQUEST);
        }
    
        $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPassword);

        $user->setRoles(['ROLE_USER']);
    
        // Sauvegarder l'utilisateur
        $this->em->persist($user);
        $this->em->flush();
    
        $this->cache->invalidateTags(["usersCache"]);
    
        // Sérialiser l'utilisateur créé
        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'getUser']);
    
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }
}
