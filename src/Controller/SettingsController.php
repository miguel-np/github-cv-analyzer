<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\GithubAccount;
use App\Entity\User;
use App\Message\SyncAccountMessage;
use App\Repository\GithubAccountRepository;
use App\Repository\SyncJobRepository;
use App\Repository\UserRepository;
use App\Service\GitHub\GitHubClient;
use App\Service\GitHub\TokenEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Github\Exception\RuntimeException as GithubRuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class SettingsController extends AbstractController
{
    private const EMAIL_DEFAULT = 'default@local.dev';

    #[Route('/settings', name: 'app_settings')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        TokenEncryptionService $tokenEncryption,
        GitHubClient $gitHubClient,
        GithubAccountRepository $accountRepo,
        UserRepository $userRepo,
        SyncJobRepository $jobRepo,
        MessageBusInterface $bus,
    ): Response {
        $user = $this->getOrCreateUser($em, $userRepo);
        $account = $accountRepo->findOneBy(['user' => $user]);

        $verificationResult = $request->getSession()->get('github_verification');
        $request->getSession()->remove('github_verification');

        if ($request->isMethod('POST') && $this->isCsrfTokenValid('settings', $request->request->get('_token'))) {
            $token = trim($request->request->get('github_token', ''));

            if ($token === '') {
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

                $bus->dispatch(new SyncAccountMessage($account->getId()));

                $this->addFlash('success', sprintf(
                    'Cuenta conectada como %s. Sincronización iniciada en segundo plano.',
                    $username
                ));
            } catch (GithubRuntimeException $e) {
                $this->addFlash('error', 'Credenciales de GitHub inválidas. Verifica el token.');
            } catch (\RuntimeException $e) {
                $this->addFlash('error', 'Error de conexión con GitHub. Inténtalo de nuevo.');
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Error inesperado al sincronizar.');
            }

            return $this->redirectToRoute('app_settings');
        }

        $jobs = $account ? $jobRepo->findBy(
            ['githubAccount' => $account],
            ['createdAt' => 'DESC'],
            5
        ) : [];

        return $this->render('settings/index.html.twig', [
            'account' => $account,
            'verification' => $verificationResult,
            'syncJobs' => $jobs,
        ]);
    }

    #[Route('/settings/verify', name: 'app_settings_verify', methods: ['POST'])]
    public function verify(
        Request $request,
        TokenEncryptionService $tokenEncryption,
        GitHubClient $gitHubClient,
    ): Response {
        $token = trim($request->request->get('github_token', ''));

        if ($token === '' || !$this->isCsrfTokenValid('verify', $request->request->get('_token'))) {
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
        } catch (GithubRuntimeException) {
            $request->getSession()->set('github_verification', [
                'success' => false,
                'error' => 'Token inválido o expirado.',
            ]);
        } catch (\Throwable) {
            $request->getSession()->set('github_verification', [
                'success' => false,
                'error' => 'Error de conexión con GitHub.',
            ]);
        }

        return $this->redirectToRoute('app_settings');
    }

    #[Route('/settings/resync', name: 'app_settings_resync', methods: ['POST'])]
    public function resync(
        GithubAccountRepository $accountRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        MessageBusInterface $bus,
        Request $request,
    ): Response {
        if (!$this->isCsrfTokenValid('resync', $request->request->get('_token'))) {
            return $this->redirectToRoute('app_settings');
        }

        $user = $this->getOrCreateUser($em, $userRepo);
        $account = $accountRepo->findOneBy(['user' => $user]);

        if ($account) {
            $bus->dispatch(new SyncAccountMessage($account->getId()));
            $this->addFlash('success', 'Re-synchronization started.');
        }

        return $this->redirectToRoute('app_settings');
    }

    private function getOrCreateUser(EntityManagerInterface $em, UserRepository $userRepo): User
    {
        $user = $userRepo->findOneBy([]);

        if ($user !== null) {
            return $user;
        }

        $user = new User();
        $user->setEmail(self::EMAIL_DEFAULT);
        $em->persist($user);
        $em->flush();

        return $user;
    }
}
