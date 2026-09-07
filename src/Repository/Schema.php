<?php

declare(strict_types=1);

namespace Webware\UserManager\Repository;

use Webware\Core\SchemaInterface;

enum Schema: string implements SchemaInterface
{
    case User = 'user';
}
