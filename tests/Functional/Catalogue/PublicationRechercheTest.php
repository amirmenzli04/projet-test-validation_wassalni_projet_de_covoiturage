<?php

namespace App\Tests\Functional\Catalogue;

use App\Entity\Vehicule;
use App\Tests\Support\CatalogueWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Groupes A et B du catalogue : publication d'un trajet (EX-07 à EX-12 ; CA-01 à CA-03, CA-21)
 * puis statut, visibilité et recherche (EX-13 à EX-15 ; CA-04, CA-05).
 * Le formulaire de publication ne saisit que des dates jj/mm/aaaa (pas d'heure, écart A-04).
 */
final class PublicationRechercheTest extends CatalogueWebTestCase
{
    private function day(int $offset): string
    {
        return (new \DateTime(sprintf('%+d days', $offset)))->format('d/m/Y');
    }

    /** Publie un trajet via POST /trajet/nouveau et renvoie l'id créé (null si aucun trajet). */
    private function publish(string $driver, array $overrides = []): ?int
    {
        $marker = 'Publication ' . bin2hex(random_bytes(5));
        $data = array_merge([
            'villeDepart' => 'Tunis',
            'destination' => 'Sousse',
            'dateDepart' => $this->day(1),
            'dateArrivee' => $this->day(2),
            'prix' => '15',
            'vehicule' => (string) $this->ids['V1'],
            'description' => $marker,
        ], $overrides);
        $this->client->loginUser($this->user($driver));
        $this->client->request('POST', '/trajet/nouveau', $data);
        $id = $this->em()->getConnection()->fetchOne('SELECT id FROM trajet WHERE description = ?', [$marker]);

        return $id === false ? null : (int) $id;
    }

    private function search(array $query): string
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->client->request('GET', '/trajet/recherche', $query);

        return $this->body();
    }

    private function shows(string $html, string $alias): bool
    {
        return str_contains($html, 'data-trajet-id="' . $this->ids[$alias] . '"');
    }

    // ---------- Groupe A : publication ----------

    #[TestDox('TC-09 (EX-08) — U-COND1 publie Tunis→Sousse J+1/J+2, 15 DT avec V1 : trajet enregistré, rattaché au conducteur')]
    public function testTC09PublicationNominale(): void
    {
        $id = $this->publish('U-COND1');

        self::assertResponseRedirects('/conducteur/dashboard');
        self::assertNotNull($id, 'Aucun trajet créé');
        $row = $this->em()->getConnection()->fetchAssociative('SELECT conducteur_id, vehicule_id, prix FROM trajet WHERE id = ?', [$id]);
        self::assertSame([$this->ids['U-COND1'], $this->ids['V1']], [(int) $row['conducteur_id'], (int) $row['vehicule_id']]);
    }

    #[TestDox('TC-10 (CA-02) — départ J-1 par requête directe : refus, aucun trajet créé')]
    public function testTC10DepartPasse(): void
    {
        self::assertNull($this->publish('U-COND1', ['dateDepart' => $this->day(-1), 'dateArrivee' => $this->day(-1)]));
    }

    #[TestDox('TC-11 (CA-02, A-04) — départ le jour même sans heure (début de journée passé) : refus attendu')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-14')]
    public function testTC11DepartLeJourMeme(): void
    {
        self::assertNull($this->publish('U-COND1', ['dateDepart' => $this->day(0), 'dateArrivee' => $this->day(1)]), 'Trajet du jour accepté à 00:00');
    }

    #[TestDox('TC-12 (CA-02) — arrivée égale au départ (J+1) : refus attendu')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-14')]
    public function testTC12ArriveeEgaleAuDepart(): void
    {
        self::assertNull($this->publish('U-COND1', ['dateDepart' => $this->day(1), 'dateArrivee' => $this->day(1)]), 'Arrivée = départ acceptée');
    }

    #[TestDox('TC-13 (CA-02) — arrivée J+1 avant départ J+2 : refus, aucun trajet')]
    public function testTC13ArriveeAvantDepart(): void
    {
        self::assertNull($this->publish('U-COND1', ['dateDepart' => $this->day(2), 'dateArrivee' => $this->day(1)]));
    }

    public static function prices(): iterable
    {
        yield 'prix 0 refusé' => ['0', false];
        yield 'prix -1 refusé' => ['-1', false];
        yield 'prix 0,01 accepté' => ['0.01', true];
        yield 'prix 15 accepté' => ['15', true];
    }

    #[DataProvider('prices')]
    #[TestDox('TC-14 (CA-03) — $_dataName')]
    public function testTC14Prix(string $price, bool $accepted): void
    {
        $id = $this->publish('U-COND1', ['prix' => $price]);
        $stored = $id === null ? 'aucun trajet' : 'trajet créé, prix enregistré ' . $this->em()->getConnection()->fetchOne('SELECT prix FROM trajet WHERE id = ?', [$id]);
        self::assertSame($accepted, $id !== null, "prix=$price : $stored");
    }

    #[TestDox('TC-14 (CA-03) — prix « abc » (non numérique) : refus attendu, aucun trajet')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-15')]
    public function testTC14PrixNonNumerique(): void
    {
        $id = $this->publish('U-COND1', ['prix' => 'abc']);
        $stored = $id === null ? 'aucun trajet' : 'trajet créé, prix enregistré ' . $this->em()->getConnection()->fetchOne('SELECT prix FROM trajet WHERE id = ?', [$id]);
        self::assertNull($id, "prix=abc : $stored");
    }

    #[TestDox('TC-15 (EX-11, CA-01) — trajet publié : statut EN_ATTENTE en base')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-16')]
    public function testTC15StatutInitialEnAttente(): void
    {
        $id = $this->publish('U-COND1');
        self::assertNotNull($id);
        self::assertSame('EN_ATTENTE', $this->em()->getConnection()->fetchOne('SELECT statut FROM trajet WHERE id = ?', [$id]));
    }

    #[TestDox('TC-16 (EX-12, CA-01) — trajet tout juste publié : absent de la recherche passager avant validation')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-16')]
    public function testTC16TrajetNonValideInvisible(): void
    {
        $id = $this->publish('U-COND1');
        self::assertNotNull($id);
        $this->ids['PUBLIE'] = $id;
        self::assertFalse($this->shows($this->search(['villeDepart' => 'Tunis', 'destination' => 'Sousse']), 'PUBLIE'));
    }

    #[TestDox('CA-21 (EX-07) — U-COND2 sans véhicule : redirigé vers l\'ajout de véhicule, aucun trajet publié')]
    public function testCA21ConducteurSansVehicule(): void
    {
        $id = $this->publish('U-COND2');
        self::assertResponseRedirects('/vehicule/nouveau');
        self::assertNull($id);
    }

    #[TestDox('CA-13 (EX-32) — U-COND2 publie avec le véhicule V1 d\'un autre conducteur : refus attendu')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-17')]
    public function testCA13VehiculeDUnAutreConducteur(): void
    {
        $own = (new Vehicule())->setMarque('Dacia')->setModele('Logan')->setImmatriculation('CATOWN' . random_int(100, 999))
            ->setNbPlaces(4)->setProprietaire($this->user('U-COND2'));
        $this->em()->persist($own);
        $this->em()->flush();

        $id = $this->publish('U-COND2', ['vehicule' => (string) $this->ids['V1']]);
        self::assertNull($id, 'Trajet publié avec le véhicule d\'un autre conducteur');
    }

    #[Group('anomalie-ouverte')]
    #[Group('ANO-35')]
    #[TestDox("CA-21 (EX-06) — U-COND2 ajoute un véhicule par le formulaire (5 champs) : véhicule enregistré")]
    public function testCA21AjoutVehicule(): void
    {
        $this->client->loginUser($this->user('U-COND2'));
        $form = $this->client->request('GET', '/vehicule/nouveau')->filter('form[name="vehicule"]')->form([
            'vehicule[marque]' => 'Renault',
            'vehicule[modele]' => 'Clio',
            'vehicule[immatriculation]' => 'CA21TEST',
            'vehicule[nbPlaces]' => '4',
            'vehicule[couleur]' => 'Bleu',
        ]);
        $this->client->submit($form);
        $this->assertNotServerError('Ajout de véhicule');
        self::assertSame(1, (int) $this->em()->getConnection()->fetchOne("SELECT COUNT(*) FROM vehicule WHERE immatriculation = 'CA21TEST'"));
    }

    // ---------- Groupe B : visibilité et recherche ----------

    #[TestDox('TC-18 (CA-04) — Tunis→Sfax sans date : T-FUT-EARLY et T-P4 affichés, T-PAST (passé) absent')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-10')]
    public function testTC18TrajetPasseAbsent(): void
    {
        $html = $this->search(['villeDepart' => 'Tunis', 'destination' => 'Sfax']);
        self::assertSame(
            ['T-FUT-EARLY' => true, 'T-P4' => true, 'T-PAST' => false],
            ['T-FUT-EARLY' => $this->shows($html, 'T-FUT-EARLY'), 'T-P4' => $this->shows($html, 'T-P4'), 'T-PAST' => $this->shows($html, 'T-PAST')]
        );
    }

    #[TestDox('TC-19 (CA-04) — T-NOMINAL trouvé ; T-FULL (0 place) et T-PEND (en attente) absents')]
    public function testTC19FiltresStatutEtPlaces(): void
    {
        $nominal = $this->shows($this->search(['villeDepart' => 'Tunis', 'destination' => 'Sousse']), 'T-NOMINAL');
        $full = $this->shows($this->search(['villeDepart' => 'Tunis', 'destination' => 'Bizerte']), 'T-FULL');
        $pending = $this->shows($this->search(['villeDepart' => 'Sousse', 'destination' => 'Monastir']), 'T-PEND');
        self::assertSame(['T-NOMINAL' => true, 'T-FULL' => false, 'T-PEND' => false], ['T-NOMINAL' => $nominal, 'T-FULL' => $full, 'T-PEND' => $pending]);
    }

    #[TestDox('TC-20 a (EX-13) — filtre prix maximum 20 DT sur Tunis→Sfax : T-FUT-EARLY (18) seul, T-P4 (22) exclu')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-18')]
    public function testTC20FiltrePrix(): void
    {
        $html = $this->search(['villeDepart' => 'Tunis', 'destination' => 'Sfax', 'prixMax' => '20']);
        self::assertSame(['T-FUT-EARLY' => true, 'T-P4' => false], ['T-FUT-EARLY' => $this->shows($html, 'T-FUT-EARLY'), 'T-P4' => $this->shows($html, 'T-P4')]);
    }

    #[TestDox('TC-20 b (EX-13) — villes saisies en minuscules : mêmes résultats')]
    public function testTC20VillesEnMinuscules(): void
    {
        $html = $this->search(['villeDepart' => 'tunis', 'destination' => 'sfax']);
        self::assertTrue($this->shows($html, 'T-FUT-EARLY') && $this->shows($html, 'T-P4'));
    }

    #[TestDox('CA-05 (EX-15) — résultats triés par date de départ croissante : T-FUT-EARLY (J+2) avant T-P4 (J+8)')]
    public function testCA05TriChronologique(): void
    {
        $html = $this->search(['villeDepart' => 'Tunis', 'destination' => 'Sfax']);
        $early = strpos($html, 'data-trajet-id="' . $this->ids['T-FUT-EARLY'] . '"');
        $late = strpos($html, 'data-trajet-id="' . $this->ids['T-P4'] . '"');
        self::assertNotFalse($early);
        self::assertNotFalse($late);
        self::assertLessThan($late, $early);
    }

    #[TestDox('CA-04 (EX-13) — filtre date J+8 : T-P4 seul, T-FUT-EARLY (J+2) exclu')]
    public function testCA04FiltreDate(): void
    {
        $html = $this->search(['villeDepart' => 'Tunis', 'destination' => 'Sfax', 'dateDepart' => (new \DateTime('+8 days'))->format('Y-m-d')]);
        self::assertSame(['T-P4' => true, 'T-FUT-EARLY' => false], ['T-P4' => $this->shows($html, 'T-P4'), 'T-FUT-EARLY' => $this->shows($html, 'T-FUT-EARLY')]);
    }

    #[Group('anomalie-ouverte')]
    #[Group('ANO-28')]
    #[TestDox('A-05 (EX-29) — les statistiques de la page de recherche reflètent la base (pas de valeurs codées en dur)')]
    public function testA05StatistiquesDeLaRecherche(): void
    {
        $html = $this->search(['villeDepart' => 'Tunis']);
        $users = (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM `user`');
        self::assertStringNotContainsString('50k+', $html, "La page affiche « 50k+ » alors que la base compte $users utilisateurs");
    }

    #[TestDox('Robustesse (OBS-G) — date de recherche invalide « abc » : pas d\'erreur serveur')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-19')]
    public function testRechercheDateInvalide(): void
    {
        $this->search(['dateDepart' => 'abc']);
        $this->assertNotServerError('Recherche dateDepart=abc');
    }
}
