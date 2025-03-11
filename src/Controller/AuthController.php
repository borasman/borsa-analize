<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @Route("/api")
 */
class AuthController extends AbstractController
{
    private $userRepository;
    private $passwordHasher;
    private $entityManager;
    private $serializer;
    private $validator;
    private $requestStack;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        RequestStack $requestStack
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/login", name="app_login", methods={"GET", "POST"})
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // For API mode, return JSON response if requested
        if ($this->isRequestingJson()) {
            if ($error) {
                return $this->json([
                    'error' => $error->getMessage()
                ], Response::HTTP_UNAUTHORIZED);
            }
            return $this->json([
                'last_username' => $lastUsername,
                'error' => $error
            ]);
        }

        // For web interface, render the login template
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * @Route("/logout", name="app_logout", methods={"GET"})
     */
    public function logout(): void
    {
        // Bu metod hiçbir zaman çalıştırılmaz, çünkü logout işlemi
        // Symfony'nin güvenlik sistemi tarafından ele alınır
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Helper method to determine if the request is asking for JSON
     */
    private function isRequestingJson(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request && (
            $request->isXmlHttpRequest() || // AJAX request
            $request->getRequestFormat() === 'json' || // Requested format
            $request->getContentType() === 'json' || // Content type
            strpos($request->headers->get('Accept'), 'application/json') !== false // Accept header
        );
    }

    /**
     * @Route("/register", name="api_register", methods={"POST"})
     */
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['firstName']) || !isset($data['lastName']) || !isset($data['recaptcha'])) {
            return $this->json(['message' => 'Eksik alanlar. Email, şifre, ad, soyad ve reCAPTCHA gereklidir.'], Response::HTTP_BAD_REQUEST);
        }

        // Verify reCAPTCHA
        $recaptcha = $data['recaptcha'];
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $recaptchaData = [
            'secret' => $_ENV['GOOGLE_RECAPTCHA_SECRET_KEY'],
            'response' => $recaptcha
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($recaptchaData)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $resultJson = json_decode($result);

        if (!$resultJson->success) {
            return $this->json(['message' => 'reCAPTCHA doğrulaması başarısız oldu.'], Response::HTTP_BAD_REQUEST);
        }

        // Check if user already exists
        $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['message' => 'Bu email adresi zaten kullanılıyor.'], Response::HTTP_CONFLICT);
        }

        // Create new user
        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        $user->setRoles(['ROLE_USER']);
        $user->setCreatedAt(new \DateTime());

        // Validate user
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['message' => 'Doğrulama hatası', 'errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        // Save user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'Kullanıcı başarıyla oluşturuldu.'], Response::HTTP_CREATED);
    }

    /**
     * @Route("/register-form", name="app_register", methods={"GET"})
     */
    public function registerForm(): Response
    {
        return $this->render('security/register.html.twig');
    }

    /**
     * @Route("/profile", name="api_profile", methods={"GET"})
     */
    public function profile(Security $security): JsonResponse
    {
        /** @var User $user */
        $user = $security->getUser();
        if (!$user) {
            return $this->json(['message' => 'Kullanıcı bulunamadı.'], Response::HTTP_UNAUTHORIZED);
        }

        $userData = $this->serializer->normalize($user, null, ['groups' => 'user:read']);

        return $this->json($userData);
    }

    /**
     * @Route("/profile", name="api_profile_update", methods={"PUT"})
     */
    public function updateProfile(Request $request, Security $security): JsonResponse
    {
        /** @var User $user */
        $user = $security->getUser();
        if (!$user) {
            return $this->json(['message' => 'Kullanıcı bulunamadı.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }

        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        }

        // Validate user
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['message' => 'Doğrulama hatası', 'errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        // Save user
        $this->entityManager->flush();

        return $this->json(['message' => 'Profil başarıyla güncellendi.']);
    }
} 