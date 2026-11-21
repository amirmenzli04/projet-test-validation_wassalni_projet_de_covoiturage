<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\Trajet;
use App\Entity\User;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * API REST des réclamations (JSON).
 *
 * Authentification : session de l'application (formulaire de connexion).
 * Protection CSRF : les requêtes qui modifient des données doivent porter « Content-Type: application/json »
 * (sinon 415). Un formulaire d'un site tiers ne peut pas envoyer ce type de contenu sans autorisation CORS.
 *
 * Cycle de vie exposé : EN_ATTENTE → EN_COURS → TRAITEE, ou EN_ATTENTE → TRAITEE (traitement direct).
 * Une réclamation TRAITEE est close : toute nouvelle modification est refusée (409).
 */
#[Route('/api/reclamations')]
class ApiReclamationController extends AbstractController
{
    private const TRANSITIONS = [
        'EN_ATTENTE' => ['EN_COURS', 'TRAITEE'],
        'EN_COURS' => ['TRAITEE'],
        'TRAITEE' => [],
    ];

    #[Route('', name: 'api_reclamations_list', methods: ['GET'])]
    public function list(Request $request, ReclamationRepository $repository): JsonResponse
    {
        if ($refus = $this->refuserSiAnonyme()) {
            return $refus;
        }

        $qb = $repository->createQueryBuilder('r')->orderBy('r.dateCreation', 'DESC')->addOrderBy('r.id', 'DESC');
        if (!$this->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('r.user = :auteur')->setParameter('auteur', $this->getUser());
        }

        $statut = $request->query->get('statut');
        if ($statut !== null && $statut !== '') {
            if (!array_key_exists($statut, self::TRANSITIONS)) {
                return $this->erreur(400, 'Statut inconnu. Valeurs possibles : ' . implode(', ', array_keys(self::TRANSITIONS)));
            }
            $qb->andWhere('r.statut = :statut')->setParameter('statut', $statut);
        }

        foreach (['dateMin' => '>=', 'dateMax' => '<'] as $parametre => $operateur) {
            $valeur = $request->query->get($parametre);
            if ($valeur === null || $valeur === '') {
                continue;
            }
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $valeur);
            if ($date === false || $date->format('Y-m-d') !== $valeur) {
                return $this->erreur(400, "Paramètre $parametre invalide : format attendu AAAA-MM-JJ");
            }
            if ($parametre === 'dateMax') {
                $date = $date->modify('+1 day'); // date de fin incluse
            }
            $qb->andWhere("r.dateCreation $operateur :$parametre")->setParameter($parametre, $date);
        }

        $reclamations = array_map(fn (Reclamation $r) => $this->versTableau($r), $qb->getQuery()->getResult());

        return $this->json(['total' => count($reclamations), 'reclamations' => $reclamations]);
    }

    #[Route('/{id}', name: 'api_reclamations_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, ReclamationRepository $repository): JsonResponse
    {
        if ($refus = $this->refuserSiAnonyme()) {
            return $refus;
        }
        $reclamation = $repository->find($id);
        if ($reclamation === null) {
            return $this->erreur(404, 'Réclamation introuvable');
        }
        if (!$this->isGranted('ROLE_ADMIN') && $reclamation->getUser() !== $this->getUser()) {
            return $this->erreur(403, 'Accès refusé : cette réclamation ne vous appartient pas');
        }

        return $this->json($this->versTableau($reclamation));
    }

    #[Route('', name: 'api_reclamations_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        if ($refus = $this->refuserSiAnonyme()) {
            return $refus;
        }
        $donnees = $this->lireJson($request);
        if ($donnees instanceof JsonResponse) {
            return $donnees;
        }

        $erreurs = [];
        $sujet = $donnees['sujet'] ?? null;
        if (!is_string($sujet) || !in_array($sujet, Reclamation::SUJETS, true)) {
            $erreurs['sujet'] = 'Sujet obligatoire, à choisir parmi : ' . implode(' ; ', Reclamation::SUJETS);
        }
        $message = $donnees['message'] ?? null;
        if (!is_string($message)) {
            $erreurs['message'] = 'Le message est obligatoire';
        }

        $trajet = null;
        if (isset($donnees['trajetId'])) {
            $trajetId = filter_var($donnees['trajetId'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $trajet = $trajetId === false ? null : $em->find(Trajet::class, $trajetId);
            if ($trajet === null) {
                $erreurs['trajetId'] = 'Trajet introuvable';
            }
        }

        $reclamation = (new Reclamation())
            ->setUser($this->getUser())
            ->setSujet(is_string($sujet) ? $sujet : '')
            ->setMessage(is_string($message) ? $message : '')
            ->setStatut('EN_ATTENTE')
            ->setTrajetConcerne($trajet);

        // Mêmes règles que le formulaire HTML : contraintes de l'entité (message d'au moins 10 caractères…)
        foreach ($validator->validate($reclamation) as $violation) {
            $erreurs[$violation->getPropertyPath()] ??= $violation->getMessage();
        }
        if ($erreurs !== []) {
            return $this->json(['erreur' => 'Données invalides', 'erreurs' => $erreurs], 422);
        }

        $em->persist($reclamation);
        $em->flush();

        $url = $this->generateUrl('api_reclamations_show', ['id' => $reclamation->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);

        return $this->json($this->versTableau($reclamation), 201, ['Location' => $url]);
    }

    #[Route('/{id}', name: 'api_reclamations_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(int $id, Request $request, ReclamationRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        if ($refus = $this->refuserSiAnonyme()) {
            return $refus;
        }
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->erreur(403, 'Seul un administrateur peut traiter une réclamation');
        }
        $reclamation = $repository->find($id);
        if ($reclamation === null) {
            return $this->erreur(404, 'Réclamation introuvable');
        }
        $donnees = $this->lireJson($request);
        if ($donnees instanceof JsonResponse) {
            return $donnees;
        }

        $actuel = $reclamation->getStatut();
        $cible = $donnees['statut'] ?? null;
        if (!is_string($cible) || !array_key_exists($cible, self::TRANSITIONS)) {
            return $this->json(['erreur' => 'Données invalides', 'erreurs' => ['statut' => 'Statut attendu : ' . implode(', ', array_keys(self::TRANSITIONS))]], 422);
        }
        if ($actuel === 'TRAITEE') {
            return $this->erreur(409, 'Réclamation déjà traitée : elle ne peut plus être modifiée');
        }
        if (!in_array($cible, self::TRANSITIONS[$actuel] ?? [], true)) {
            return $this->erreur(409, "Transition interdite : $actuel → $cible");
        }

        $reponse = $donnees['reponse'] ?? null;
        if ($cible === 'TRAITEE' && (!is_string($reponse) || trim($reponse) === '')) {
            return $this->json(['erreur' => 'Données invalides', 'erreurs' => ['reponse' => 'Une réponse est obligatoire pour traiter la réclamation']], 422);
        }
        if (is_string($reponse) && trim($reponse) !== '') {
            $reclamation->setReponse($reponse);
        }
        $reclamation->setStatut($cible);
        $em->flush();

        return $this->json($this->versTableau($reclamation));
    }

    private function refuserSiAnonyme(): ?JsonResponse
    {
        return $this->getUser() instanceof User ? null : $this->erreur(401, 'Authentification requise');
    }

    /** @return array<string, mixed>|JsonResponse */
    private function lireJson(Request $request): array|JsonResponse
    {
        if ($request->getContentTypeFormat() !== 'json') {
            return $this->erreur(415, 'Content-Type attendu : application/json');
        }
        $donnees = json_decode($request->getContent(), true);
        if (!is_array($donnees)) {
            return $this->erreur(400, 'Corps JSON invalide');
        }

        return $donnees;
    }

    private function erreur(int $code, string $message): JsonResponse
    {
        return $this->json(['erreur' => $message], $code);
    }

    /** L'adresse e-mail de l'auteur n'est jamais exposée (EX-33). */
    private function versTableau(Reclamation $r): array
    {
        return [
            'id' => $r->getId(),
            'sujet' => $r->getSujet(),
            'message' => $r->getMessage(),
            'statut' => $r->getStatut(),
            'dateCreation' => $r->getDateCreation()?->format(\DateTimeInterface::ATOM),
            'dateTraitement' => $r->getDateTraitement()?->format(\DateTimeInterface::ATOM),
            'reponse' => $r->getReponse(),
            'auteurId' => $r->getUser()?->getId(),
            'trajetId' => $r->getTrajetConcerne()?->getId(),
        ];
    }
}
