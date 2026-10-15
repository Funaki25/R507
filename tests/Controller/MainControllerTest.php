<?php

namespace App\Tests\Controller;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MainControllerTest extends WebTestCase
{
    public function testContactFormDisplay(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Formulaire de contact');
        $this->assertSelectorExists('form input[name="form[firstName]"]');
        $this->assertSelectorExists('form input[name="form[name]"]');
        $this->assertSelectorExists('form textarea[name="form[message]"]');
    }

    public function testContactFormSubmission(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $client->submitForm('Envoyer', [
            'form[firstName]' => 'John',
            'form[name]' => 'Doe',
            'form[message]' => 'Test message',
        ]);

        $this->assertResponseRedirects('/');

        $contact = static::getContainer()->get(ContactRepository::class)->findOneBy(['name' => 'Doe']);
        $this->assertNotNull($contact);
        $this->assertSame('John', $contact->getFirstName());
        $this->assertSame('Test message', $contact->getMessage());
        $this->assertSame('new', $contact->getStatus());
        $this->assertNotNull($contact->getCreatedAt());
    }

    public function testEmptyContactFormIsRejected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $client->submitForm('Envoyer', [
            'form[firstName]' => '',
            'form[name]' => '',
            'form[message]' => '',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(0, static::getContainer()->get(ContactRepository::class)->count([]));
    }

    public function testContactListDisplaysContacts(): void
    {
        $client = static::createClient();

        $contact = (new Contact())
            ->setFirstName('Ada')
            ->setName('Lovelace')
            ->setMessage('Bonjour')
            ->setStatus('new')
            ->setCreatedAt(new \DateTimeImmutable());
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($contact);
        $em->flush();

        $client->request('GET', '/liste');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste de contacts');
        $this->assertSelectorTextContains('table tbody', 'Lovelace');
    }

    public function testLoginPageDisplay(): void
    {
        $client = static::createClient();
        $client->request('GET', '/connexion');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
    }
}
