<?php

namespace Database\DataAccess\Interfaces;

use Models\User;

interface UserDAO
{
    public function create(User $user, string $password): bool;
    public function getById(int $id): ?User;
    public function getByEmail(string $email): ?User;
    public function getHashedPasswordById(int $id): ?string;
    public function isVerifiedUser(string $email): bool;
    public function updateEmailConfirmedAt(string $email): bool;
}