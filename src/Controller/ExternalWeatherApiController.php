<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

class ExternalWeatherApiController extends AbstractController
{
    private CacheInterface $cache;
    private HttpClientInterface $httpClient;
    private SerializerInterface $serializer;

    public function __construct(CacheInterface $cache, HttpClientInterface $httpClient, SerializerInterface $serializer)
    {
        $this->cache = $cache;
        $this->httpClient = $httpClient;
        $this->serializer = $serializer;
    }

    #[Route('/api/weather', name: 'weatherByCurrentUser', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Retourne la météo pour la ville de l\'utilisateur authentifié'
    )]
    #[OA\Tag(name: 'Weather')]
    public function getWeatherByCurrentUser(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Utilisateur invalide'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    
        $postCode = $user->getPostCode();
        if ($postCode === null) {
            return new JsonResponse(['message' => 'Code postal non défini'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $postCodeString = (string)$postCode;
        $apiKey = $this->getParameter('openweathermap_api_key');

        $cacheKey = "weather_$postCodeString";
        $jsonWeather = $this->cache->get($cacheKey, function ($item) use ($postCodeString, $apiKey) {
            $item->expiresAfter(3600);
        
            $response = $this->httpClient->request('GET', 'http://api.openweathermap.org/data/2.5/weather', [
                'query' => [
                    'q' => $postCodeString,
                    'appid' => $apiKey,
                    'units' => 'metric',
                ],
            ]);
        
            if ($response->getStatusCode() === 200) {
                return $response->getContent();
            }
        
            return json_encode(['message' => 'Erreur lors de la récupération des données météo']);
        });

        return new JsonResponse($jsonWeather, JsonResponse::HTTP_OK, [], true);
    }

    
    #[OA\Tag(name: 'Weather')]
    #[OA\Get(
        path: '/api/weather/{city}',
        summary: 'Retourne la météo pour une ville donnée',
        parameters: [
            new OA\Parameter(
                name: 'city',
                in: 'path',
                required: true,
                description: 'Nom de la ville pour laquelle obtenir la météo',
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Retourne la météo pour la ville spécifiée'
            ),
            new OA\Response(
                response: 400,
                description: 'Requête invalide'
            ),
            new OA\Response(
                response: 404,
                description: 'Ville non trouvée'
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur'
            )
        ]
    )]
    #[Route('/api/weather/{city}', name: 'weatherByCity', methods: ['GET'])]
    public function getWeatherByCity(string $city): JsonResponse
    {
        $apiKey = $this->getParameter('openweathermap_api_key');

        $cacheKey = "weather_$city";
        $jsonWeather = $this->cache->get($cacheKey, function ($item) use ($city, $apiKey) {
            $item->expiresAfter(3600);

            $response = $this->httpClient->request('GET', 'http://api.openweathermap.org/data/2.5/weather', [
                'query' => [
                    'q' => $city,
                    'appid' => $apiKey,
                    'units' => 'metric',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return $response->getContent();
            }

            return json_encode(['message' => 'Erreur lors de la récupération des données météo']);
        });

        return new JsonResponse($jsonWeather, JsonResponse::HTTP_OK, [], true);
    }
}
