<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Book $book): bool
    {
        return $user->hasRole('admin') || (int) $book->requester_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Book $book): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return (int) $book->requester_id === (int) $user->id && $book->isPending();
    }

    public function delete(User $user, Book $book): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return (int) $book->requester_id === (int) $user->id && $book->isPending();
    }

    public function approve(User $user, Book $book): bool
    {
        return $user->hasRole('admin');
    }

    public function reject(User $user, Book $book): bool
    {
        return $user->hasRole('admin');
    }
}
