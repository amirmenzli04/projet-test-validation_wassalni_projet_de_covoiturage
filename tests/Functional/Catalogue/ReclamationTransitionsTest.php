<?php

namespace App\Tests\Functional\Catalogue;

use App\Entity\Reclamation;
use App\Tests\Support\CatalogueWebTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Semaine 5 — Transitions d'état des réclamations (EX-25) et observations OBS-05-01 à OBS-05-04.
 *
 * Aucune règle produit ne définit la machine à états (EN_COURS, retraitement). Les tests du groupe
 * « caracterisation » FIGENT le comportement observé, sans le déclarer conforme : un changement de
 * comportement les fera échouer et devra être relu au regard de la décision produit.
 */
final class ReclamationTransitionsTest extends CatalogueWebTestCase
{
    private function complaint(string $status = 'EN_ATTENTE'): int
    {
        $reclamation = (new Reclamation())->setSujet('Retard important')->setMessage('Réclamation de transition')->setUser($this->user('U-PASS1'));
        $this->em()->persist($reclamation);
        $this->em()->flush();
        $id = $reclamation->getId();
        $this->em()->getConnection()->executeStatement('UPDATE reclamation SET statut = ? WHERE id = ?', [$status, $id]);
        // La mise à jour SQL contourne Doctrine : on vide la carte d'identité pour que l'application relise la base.
        $this->em()->clear();

        return $id;
    }

    private function row(int $id): array
    {
        return $this->em()->getConnection()->fetchAssociative('SELECT statut, reponse, date_traitement FROM reclamation WHERE id = ?', [$id]);
    }

    #[TestDox('T-01 (EX-25) — création par le formulaire : état initial EN_ATTENTE, date de traitement vide')]
    public function testCreationEnAttente(): void
    {
        $this->client->loginUser($this->user('U-PASS1'));
        $form = $this->client->request('GET', '/reclamation/nouvelle')->filter('#reclamationForm')->form();
        $form['reclamation[sujet]'] = 'Autre problème';
        $form['reclamation[message]'] = 'Transition T-01 de test';
        $this->client->submit($form);
        $row = $this->em()->getConnection()->fetchAssociative("SELECT statut, date_traitement FROM reclamation WHERE message = 'Transition T-01 de test'");
        self::assertSame(['statut' => 'EN_ATTENTE', 'date_traitement' => null], $row);
    }

    #[Group('caracterisation')]
    #[TestDox('T-02 / OBS-05-01 — EN_ATTENTE → TRAITEE directement par la réponse administrateur (aucun passage par EN_COURS)')]
    public function testTraitementDirect(): void
    {
        $id = $this->complaint();
        $this->client->loginUser($this->user('U-ADMIN'));
        $before = new \DateTimeImmutable('-2 seconds');
        $this->client->request('POST', '/admin/reclamation/traiter/' . $id, ['reponse' => 'Réponse T-02']);
        $after = new \DateTimeImmutable('+1 second');
        $row = $this->row($id);

        self::assertSame(['TRAITEE', 'Réponse T-02'], [$row['statut'], $row['reponse']]);
        // OBS-05-03 : malgré le double appel (setStatut puis setDateTraitement), la date persistée est cohérente.
        $processed = new \DateTimeImmutable($row['date_traitement']);
        self::assertTrue($processed >= $before && $processed <= $after, 'date_traitement hors de la fenêtre de la requête');
    }

    #[Group('caracterisation')]
    #[TestDox('T-03 — TRAITEE → TRAITEE : une seconde réponse par requête directe écrase la première (transition non gardée)')]
    public function testRetraitementNonGarde(): void
    {
        $id = $this->complaint();
        $this->client->loginUser($this->user('U-ADMIN'));
        $this->client->request('POST', '/admin/reclamation/traiter/' . $id, ['reponse' => 'Première réponse']);
        $this->client->request('POST', '/admin/reclamation/traiter/' . $id, ['reponse' => 'Seconde réponse']);

        self::assertSame(['TRAITEE', 'Seconde réponse'], array_values(array_slice($this->row($id), 0, 2)));
    }

    #[Group('caracterisation')]
    #[TestDox('T-04 / OBS-05-02 — Reclamation::setStatut() accepte un statut hors liste (aucune garde dans l\'entité)')]
    public function testStatutHorsListeAccepteParLEntite(): void
    {
        $reclamation = (new Reclamation())->setStatut('STATUT_INCONNU');
        self::assertSame('STATUT_INCONNU', $reclamation->getStatut());
    }

    #[Group('anomalie-ouverte')]
    #[Group('ANO-33')]
    #[TestDox('T-05 / OBS-05-04 (EX-29) — liste « mes réclamations » avec une réclamation EN_COURS : total = en attente + traitées')]
    public function testStatistiquesAvecEnCours(): void
    {
        $this->complaint('EN_COURS');
        $this->client->loginUser($this->user('U-PASS1'));
        $crawler = $this->client->request('GET', '/reclamation/mes-reclamations');
        $values = $crawler->filter('.stat-card .stat-info h3')->each(fn ($node) => (int) $node->text());
        self::assertCount(3, $values);
        [$total, $pending, $processed] = $values;
        self::assertSame($total, $pending + $processed, sprintf('Total %d, en attente %d, traitées %d', $total, $pending, $processed));
    }

    #[TestDox('T-06 (CA-12) — un passager ne peut pas traiter une réclamation : 403, statut inchangé')]
    public function testTraitementRefuseAuPassager(): void
    {
        $id = $this->complaint();
        $this->client->loginUser($this->user('U-PASS2'));
        $this->client->request('POST', '/admin/reclamation/traiter/' . $id, ['reponse' => 'Tentative']);
        self::assertResponseStatusCodeSame(403);
        self::assertSame('EN_ATTENTE', $this->row($id)['statut']);
    }
}
