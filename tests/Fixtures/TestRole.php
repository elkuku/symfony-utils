<?php

declare(strict_types=1);

namespace Elkuku\SymfonyUtils\Tests\Fixtures;

enum TestRole: string
{
    case USER = 'ROLE_USER';
    case ADMIN = 'ROLE_ADMIN';
}
