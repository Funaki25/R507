<?php

namespace App\Tests\Controller;

use App\Entity\Contact;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminControllerTest extends WebTestCase
{
    private const ADMIN_HOST = 'http://r507.iut-nevers.fr';

    public function testAdminRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', self::ADMIN_HOST.'/admin');

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/connexion', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testAdminLoginWithValidCredentials(): void
    {
        $client = static::createClient();
        // Même domaine que l'admin : sinon le cookie de session n'est pas renvoyé
        $client->request('GET', self::ADMIN_HOST.'/connexion');

        $client->submitForm('Connexion', [
            '_username' => 'Admin',
            '_password' => 'azerty',
        ]);

        $this->assertResponseRedirects();
        $client->followRedirects();
        $client->request('GET', self::ADMIN_HOST.'/admin');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('/admin/contact', (string) $client->getRequest()->getUri());
    }

    public function testAdminLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();
        $client->request('GET', '/connexion');

        $client->submitForm('Connexion', [
            '_username' => 'Admin',
            '_password' => 'mauvais-mot-de-passe',
        ]);

        $this->assertResponseRedirects('/connexion');
        $client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }

    public function testAdminCanMarkContactAsTreatedThenArchived(): void
    {
        $client = $this->createAdminClient();
        $contact = $this->createContact('new');

        $crawler = $client->request('GET', self::ADMIN_HOST.'/admin/contact/'.$contact->getId());
        $this->assertResponseIsSuccessful();

        $client->click($crawler->selectLink('Marquer comme traité')->link());
        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'traité');
        $this->assertSame('treated', $this->reloadContact($contact)->getStatus());

        $client->click($crawler->selectLink('Marquer comme archivé')->link());
        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'archivé');
        $this->assertSame('archived', $this->reloadContact($contact)->getStatus());
    }

    private function createAdminClient(): KernelBrowser
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['username' => 'Admin']);
        $this->assertInstanceOf(User::class, $admin, 'Les fixtures doivent être chargées (utilisateur Admin).');
        $client->loginUser($admin);

        return $client;
    }

    private function createContact(string $status): Contact
    {
        $contact = (new Contact())
            ->setFirstName('Grace')
            ->setName('Hopper')
            ->setMessage('Hello')
            ->setStatus($status)
            ->setCreatedAt(new \DateTimeImmutable());

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($contact);
        $em->flush();

        return $contact;
    }

    private function reloadContact(Contact $contact): Contact
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        return $em->find(Contact::class, $contact->getId());
    }
}
