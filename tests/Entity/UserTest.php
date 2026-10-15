<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserTest extends TestCase
{
    public function testRolesAlwaysContainRoleUserOnce(): void
    {
        $user = (new User())->setRoles(['ROLE_ADMIN', 'ROLE_USER']);

        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], array_values($user->getRoles()));
        $this->assertSame(['ROLE_USER'], (new User())->getRoles());
    }

    public function testUserIdentifierIsUsername(): void
    {
        $user = (new User())->setUsername('Admin');

        $this->assertSame('Admin', $user->getUserIdentifier());
    }

    public function testInitPasswordHashesPlainPassword(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), 'azerty')
            ->willReturn('hashed');

        $user = (new User())->setPlainPassword('azerty')->initPassword($hasher);

        $this->assertSame('hashed', $user->getPassword());
    }

    public function testInitPasswordDoesNothingWithoutPlainPassword(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->never())->method('hashPassword');

        $user = (new User())->setPassword('old')->initPassword($hasher);

        $this->assertSame('old', $user->getPassword());
    }
}
