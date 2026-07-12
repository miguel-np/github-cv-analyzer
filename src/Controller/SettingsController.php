<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\GithubAccount;
use App\Entity\User;
use App\Repository\GithubAccountRepository;
use App\Repository\UserRepository;
use App\Service\GitHub\GitHubClient;
use App\Service\GitHub\GitHubSyncService;
use App\Service\GitHub\TokenEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SettingsController extends AbstractController
{
    #[Route('/settings', name: 'app_settings')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        TokenEncryptionService $tokenEncryption,
        GitHubClient $gitHubClient,
        GitHubSyncService $syncService,
        GithubAccountRepository $accountRepo,
        UserRepository $userRepo,
    ): Response {
        $user = $this->getOrCreateUser($em, $userRepo);
        $account = $accountRepo->findOneBy(['user' => $user]);

        $verificationResult = $request->getSession()->get('github_verification');
        $request->getSession()->remove('github_verification');

        if ($request->isMethod('POST') && $this->isCsrfTokenValid('settings', $request->request->get('_token'))) {
            $token = $request->request->get('github_token', '');

            if (empty(trim($token))) {
                $this->addFlash('error', 'El token de GitHub no puede estar vacío.');

                return $this->redirectToRoute('app_settings');
            }

            if (!$account) {
                $account = new GithubAccount();
                $account->setUser($user);
                $em->persist($account);
            }

            $account->setEncryptedToken($tokenEncryption->encrypt($token));

            try {
                $gitHubClient->authenticate($account->getEncryptedToken());
                $username = $gitHubClient->getCurrentUsername();
                $account->setGithubUsername($username);
                $em->flush();

                $repos = $syncService->syncRepositories($account);

                $this->addFlash('success', sprintf(
                    'Cuenta conectada como **%s**. %d repositorios sincronizados.',
                    $username,
                    count($repos)
                ));
            } catch (\Throwable $e) {
                $em->clear();
                $this->addFlash('error', 'Error al conectar con GitHub: ' . $e->getMessage());
            }

            return $this->redirectToRoute('app_settings');
        }

        return $this->render('settings/index.html.twig', [
            'account' => $account,
            'verification' => $verificationResult,
        ]);
    }

    #[Route('/settings/verify', name: 'app_settings_verify', methods: ['POST'])]
    public function verify(
        Request $request,
        TokenEncryptionService $tokenEncryption,
        GitHubClient $gitHubClient,
    ): Response {
        $token = $request->request->get('github_token', '');

        if (empty(trim($token)) || !$this->isCsrfTokenValid('verify', $request->request->get('_token'))) {
            return $this->redirectToRoute('app_settings');
        }

        try {
            $encrypted = $tokenEncryption->encrypt($token);
            $gitHubClient->authenticate($encrypted);
            $username = $gitHubClient->getCurrentUsername();

            $request->getSession()->set('github_verification', [
                'success' => true,
                'username' => $username,
            ]);
        } catch (\Throwable $e) {
            $request->getSession()->set('github_verification', [
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->redirectToRoute('app_settings');
    }

    private function getOrCreateUser(EntityManagerInterface $em, UserRepository $userRepo): User
    {
        $users = $userRepo->findAll();

        if (count($users) > 0) {
            return $users[0];
        }

        $user = new User();
        $user->setEmail('default@local.dev');
        $em->persist($user);
        $em->flush();

        return $user;
    }
}
