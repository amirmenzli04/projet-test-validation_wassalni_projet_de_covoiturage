<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Entity\User;
use App\Repository\ReservationRepository;
use App\Repository\TrajetRepository;
use App\Service\ReservationService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/new/{id}', name: 'reservation_new', methods: ['POST'])]
    #[IsGranted('ROLE_PASSAGER')]
    public function new(
        Trajet $trajet,
        Request $request,
        EntityManagerInterface $em,
        ReservationService $reservationService
    ): Response {
        if (!$this->isCsrfTokenValid('reservation_new', $request->request->get('_token', ''))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($trajet->getStatut() !== 'VALIDE') {
            $this->addFlash('error', 'Ce trajet n\'est pas disponible.');
            return $this->redirectToRoute('trajet_search');
        }

        try {
            $reservation = $reservationService->reserver(
                $trajet,
                $this->currentUser(),
                (int) $request->request->get('nbPlaces', 1)
            );
        } catch (\DomainException $exception) {
            $this->addFlash('error', $exception->getMessage());
            return $this->redirectToRoute('trajet_search');
        }

        $em->persist($reservation);
        $em->flush();

        $this->addFlash('success', 'Votre demande de réservation a été envoyée au conducteur.');
        return $this->redirectToRoute('trajet_search');
    }

    #[Route('/accepter/{id}', name: 'reservation_accept', methods: ['POST'])]
    #[IsGranted('ROLE_CONDUCTEUR')]
    public function accept(
        Reservation $reservation,
        Request $request,
        EntityManagerInterface $em,
        ReservationService $reservationService
    ): Response {
        $conducteur = $this->currentUser();
        $this->verifierProprietaire($reservation, $conducteur);
        $this->verifierJetonAction($reservation, 'accept', $request);

        $erreur = $this->modifierAvecVerrou($reservation, $em, function () use ($reservation, $conducteur, $reservationService): void {
            $reservationService->accepter($reservation, $conducteur);
        });
        if ($erreur === null) {
            $this->addFlash('success', 'La demande de réservation a été acceptée.');
        } else {
            $this->addFlash('error', $erreur->getMessage());
        }

        return $this->redirectToRoute('conducteur_demandes');
    }

    #[Route('/refuser/{id}', name: 'reservation_refuse', methods: ['POST'])]
    #[IsGranted('ROLE_CONDUCTEUR')]
    public function refuse(
        Reservation $reservation,
        Request $request,
        EntityManagerInterface $em,
        ReservationService $reservationService
    ): Response {
        $conducteur = $this->currentUser();
        $this->verifierProprietaire($reservation, $conducteur);
        $this->verifierJetonAction($reservation, 'refuse', $request);

        $erreur = $this->modifierAvecVerrou($reservation, $em, function () use ($reservation, $conducteur, $reservationService): void {
            $reservationService->refuser($reservation, $conducteur);
        });
        if ($erreur === null) {
            $this->addFlash('success', 'La demande de réservation a été refusée.');
        } else {
            $this->addFlash('error', $erreur->getMessage());
        }

        return $this->redirectToRoute('conducteur_demandes');
    }

    #[Route('/annuler/{id}', name: 'reservation_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_CONDUCTEUR')]
    public function cancel(
        Reservation $reservation,
        Request $request,
        EntityManagerInterface $em,
        ReservationService $reservationService
    ): Response {
        $conducteur = $this->currentUser();
        $this->verifierProprietaire($reservation, $conducteur);
        $this->verifierJetonAction($reservation, 'cancel', $request);

        $erreur = $this->modifierAvecVerrou($reservation, $em, function () use ($reservation, $conducteur, $reservationService): void {
            $reservationService->annuler($reservation, $conducteur);
        });
        if ($erreur === null) {
            $this->addFlash('success', 'Réservation annulée avec succès.');
        } else {
            $this->addFlash('error', $erreur->getMessage());
        }

        return $this->redirectToRoute('conducteur_demandes');
    }

    #[Route('/mes-reservations', name: 'mes_reservations')]
    #[IsGranted('ROLE_PASSAGER')]
    public function mesReservations(ReservationRepository $reservationRepo): Response
    {
        $reservations = $reservationRepo->findBy(
            ['passager' => $this->currentUser()],
            ['dateReservation' => 'DESC']
        );

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations
        ]);
    }

    #[Route('/new-ajax', name: 'reservation_new_ajax', methods: ['POST'])]
    #[IsGranted('ROLE_PASSAGER')]
    public function newAjax(
        Request $request,
        EntityManagerInterface $em,
        TrajetRepository $trajetRepo,
        ReservationService $reservationService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['success' => false, 'message' => 'Requête invalide'], 400);
        }

        if (!$this->isCsrfTokenValid('reservation_new', $data['_token'] ?? '')) {
            return new JsonResponse(['success' => false, 'message' => 'Jeton CSRF invalide'], 403);
        }

        $trajetId = $data['trajetId'] ?? null;
        if (!$trajetId) {
            return new JsonResponse(['success' => false, 'message' => 'Trajet non spécifié'], 400);
        }

        $trajet = $trajetRepo->find($trajetId);
        if (!$trajet) {
            return new JsonResponse(['success' => false, 'message' => 'Trajet non trouvé'], 404);
        }
        if ($trajet->getStatut() !== 'VALIDE') {
            return new JsonResponse(['success' => false, 'message' => 'Ce trajet n\'est pas disponible'], 400);
        }

        try {
            $reservation = $reservationService->reserver(
                $trajet,
                $this->currentUser(),
                (int) ($data['nbPlaces'] ?? 1)
            );
        } catch (\DomainException $exception) {
            return new JsonResponse(['success' => false, 'message' => $exception->getMessage()], 400);
        }

        $em->persist($reservation);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Votre demande de réservation a été envoyée au conducteur.',
            'reservationId' => $reservation->getId(),
            'prixTotal' => $reservation->getPrixTotal()
        ]);
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function verifierProprietaire(Reservation $reservation, User $conducteur): void
    {
        $proprietaire = $reservation->getTrajet()->getConducteur();
        if ($proprietaire !== $conducteur
            && ($proprietaire?->getId() === null || $proprietaire->getId() !== $conducteur->getId())) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier les réservations de ce trajet.');
        }
    }

    private function verifierJetonAction(Reservation $reservation, string $action, Request $request): void
    {
        if (!$this->isCsrfTokenValid(
            sprintf('reservation_%s_%d', $action, $reservation->getId()),
            $request->request->get('_token', '')
        )) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }

    private function modifierAvecVerrou(
        Reservation $reservation,
        EntityManagerInterface $em,
        callable $modifier
    ): ?\DomainException
    {
        return $em->wrapInTransaction(function () use ($reservation, $em, $modifier): ?\DomainException {
            $em->refresh($reservation, LockMode::PESSIMISTIC_WRITE);
            $em->refresh($reservation->getTrajet(), LockMode::PESSIMISTIC_WRITE);

            try {
                $modifier();
            } catch (\DomainException $exception) {
                return $exception;
            }

            return null;
        });
    }
}
