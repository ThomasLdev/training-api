<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum Status: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
