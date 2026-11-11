<?php

namespace App\Tests\Functional\Catalogue;

use App\Entity\Reclamation;
use App\Tests\Support\CatalogueWebTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Groupe D du catalogue : connexion et contrôle d'accès (EX-03, EX-04, EX-27, EX-28, EX-31 à EX-35 ;
 * CA-11 à CA-16, CA-20). Les connexions des TC-03, TC-04 et TC-51 passent par le vrai formulaire.
 */
final class ConnexionAccesTest extends CatalogueWebTestCase
{
    private function loginErrorMessage(): ?string
    {
        $crawler = $this->client->request('GET', '/connexion');
        $alert = $crawler->filter('.alert');

        return $alert->count() > 0 ? trim($alert->text()) : null;
    }

    #[TestDox('TC-03 (CA-11, EX-03) — compte non vérifié avec le bon mot de passe : aucune session authentifiée')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-20')]
    public function testTC03CompteNonVerifie(): void
    {
        $this->loginByForm($this->client, $this->emails['U-PASS-NV'], $this->plainPassword);
        self::assertFalse($this->hasPassengerSession($this->client), 'Le compte non vérifié accède à /reservation/mes-reservations');
    }

    #[TestDox('TC-51 (CA-11) — mauvais mot de passe puis e-mail inconnu : refus, message identique, aucune session')]
    public function testTC51MauvaisIdentifiants(): void
    {
        $this->loginByForm($this->client, $this->emails['U-PASS1'], 'mauvais-mot-de-passe');
        $wrongPassword = $this->loginErrorMessage();
        $sessionAfterWrongPassword = $this->hasPassengerSession($this->client);

        $this->loginByForm($this->client, 'inconnu-' . bin2hex(random_bytes(3)) . '@example.test', 'peu-importe');
        $unknownEmail = $this->loginErrorMessage();

        self::assertNotNull($wrongPassword);
        self::assertSame($wrongPassword, $unknownEmail);
        self::assertFalse($sessionAfterWrongPassword || $this->hasPassengerSession($this->client));
    }

    #[Group('anomalie-ouverte')]
    #[Group('ANO-30')]
    #[TestDox("A-09 — le message d'échec de connexion est rédigé en français")]
    public function testA09MessageDeConnexionEnFrancais(): void
    {
        $this->loginByForm($this->client, $this->emails['U-PASS1'], 'mauvais-mot-de-passe');
        $message = $this->loginErrorMessage();
        self::assertNotNull($message);
        self::assertStringNotContainsString('Invalid credentials', $message, "Message affiché : « $message »");
    }

    #[TestDox('TC-04 (CA-11, EX-04) — U-PASS2 bloqué par l\'admin ne peut plus se connecter ; réactivé, il le peut')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-21')]
    public function testTC04BlocagePuisReactivation(): void
    {
        $steps = [];
        $this->loginByForm($this->client, $this->emails['U-PASS2'], $this->plainPassword);
        $steps['1_connexion_initiale'] = $this->hasPassengerSession($this->client);
        $this->client->request('GET', '/deconnexion');

        $this->client->loginUser($this->user('U-ADMIN'));
        $this->client->request('GET', '/admin/utilisateur/bloquer/' . $this->ids['U-PASS2']);
        $steps['2_statut_en_base'] = $this->em()->getConnection()->fetchOne('SELECT statut FROM `user` WHERE id = ?', [$this->ids['U-PASS2']]);
        $this->client->request('GET', '/deconnexion');

        $this->loginByForm($this->client, $this->emails['U-PASS2'], $this->plainPassword);
        $steps['3_connexion_compte_bloque'] = $this->hasPassengerSession($this->client);
        $this->client->request('GET', '/deconnexion');

        $this->client->loginUser($this->user('U-ADMIN'));
        $this->client->request('GET', '/admin/utilisateur/activer/' . $this->ids['U-PASS2']);
        $this->client->request('GET', '/deconnexion');
        $this->loginByForm($this->client, $this->emails['U-PASS2'], $this->plainPassword);
        $steps['5_connexion_apres_reactivation'] = $this->hasPassengerSession($this->client);

        self::assertSame([
            '1_connexion_initiale' => true,
            '2_statut_en_base' => 'BLOQUE',
            '3_connexion_compte_bloque' => false,
            '5_connexion_apres_reactivation' => true,
        ], $steps);
    }

    #[TestDox('TC-41 (CA-12, EX-31) — U-PASS1 ouvre /admin/dashboard, /admin/utilisateurs et /admin/trajets : 403 pour chaque page')]
    public function testTC41PassagerSurPagesAdmin(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $codes = [];
        foreach (['/admin/dashboard', '/admin/utilisateurs', '/admin/trajets'] as $url) {
            $this->client->request('GET', $url);
            $codes[$url] = $this->httpStatus();
        }
        self::assertSame(['/admin/dashboard' => 403, '/admin/utilisateurs' => 403, '/admin/trajets' => 403], $codes);
    }

    #[TestDox('CA-12 (EX-31) — passager sur l\'espace conducteur : 403 ; visiteur anonyme : redirection vers /connexion')]
    public function testCA12RolesEtAnonyme(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->client->request('GET', '/conducteur/dashboard');
        $passenger = $this->httpStatus();
        $this->client->request('GET', '/deconnexion');

        $anonymous = [];
        foreach (['/admin/trajets', '/conducteur/mes-trajets', '/vehicule/mes-vehicules', '/reclamation/mes-reclamations'] as $url) {
            $this->client->request('GET', $url);
            $anonymous[$url] = $this->client->getResponse()->headers->get('Location');
        }
        self::assertSame(403, $passenger);
        self::assertSame(array_fill_keys(array_keys($anonymous), '/connexion'), $anonymous);
    }

    #[TestDox('TC-45 (CA-14, EX-33) — /get-last-user-email sans connexion : accès refusé, aucune adresse e-mail renvoyée')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-22')]
    public function testTC45FuiteEmailDernierInscrit(): void
    {
        $this->client->request('GET', '/get-last-user-email');
        $body = $this->body();
        self::assertDoesNotMatchRegularExpression('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[a-z]{2,}/', $body, "Réponse HTTP {$this->httpStatus()} : $body");
        self::assertContains($this->httpStatus(), [302, 401, 403]);
    }

    #[TestDox('CA-14 (EX-33) — /check-verification-status sans connexion : accès refusé')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-22')]
    public function testCA14StatutVerificationAnonyme(): void
    {
        $this->client->request('GET', '/check-verification-status');
        self::assertContains($this->httpStatus(), [302, 401, 403], "HTTP {$this->httpStatus()} : {$this->body()}");
    }

    #[TestDox('TC-38 (CA-15, EX-34) — admin ouvre par GET /admin/trajet/valider/{T-PEND} : refus, T-PEND reste EN_ATTENTE')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-13')]
    public function testTC38ValidationParGet(): void
    {
        $this->client->loginUser($this->user('U-ADMIN'));
        $this->client->request('GET', '/admin/trajet/valider/' . $this->ids['T-PEND']);
        self::assertSame('EN_ATTENTE', $this->em()->getConnection()->fetchOne('SELECT statut FROM trajet WHERE id = ?', [$this->ids['T-PEND']]), "HTTP {$this->httpStatus()}");
    }

    #[TestDox('CA-15 (EX-34) — admin bloque U-PASS2 par simple GET sans jeton : refus, statut ACTIF conservé')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-13')]
    public function testCA15BlocageParGet(): void
    {
        $this->client->loginUser($this->user('U-ADMIN'));
        $this->client->request('GET', '/admin/utilisateur/bloquer/' . $this->ids['U-PASS2']);
        self::assertSame('ACTIF', $this->em()->getConnection()->fetchOne('SELECT statut FROM `user` WHERE id = ?', [$this->ids['U-PASS2']]));
    }

    #[TestDox('CA-15 (EX-34) — admin supprime T-P1 par simple GET sans jeton : refus, trajet conservé')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-13')]
    public function testCA15SuppressionTrajetParGet(): void
    {
        $this->client->loginUser($this->user('U-ADMIN'));
        $this->client->request('GET', '/admin/trajet/supprimer/' . $this->ids['T-P1']);
        self::assertSame(1, (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM trajet WHERE id = ?', [$this->ids['T-P1']]), "HTTP {$this->httpStatus()}");
    }

    #[TestDox('CA-15 (EX-34) — traitement d\'une réclamation par POST sans jeton CSRF : refus, statut EN_ATTENTE conservé')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-13')]
    public function testCA15TraitementReclamationSansJeton(): void
    {
        $reclamation = (new Reclamation())->setSujet('Retard du conducteur')->setMessage('Message de test catalogue')->setUser($this->user('U-PASS1'));
        $this->em()->persist($reclamation);
        $this->em()->flush();

        $this->client->loginUser($this->user('U-ADMIN'));
        $this->client->request('POST', '/admin/reclamation/traiter/' . $reclamation->getId(), ['reponse' => 'Réponse sans jeton']);
        self::assertSame('EN_ATTENTE', $this->em()->getConnection()->fetchOne('SELECT statut FROM reclamation WHERE id = ?', [$reclamation->getId()]));
    }

    #[TestDox('CA-20 (EX-28, EX-35) — admin supprime T-NOMINAL qui a des réservations : refus propre ou propagation, sans erreur 500 ni orphelin')]
    public function testCA20SuppressionTrajetAvecReservations(): void
    {
        $this->client->loginUser($this->user('U-ADMIN'));
        $this->client->request('GET', '/admin/trajet/supprimer/' . $this->ids['T-NOMINAL']);
        $orphans = (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM reservation r LEFT JOIN trajet t ON t.id = r.trajet_id WHERE t.id IS NULL');
        self::assertSame(['http_5xx' => false, 'reservations_orphelines' => 0], ['http_5xx' => $this->httpStatus() >= 500, 'reservations_orphelines' => $orphans]);
    }

    #[TestDox('CA-20 (EX-27, EX-35) — admin supprime U-PASS1 qui a des réservations : refus propre ou propagation, sans erreur 500')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-23')]
    public function testCA20SuppressionUtilisateurAvecReservations(): void
    {
        $this->client->loginUser($this->user('U-ADMIN'));
        $this->client->request('GET', '/admin/utilisateur/supprimer/' . $this->ids['U-PASS1']);
        $this->assertNotServerError('Suppression d\'un utilisateur ayant des réservations');
    }

    #[TestDox('CA-16 (EX-26) — tableau de bord admin : le compteur « Réclamations » égale le nombre en attente en base')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-24')]
    public function testCA16CompteurReclamationsAdmin(): void
    {
        $reclamation = (new Reclamation())->setSujet('Retard du conducteur')->setMessage('Réclamation pour compteur')->setUser($this->user('U-PASS1'));
        $this->em()->persist($reclamation);
        $this->em()->flush();
        $expected = (int) $this->em()->getConnection()->fetchOne("SELECT COUNT(*) FROM reclamation WHERE statut = 'EN_ATTENTE'");

        $this->client->loginUser($this->user('U-ADMIN'));
        $crawler = $this->client->request('GET', '/admin/dashboard');
        $card = $crawler->filter('.stat-mini-card')->reduce(fn ($node) => str_contains($node->text(), 'Réclamations'));
        self::assertCount(1, $card);
        self::assertSame($expected, (int) $card->filter('.number')->text());
    }

    #[TestDox('Disponibilité (OBS-H) — /avis/conducteur/{id} et /inscription répondent sans erreur serveur')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-25')]
    public function testPagesSansErreurServeur(): void
    {
        $codes = [];
        $this->client->request('GET', '/avis/conducteur/' . $this->ids['U-COND1']);
        $codes['/avis/conducteur/{id}'] = $this->httpStatus();
        $this->client->request('GET', '/inscription');
        $codes['/inscription'] = $this->httpStatus();
        self::assertSame(['/avis/conducteur/{id}' => false, '/inscription' => false], array_map(fn ($c) => $c >= 500, $codes), json_encode($codes));
    }

    #[TestDox('CA-13 (EX-32) — U-COND2 ouvre la modification du véhicule V1 de U-COND1 : 403')]
    public function testCA13VehiculeDUnAutre(): void
    {
        $this->client->loginUser($this->user('U-COND2'));
        $this->client->request('GET', '/vehicule/modifier/' . $this->ids['V1']);
        self::assertResponseStatusCodeSame(403);
    }
}
