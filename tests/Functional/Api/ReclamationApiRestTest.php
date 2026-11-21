<?php

namespace App\Tests\Functional\Api;

use App\Entity\Reclamation;
use App\Tests\Support\CatalogueWebTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * API REST des réclamations (/api/reclamations) : méthodes HTTP, codes de statut, corps JSON,
 * authentification, autorisations par rôle, validation et transitions d'état.
 * Identifiants API-xx, rattachés à EX-24, EX-25, EX-31, EX-32, EX-33 (voir la matrice).
 */
#[Group('api-rest')]
final class ReclamationApiRestTest extends CatalogueWebTestCase
{
    private function complaint(string $ownerAlias, string $statut = 'EN_ATTENTE'): Reclamation
    {
        $reclamation = (new Reclamation())
            ->setSujet('Retard important')
            ->setMessage('Réclamation créée pour les tests de l\'API REST')
            ->setUser($this->user($ownerAlias))
            ->setStatut($statut);
        $this->em()->persist($reclamation);
        $this->em()->flush();

        return $reclamation;
    }

    private function jsonRequest(string $method, string $url, ?array $body = null): void
    {
        $this->client->request($method, $url, [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            $body === null ? null : json_encode($body));
    }

    private function json(): array
    {
        return json_decode($this->body(), true, flags: JSON_THROW_ON_ERROR);
    }

    #[TestDox('API-01 (EX-33) — visiteur anonyme : 401 JSON sur la liste, le détail, la création et la modification')]
    public function testAnonymeRecoit401(): void
    {
        $id = $this->complaint('U-PASS1')->getId();
        $codes = [];
        foreach ([['GET', '/api/reclamations'], ['GET', "/api/reclamations/$id"], ['POST', '/api/reclamations'], ['PATCH', "/api/reclamations/$id"]] as [$method, $url]) {
            $this->jsonRequest($method, $url, ['statut' => 'TRAITEE']);
            $codes["$method $url"] = $this->httpStatus();
            self::assertStringContainsString('application/json', (string) $this->client->getResponse()->headers->get('Content-Type'));
        }
        self::assertSame(array_fill_keys(array_keys($codes), 401), $codes);
    }

    #[TestDox('API-02 (EX-32) — un passager ne liste que ses propres réclamations ; aucune adresse e-mail exposée')]
    public function testListeLimiteeAuProprietaire(): void
    {
        $mine = $this->complaint('U-PASS1')->getId();
        $other = $this->complaint('U-PASS2')->getId();
        $this->client->loginUser($this->user('U-PASS1'));

        $this->jsonRequest('GET', '/api/reclamations');

        self::assertSame(200, $this->httpStatus());
        $ids = array_column($this->json()['reclamations'], 'id');
        self::assertContains($mine, $ids);
        self::assertNotContains($other, $ids);
        self::assertStringNotContainsString('@', $this->body());
    }

    #[TestDox('API-03 (EX-25) — l\'administrateur liste toutes les réclamations et filtre par statut')]
    public function testAdministrateurListeEtFiltre(): void
    {
        $pending = $this->complaint('U-PASS1')->getId();
        $done = $this->complaint('U-PASS2', 'TRAITEE')->getId();
        $this->client->loginUser($this->user('U-ADMIN'));

        $this->jsonRequest('GET', '/api/reclamations');
        $all = array_column($this->json()['reclamations'], 'id');
        $this->jsonRequest('GET', '/api/reclamations?statut=TRAITEE');
        $filtered = $this->json()['reclamations'];

        self::assertContains($pending, $all);
        self::assertContains($done, $all);
        self::assertContains($done, array_column($filtered, 'id'));
        self::assertNotContains($pending, array_column($filtered, 'id'));
        self::assertSame(['TRAITEE'], array_values(array_unique(array_column($filtered, 'statut'))));
    }

    #[TestDox('API-04 — filtres invalides (statut inconnu, date mal formée) : 400 JSON')]
    public function testFiltresInvalides(): void
    {
        $this->client->loginUser($this->user('U-ADMIN'));
        $codes = [];
        foreach (['/api/reclamations?statut=CLOTUREE', '/api/reclamations?dateMin=21/11/2026', '/api/reclamations?dateMax=2026-02-30'] as $url) {
            $this->jsonRequest('GET', $url);
            $codes[$url] = $this->httpStatus();
        }
        self::assertSame(array_fill_keys(array_keys($codes), 400), $codes);
    }

    #[TestDox('API-05 — filtre par date de création : aujourd\'hui inclus, demain exclu')]
    public function testFiltreParDate(): void
    {
        $id = $this->complaint('U-PASS1')->getId();
        $this->client->loginUser($this->user('U-PASS1'));
        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $tomorrow = (new \DateTimeImmutable('+1 day'))->format('Y-m-d');

        $this->jsonRequest('GET', "/api/reclamations?dateMin=$today&dateMax=$today");
        $included = array_column($this->json()['reclamations'], 'id');
        $this->jsonRequest('GET', "/api/reclamations?dateMin=$tomorrow");
        $excluded = array_column($this->json()['reclamations'], 'id');

        self::assertContains($id, $included);
        self::assertNotContains($id, $excluded);
    }

    #[TestDox('API-06 (EX-32) — détail : 200 pour l\'auteur, 403 pour un autre passager, 404 si inexistante')]
    public function testDetailDroitsEtInexistant(): void
    {
        $id = $this->complaint('U-PASS1')->getId();

        $this->client->loginUser($this->user('U-PASS1'));
        $this->jsonRequest('GET', "/api/reclamations/$id");
        $owner = $this->httpStatus();
        $sujet = $this->json()['sujet'] ?? null;

        $this->client->loginUser($this->user('U-PASS2'));
        $this->jsonRequest('GET', "/api/reclamations/$id");
        $other = $this->httpStatus();
        $this->jsonRequest('GET', '/api/reclamations/999999999');
        $missing = $this->httpStatus();

        self::assertSame(['auteur' => 200, 'sujet' => 'Retard important', 'autre_passager' => 403, 'inexistante' => 404],
            ['auteur' => $owner, 'sujet' => $sujet, 'autre_passager' => $other, 'inexistante' => $missing]);
    }

    #[TestDox('API-07 (EX-24) — création valide : 201, en-tête Location, statut EN_ATTENTE, enregistrée en base')]
    public function testCreationValide(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->jsonRequest('POST', '/api/reclamations', ['sujet' => 'Problème de paiement', 'message' => 'Paiement débité deux fois', 'trajetId' => $this->ids['T-P4']]);

        self::assertSame(201, $this->httpStatus(), $this->body());
        $json = $this->json();
        self::assertSame('/api/reclamations/' . $json['id'], $this->client->getResponse()->headers->get('Location'));
        self::assertSame(['EN_ATTENTE', $this->ids['T-P4'], $this->ids['U-PASS1']], [$json['statut'], $json['trajetId'], $json['auteurId']]);
        $row = $this->em()->getConnection()->fetchAssociative('SELECT statut, user_id, sujet FROM reclamation WHERE id = ?', [$json['id']]);
        self::assertSame(['statut' => 'EN_ATTENTE', 'user_id' => $this->ids['U-PASS1'], 'sujet' => 'Problème de paiement'],
            ['statut' => $row['statut'], 'user_id' => (int) $row['user_id'], 'sujet' => $row['sujet']]);
    }

    #[TestDox('API-08 (EX-24, valeurs limites) — message de 9 caractères refusé (422), de 10 caractères accepté (201)')]
    public function testValeursLimitesDuMessage(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->jsonRequest('POST', '/api/reclamations', ['sujet' => 'Autre problème', 'message' => str_repeat('a', 9)]);
        $nine = [$this->httpStatus(), array_keys($this->json()['erreurs'] ?? [])];
        $this->jsonRequest('POST', '/api/reclamations', ['sujet' => 'Autre problème', 'message' => str_repeat('a', 10)]);
        $ten = $this->httpStatus();

        self::assertSame([[422, ['message']], 201], [$nine, $ten]);
    }

    #[TestDox('API-09 — création invalide : sujet hors liste ou trajet inconnu (422), JSON malformé (400), mauvais Content-Type (415) ; rien en base')]
    public function testCreationInvalide(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $before = (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM reclamation WHERE user_id = ?', [$this->ids['U-PASS1']]);
        $codes = [];

        $this->jsonRequest('POST', '/api/reclamations', ['sujet' => 'Sujet inventé', 'message' => 'Message suffisamment long']);
        $codes['sujet_hors_liste'] = $this->httpStatus();
        $this->jsonRequest('POST', '/api/reclamations', ['sujet' => 'Autre problème', 'message' => 'Message suffisamment long', 'trajetId' => 999999999]);
        $codes['trajet_inconnu'] = $this->httpStatus();
        $this->client->request('POST', '/api/reclamations', [], [], ['CONTENT_TYPE' => 'application/json'], '{"sujet": ');
        $codes['json_malforme'] = $this->httpStatus();
        $this->client->request('POST', '/api/reclamations', ['sujet' => 'Autre problème', 'message' => 'Message suffisamment long']);
        $codes['formulaire_non_json'] = $this->httpStatus();

        $after = (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM reclamation WHERE user_id = ?', [$this->ids['U-PASS1']]);
        self::assertSame(['sujet_hors_liste' => 422, 'trajet_inconnu' => 422, 'json_malforme' => 400, 'formulaire_non_json' => 415, 'aucune_creation' => true],
            $codes + ['aucune_creation' => $after === $before]);
    }

    #[TestDox('API-10 (EX-31) — un passager ne peut pas modifier le statut d\'une réclamation, même la sienne : 403, rien ne change')]
    public function testPassagerNePeutPasModifier(): void
    {
        $id = $this->complaint('U-PASS1')->getId();
        $this->client->loginUser($this->user('U-PASS1'));
        $this->jsonRequest('PATCH', "/api/reclamations/$id", ['statut' => 'TRAITEE', 'reponse' => 'Je me réponds à moi-même']);

        self::assertSame(403, $this->httpStatus());
        self::assertSame('EN_ATTENTE', $this->em()->getConnection()->fetchOne('SELECT statut FROM reclamation WHERE id = ?', [$id]));
    }

    #[TestDox('API-11 (EX-25, transitions) — administrateur : EN_ATTENTE → EN_COURS → TRAITEE, réponse et date de traitement enregistrées')]
    public function testCycleDeVieComplet(): void
    {
        $id = $this->complaint('U-PASS1')->getId();
        $this->client->loginUser($this->user('U-ADMIN'));

        $this->jsonRequest('PATCH', "/api/reclamations/$id", ['statut' => 'EN_COURS']);
        $step1 = [$this->httpStatus(), $this->json()['statut'], $this->json()['dateTraitement']];
        $this->jsonRequest('PATCH', "/api/reclamations/$id", ['statut' => 'TRAITEE', 'reponse' => 'Remboursement effectué']);
        $step2 = [$this->httpStatus(), $this->json()['statut'], $this->json()['reponse']];

        self::assertSame([200, 'EN_COURS', null], $step1);
        self::assertSame([200, 'TRAITEE', 'Remboursement effectué'], $step2);
        self::assertNotNull($this->em()->getConnection()->fetchOne('SELECT date_traitement FROM reclamation WHERE id = ?', [$id]));
    }

    #[TestDox('API-12 (transitions interdites) — retour EN_COURS → EN_ATTENTE (409) ; réclamation TRAITEE close (409) ; réponse écrasée impossible')]
    public function testTransitionsInterdites(): void
    {
        $inProgress = $this->complaint('U-PASS1', 'EN_COURS')->getId();
        $closed = $this->complaint('U-PASS2', 'TRAITEE');
        $closed->setReponse('Réponse initiale');
        $this->em()->flush();
        $this->client->loginUser($this->user('U-ADMIN'));

        $this->jsonRequest('PATCH', "/api/reclamations/$inProgress", ['statut' => 'EN_ATTENTE']);
        $backwards = $this->httpStatus();
        $this->jsonRequest('PATCH', '/api/reclamations/' . $closed->getId(), ['statut' => 'TRAITEE', 'reponse' => 'Seconde réponse']);
        $reopen = $this->httpStatus();

        self::assertSame([409, 409], [$backwards, $reopen]);
        self::assertSame('Réponse initiale', $this->em()->getConnection()->fetchOne('SELECT reponse FROM reclamation WHERE id = ?', [$closed->getId()]));
    }

    #[TestDox('API-13 — traitement sans réponse (422) ; statut inconnu (422) ; réclamation inexistante (404)')]
    public function testModificationInvalide(): void
    {
        $id = $this->complaint('U-PASS1')->getId();
        $this->client->loginUser($this->user('U-ADMIN'));
        $codes = [];

        $this->jsonRequest('PATCH', "/api/reclamations/$id", ['statut' => 'TRAITEE', 'reponse' => '   ']);
        $codes['reponse_vide'] = $this->httpStatus();
        $this->jsonRequest('PATCH', "/api/reclamations/$id", ['statut' => 'RESOLUE']);
        $codes['statut_inconnu'] = $this->httpStatus();
        $this->jsonRequest('PATCH', '/api/reclamations/999999999', ['statut' => 'EN_COURS']);
        $codes['inexistante'] = $this->httpStatus();

        self::assertSame(['reponse_vide' => 422, 'statut_inconnu' => 422, 'inexistante' => 404], $codes);
        self::assertSame('EN_ATTENTE', $this->em()->getConnection()->fetchOne('SELECT statut FROM reclamation WHERE id = ?', [$id]));
    }

    #[TestDox('API-14 — méthode non prévue (DELETE, PUT) : 405 Method Not Allowed')]
    public function testMethodesNonAutorisees(): void
    {
        $id = $this->complaint('U-PASS1')->getId();
        $this->client->loginUser($this->user('U-ADMIN'));
        $codes = [];
        foreach (['DELETE', 'PUT'] as $method) {
            $this->jsonRequest($method, "/api/reclamations/$id", ['statut' => 'TRAITEE']);
            $codes[$method] = $this->httpStatus();
        }
        self::assertSame(['DELETE' => 405, 'PUT' => 405], $codes);
    }
}
