<?php

declare(strict_types=1);

namespace Webware\UserManager\Repository;

use PhpDb\SchemaInterface;

enum Schema: string implements SchemaInterface
{
    case User = 'user';
}
