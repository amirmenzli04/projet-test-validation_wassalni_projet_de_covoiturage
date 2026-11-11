<?php

namespace App\Tests\Functional\Catalogue;

use App\Tests\Support\CatalogueWebTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Groupe E du catalogue : avis et réclamations (EX-21, EX-22, EX-24, EX-25 ; CA-17, CA-18).
 */
final class AvisReclamationTest extends CatalogueWebTestCase
{
    private function countAvis(string $tripAlias): int
    {
        return (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM avis WHERE passager_id = ? AND trajet_id = ?', [$this->ids['U-PASS1'], $this->ids[$tripAlias]]);
    }

    /** Soumet le vrai formulaire d'avis (jeton CSRF inclus) avec une note éventuellement hors liste. */
    private function submitAvis(string $tripAlias, string $note): void
    {
        $crawler = $this->client->request('GET', '/avis/nouveau/' . $this->ids[$tripAlias]);
        $button = $crawler->selectButton('Publier mon avis');
        self::assertCount(1, $button, 'Formulaire d\'avis non affiché (HTTP ' . $this->httpStatus() . ')');
        $form = $button->form();
        $form->disableValidation();
        $form['avis[note]']->setValue($note);
        $form['avis[commentaire]']->setValue('Avis du catalogue de test');
        $this->client->submit($form);
    }

    #[TestDox('TC-33 (CA-18, EX-21) — notes 0, 1, 5, 6 sur un trajet terminé : 0 et 6 refusées, 1 et 5 acceptées')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-27')]
    public function testTC33BornesDeLaNote(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $result = [];
        foreach (['0', '1', '5', '6'] as $note) {
            $this->em()->getConnection()->executeStatement('DELETE FROM avis WHERE passager_id = ?', [$this->ids['U-PASS1']]);
            $this->submitAvis('T-PAST-RES', $note);
            $result[$note] = $this->countAvis('T-PAST-RES') === 1;
        }
        self::assertSame(['0' => false, '1' => true, '5' => true, '6' => false], $result);
    }

    #[TestDox('CA-18 (EX-22) — second avis sur le même trajet : refusé, un seul avis')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-27')]
    public function testCA18SecondAvis(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->submitAvis('T-PAST-RES', '4');
        $this->client->request('GET', '/avis/nouveau/' . $this->ids['T-PAST-RES']);
        self::assertSame(1, $this->countAvis('T-PAST-RES'));
        self::assertResponseRedirects('/reservation/mes-reservations');
    }

    #[TestDox('CA-18 (EX-21) — avis sur T-NOMINAL avant la fin du trajet (départ J+7) : refusé')]
    #[Group('anomalie-ouverte')]
    #[Group('ANO-26')]
    public function testCA18AvisAvantLaFinDuTrajet(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->client->request('GET', '/avis/nouveau/' . $this->ids['T-NOMINAL']);
        $formShown = $this->client->getCrawler()->selectButton('Publier mon avis')->count() === 1;
        if ($formShown) {
            $this->submitAvis('T-NOMINAL', '5');
        }
        self::assertSame(['formulaire_propose' => false, 'avis_enregistres' => 0], ['formulaire_propose' => $formShown, 'avis_enregistres' => $this->countAvis('T-NOMINAL')]);
    }

    #[TestDox('CA-18 — avis sur un trajet jamais réservé (T-P4) : refusé')]
    public function testCA18AvisSansReservation(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $this->client->request('GET', '/avis/nouveau/' . $this->ids['T-P4']);
        self::assertResponseRedirects('/reservation/mes-reservations');
        self::assertSame(0, $this->countAvis('T-P4'));
    }

    #[TestDox('TC-36 (CA-17, EX-24) — réclamation : message vide et de 9 caractères refusés, 10 accepté ; sujet hors liste (101 car.) refusé')]
    public function testTC36BornesDeLaReclamation(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $count = fn () => (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM reclamation WHERE user_id = ?', [$this->ids['U-PASS1']]);
        $result = [];
        foreach (['message vide' => ['Retard important', ''], 'message 9' => ['Retard important', str_repeat('a', 9)], 'message 10' => ['Retard important', str_repeat('b', 10)], 'sujet 101' => [str_repeat('s', 101), str_repeat('c', 12)]] as $case => [$subject, $message]) {
            $before = $count();
            $form = $this->client->request('GET', '/reclamation/nouvelle')->filter('#reclamationForm')->form();
            $form->disableValidation();
            $form['reclamation[sujet]']->setValue($subject);
            $form['reclamation[message]']->setValue($message);
            $this->client->submit($form);
            $result[$case] = $count() > $before;
        }
        self::assertSame(['message vide' => false, 'message 9' => false, 'message 10' => true, 'sujet 101' => false], $result);
    }
}
