<?php

namespace App\Tests\Functional;

use App\Entity\Reclamation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpFoundation\Response;

class ReclamationWorkflowTest extends WebTestCase
{
    private AbstractBrowser $client;
    private string $emailPrefix;
    private array $emails = [];

    protected function setUp(): void
    {
        $this->client = static::createClient(['debug' => false]);
        $this->emailPrefix = 'phpunit-' . bin2hex(random_bytes(8)) . '-';
    }

    protected function tearDown(): void
    {
        if (static::$kernel !== null) {
            $entityManager = $this->entityManager();

            foreach ($this->emails as $email) {
                $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($user === null) {
                    continue;
                }

                foreach ($entityManager->getRepository(Reclamation::class)->findBy(['user' => $user]) as $reclamation) {
                    $entityManager->remove($reclamation);
                }
                $entityManager->remove($user);
            }

            $entityManager->flush();
            $entityManager->clear();
        }

        parent::tearDown();
    }

    public function testAuthenticatedUserCanCreateAComplaint(): void
    {
        $owner = $this->createUser('ROLE_PASSAGER');
        $this->client->loginUser($owner);

        $crawler = $this->client->request('GET', '/reclamation/nouvelle');
        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('#reclamationForm'));

        $form = $crawler->filter('#reclamationForm')->form();
        $form['reclamation[sujet]'] = 'Autre problème';
        $form['reclamation[message]'] = 'Message valide de dix caractères';
        $this->client->submit($form);

        self::assertResponseRedirects('/reclamation/mes-reclamations');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Autre problème');
        self::assertSelectorTextContains('body', 'Message valide de dix caractères');

        $entityManager = $this->entityManager();
        $entityManager->clear();
        $savedComplaint = $entityManager->getRepository(Reclamation::class)->findOneBy([
            'user' => $entityManager->getRepository(User::class)->findOneBy(['email' => $owner->getEmail()]),
            'message' => 'Message valide de dix caractères',
        ]);

        self::assertNotNull($savedComplaint);
        self::assertSame('EN_ATTENTE', $savedComplaint->getStatut());
        self::assertNotNull($savedComplaint->getDateCreation());
    }

    public function testComplaintFormRejectsNineCharacterMessage(): void
    {
        $owner = $this->createUser('ROLE_PASSAGER');
        $this->client->loginUser($owner);

        $crawler = $this->client->request('GET', '/reclamation/nouvelle');
        $form = $crawler->filter('#reclamationForm')->form();
        $form['reclamation[sujet]'] = 'Autre problème';
        $form['reclamation[message]'] = '123456789';
        $this->client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', '10 caractères');
        self::assertSame(
            0,
            $this->entityManager()->getRepository(Reclamation::class)->count(['user' => $owner])
        );
    }

    public function testOnlyTheComplaintOwnerCanReadItsDetails(): void
    {
        $owner = $this->createUser('ROLE_PASSAGER');
        $otherUser = $this->createUser('ROLE_PASSAGER');
        $complaint = $this->createComplaint($owner, 'Message privé du propriétaire');

        $this->client->loginUser($owner);
        $this->client->request('GET', '/reclamation/voir/' . $complaint->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Message privé du propriétaire');

        $this->client->loginUser($otherUser);
        $this->client->request('GET', '/reclamation/voir/' . $complaint->getId());
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertStringNotContainsString(
            'Message privé du propriétaire',
            $this->client->getResponse()->getContent()
        );
    }

    public function testAdministratorCanProcessAndPersistAComplaint(): void
    {
        $owner = $this->createUser('ROLE_PASSAGER');
        $administrator = $this->createUser('ROLE_ADMIN');
        $complaint = $this->createComplaint($owner, 'Réclamation à traiter');

        $this->client->loginUser($administrator);
        $this->client->request('POST', '/admin/reclamation/traiter/' . $complaint->getId(), [
            'reponse' => 'Votre demande a été examinée.',
        ]);

        self::assertResponseRedirects('/admin/reclamations');

        $entityManager = $this->entityManager();
        $entityManager->clear();
        $savedComplaint = $entityManager->getRepository(Reclamation::class)->find($complaint->getId());

        self::assertNotNull($savedComplaint);
        self::assertSame('TRAITEE', $savedComplaint->getStatut());
        self::assertSame('Votre demande a été examinée.', $savedComplaint->getReponse());
        self::assertNotNull($savedComplaint->getDateTraitement());
    }

    public function testEmptyAdministratorResponseLeavesComplaintPending(): void
    {
        $owner = $this->createUser('ROLE_PASSAGER');
        $administrator = $this->createUser('ROLE_ADMIN');
        $complaint = $this->createComplaint($owner, 'Réclamation sans réponse');

        $this->client->loginUser($administrator);
        $this->client->request('POST', '/admin/reclamation/traiter/' . $complaint->getId(), [
            'reponse' => '',
        ]);

        self::assertResponseRedirects('/admin/reclamations');

        $entityManager = $this->entityManager();
        $entityManager->clear();
        $savedComplaint = $entityManager->getRepository(Reclamation::class)->find($complaint->getId());

        self::assertNotNull($savedComplaint);
        self::assertSame('EN_ATTENTE', $savedComplaint->getStatut());
        self::assertNull($savedComplaint->getReponse());
        self::assertNull($savedComplaint->getDateTraitement());
    }

    public function testNonAdministratorCannotOpenAdministration(): void
    {
        $passenger = $this->createUser('ROLE_PASSAGER');
        $this->client->loginUser($passenger);

        $this->client->request('GET', '/admin/dashboard');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testComplaintListOnlyContainsTheCurrentUsersComplaints(): void
    {
        $owner = $this->createUser('ROLE_PASSAGER');
        $otherUser = $this->createUser('ROLE_PASSAGER');
        $this->createComplaint($owner, 'Contenu visible pour A');
        $this->createComplaint($otherUser, 'Contenu privé pour B');
        $this->client->loginUser($owner);

        $this->client->request('GET', '/reclamation/mes-reclamations');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Contenu visible pour A');
        self::assertStringNotContainsString('Contenu privé pour B', $this->client->getResponse()->getContent());
    }

    private function createUser(string $role): User
    {
        $email = $this->emailPrefix . count($this->emails) . '@example.test';
        $this->emails[] = $email;

        $user = (new User())
            ->setEmail($email)
            ->setNom('Testeur')
            ->setPrenom('Automatique')
            ->setTelephone('10000000')
            ->setPassword('test-password')
            ->setRoles([$role])
            ->setIsVerified(true);

        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        return $user;
    }

    private function createComplaint(User $owner, string $message): Reclamation
    {
        $complaint = (new Reclamation())
            ->setSujet('Autre problème')
            ->setMessage($message)
            ->setUser($owner);

        $this->entityManager()->persist($complaint);
        $this->entityManager()->flush();

        return $complaint;
    }

    private function entityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}
