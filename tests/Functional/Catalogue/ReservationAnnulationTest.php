<?php

namespace App\Tests\Functional\Catalogue;

use App\Entity\Reservation;
use App\Tests\Support\CatalogueWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Groupe C du catalogue : réservation et annulation (EX-16 à EX-20, EX-32, EX-34, EX-35 ;
 * CA-06 à CA-10, CA-13, CA-15). Voie HTML : POST /reservation/new/{id} ; voie JSON : /reservation/new-ajax.
 */
final class ReservationAnnulationTest extends CatalogueWebTestCase
{
    /** Réservation par le formulaire HTML, avec le jeton CSRF que porte le formulaire (sauf $withToken = false). */
    private function reserveHtml(string $tripAlias, string|int $seats, bool $withToken = true): void
    {
        $params = ['nbPlaces' => (string) $seats];
        if ($withToken) {
            $params['_token'] = $this->csrfToken('reserver' . $this->ids[$tripAlias]);
        }
        $this->client->request('POST', '/reservation/new/' . $this->ids[$tripAlias], $params);
    }

    private function cancelHtml(string $reservationAlias): void
    {
        $id = $this->ids[$reservationAlias];
        $this->client->request('POST', '/reservation/annuler/' . $id, ['_token' => $this->csrfToken('annuler' . $id)]);
    }

    private function reserveJson(string $tripAlias, int $seats): void
    {
        $this->client->request('POST', '/reservation/new-ajax', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['trajetId' => $this->ids[$tripAlias], 'nbPlaces' => $seats]));
    }

    #[TestDox('TC-22 (CA-07, CA-08) — réserver les 4 places de T-P4 : CONFIRMEE, 0 place restante, total 88 DT')]
    public function testTC22ReserverToutesLesPlaces(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->reserveHtml('T-P4', 4);

        self::assertResponseRedirects();
        $row = $this->em()->getConnection()->fetchAssociative(
            'SELECT statut, nb_places, prix_total FROM reservation WHERE passager_id = ? AND trajet_id = ?',
            [$this->ids['U-PASS1'], $this->ids['T-P4']]);
        self::assertSame(['statut' => 'CONFIRMEE', 'nb_places' => 4], ['statut' => $row['statut'], 'nb_places' => (int) $row['nb_places']]);
        self::assertEqualsWithDelta(88.0, (float) $row['prix_total'], 0.001);
        self::assertSame(0, $this->seats('T-P4'));
    }

    #[TestDox('TC-23 (CA-06) — demander 5 places sur 4 par requête directe : refus, aucune réservation, 4 places')]
    public function testTC23DemandeSuperieureAuStock(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->reserveHtml('T-P4', 5);

        $this->assertNotServerError('TC-23');
        self::assertSame(0, $this->countReservations('U-PASS1', 'T-P4'));
        self::assertSame(4, $this->seats('T-P4'));
    }

    public static function invalidSeatCounts(): iterable
    {
        yield 'zéro' => ['0'];
        yield 'négatif' => ['-1'];
        yield 'décimal' => ['3.5'];
        yield 'texte' => ['abc'];
    }

    #[DataProvider('invalidSeatCounts')]
    #[TestDox('TC-24 (CA-06, EX-35) — nombre de places invalide « $seats » par la voie HTML : refus, stock inchangé')]
    #[Group('ANO-07')]
    public function testTC24NombreDePlacesInvalideVoieHtml(string $seats): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->reserveHtml('T-P4', $seats);

        $this->assertNotServerError('TC-24');
        $created = $this->em()->getConnection()->fetchAssociative(
            'SELECT nb_places, prix_total FROM reservation WHERE passager_id = ? AND trajet_id = ?', [$this->ids['U-PASS1'], $this->ids['T-P4']]);
        // Comparaison de l'état complet : le message d'échec montre la réservation créée et le stock obtenu.
        self::assertSame(
            ['reservation_creee' => null, 'places_restantes' => 4],
            ['reservation_creee' => $created === false ? null : $created, 'places_restantes' => $this->seats('T-P4')],
            "nbPlaces=$seats"
        );
    }

    #[TestDox('TC-25 (CA-07) — totaux de 1, 2 et 4 places à 22 DT : 22, 44 et 88 DT')]
    public function testTC25CalculDuTotal(): void
    {
        $totals = [];
        foreach ([1 => 'U-PASS1', 2 => 'U-PASS2', 4 => 'U-ADMIN'] as $seats => $alias) {
            // Trois passagers distincts pour ne pas déclencher la règle d'unicité ; stock rétabli entre deux envois.
            $this->em()->getConnection()->executeStatement('UPDATE trajet SET places_disponibles = 4 WHERE id = ?', [$this->ids['T-P4']]);
            $this->em()->getConnection()->executeStatement('UPDATE `user` SET roles = ? WHERE id = ?', ['["ROLE_PASSAGER"]', $this->ids[$alias]]);
            $this->client->loginUser($this->user($alias));
            $this->reserveHtml('T-P4', $seats);
            $totals[$seats] = (float) $this->em()->getConnection()->fetchOne(
                'SELECT prix_total FROM reservation WHERE passager_id = ? AND trajet_id = ?', [$this->ids[$alias], $this->ids['T-P4']]);
        }

        self::assertEqualsWithDelta([1 => 22.0, 2 => 44.0, 4 => 88.0], $totals, 0.001);
    }

    #[TestDox('TC-28 (CA-09) — seconde réservation de U-PASS1 sur T-NOMINAL par la voie HTML : refus, une seule réservation')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-08')]
    public function testTC28DoublonVoieHtml(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->reserveHtml('T-NOMINAL', 1);

        $this->assertNotServerError('TC-28');
        self::assertSame(1, $this->countReservations('U-PASS1', 'T-NOMINAL', 'CONFIRMEE'));
        self::assertSame(2, $this->seats('T-NOMINAL'));
    }

    #[TestDox('TC-28 bis (CA-09) — même doublon par la voie JSON : HTTP 400, une seule réservation')]
    public function testTC28DoublonVoieJson(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->reserveJson('T-NOMINAL', 1);

        self::assertResponseStatusCodeSame(400);
        self::assertSame(1, $this->countReservations('U-PASS1', 'T-NOMINAL', 'CONFIRMEE'));
    }

    #[TestDox('TC-29 (CA-10) — annuler RES-F1 puis réserver de nouveau T-NOMINAL : ANNULEE, places rendues, nouvelle réservation acceptée')]
    public function testTC29AnnulerPuisReserverDeNouveau(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->cancelHtml('RES-F1');
        self::assertSame(Reservation::STATUT_ANNULE, $this->reload(Reservation::class, 'RES-F1')?->getStatut());
        self::assertSame(3, $this->seats('T-NOMINAL'));

        $this->reserveHtml('T-NOMINAL', 1);
        self::assertSame(1, $this->countReservations('U-PASS1', 'T-NOMINAL', 'CONFIRMEE'));
        self::assertSame(2, $this->seats('T-NOMINAL'));
    }

    #[TestDox('TC-30 (CA-10) — annuler RES-PAST (trajet passé) : refus, statut CONFIRMEE, places inchangées')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-09')]
    public function testTC30AnnulationReservationPassee(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->cancelHtml('RES-PAST');

        $this->assertNotServerError('TC-30');
        self::assertSame(Reservation::STATUT_CONFIRME, $this->reload(Reservation::class, 'RES-PAST')?->getStatut());
        self::assertSame(2, $this->seats('T-PAST-RES'));
    }

    #[TestDox('TC-49 (CA-04) — réserver T-PAST (validé mais passé) par la voie HTML : refus, aucune réservation')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-10')]
    public function testTC49TrajetPasseVoieHtml(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->reserveHtml('T-PAST', 1);

        self::assertSame(0, $this->countReservations('U-PASS1', 'T-PAST'));
        self::assertSame(3, $this->seats('T-PAST'));
    }

    #[TestDox('TC-49 bis (CA-04, CA-09) — réserver T-PAST par la voie JSON : refus, aucune réservation')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-10')]
    public function testTC49TrajetPasseVoieJson(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->reserveJson('T-PAST', 1);

        self::assertSame(0, $this->countReservations('U-PASS1', 'T-PAST'));
    }

    #[TestDox('TC-50 (CA-01) — réserver T-PEND (EN_ATTENTE) par la voie HTML : refus, aucune réservation')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-11')]
    public function testTC50TrajetNonValideVoieHtml(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->reserveHtml('T-PEND', 1);

        self::assertSame(0, $this->countReservations('U-PASS1', 'T-PEND'));
        self::assertSame(3, $this->seats('T-PEND'));
    }

    #[TestDox('TC-50 bis (CA-01) — réserver T-PEND par la voie JSON : HTTP 400, aucune réservation')]
    public function testTC50TrajetNonValideVoieJson(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->reserveJson('T-PEND', 1);

        self::assertResponseStatusCodeSame(400);
        self::assertSame(0, $this->countReservations('U-PASS1', 'T-PEND'));
    }

    #[TestDox('CA-13 — U-PASS2 tente d\'annuler RES-F1 de U-PASS1 par URL directe : 403, état et stock inchangés')]
    public function testCA13AnnulationParUnAutrePassager(): void
    {
        $this->client->loginUser($this->user('U-PASS2'));
        $this->cancelHtml('RES-F1');

        self::assertResponseStatusCodeSame(403);
        self::assertSame(Reservation::STATUT_CONFIRME, $this->reload(Reservation::class, 'RES-F1')?->getStatut());
        self::assertSame(2, $this->seats('T-NOMINAL'));
    }

    #[TestDox('CA-10 — double annulation de RES-F1 : places rendues une seule fois')]
    public function testCA10DoubleAnnulation(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->cancelHtml('RES-F1');
        $this->cancelHtml('RES-F1');

        self::assertSame(3, $this->seats('T-NOMINAL'));
    }

    #[TestDox('CA-15 (EX-34) — annulation par simple GET sans jeton CSRF : refusée (405/403), réservation inchangée')]
    #[Group('ANO-12')]
    public function testCA15AnnulationParGetSansJeton(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->client->request('GET', '/reservation/annuler/' . $this->ids['RES-F1']);

        self::assertContains($this->httpStatus(), [403, 405], 'L\'annulation a été acceptée par GET (HTTP ' . $this->httpStatus() . ')');
        self::assertSame(Reservation::STATUT_CONFIRME, $this->reload(Reservation::class, 'RES-F1')?->getStatut());
    }

    #[TestDox('CA-15 (EX-34) — réservation HTML sans jeton CSRF : refusée, aucune réservation')]
    #[Group('ANO-12')]
    public function testCA15ReservationHtmlSansJeton(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->reserveHtml('T-P4', 1, false);

        self::assertSame(0, $this->countReservations('U-PASS1', 'T-P4'), 'Réservation créée par un POST sans jeton CSRF');
    }

    #[TestDox('CA-12 (EX-31) — un conducteur ne peut pas réserver (route réservée à ROLE_PASSAGER) : 403')]
    public function testCA12ConducteurNePeutPasReserver(): void
    {
        $this->client->loginUser($this->user('U-COND1'));
        $this->reserveHtml('T-P4', 1);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(4, $this->seats('T-P4'));
    }
}
