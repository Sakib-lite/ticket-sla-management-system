<?php

namespace App\Enums;

enum ImportErrorKind: string
{
    case Invalid = 'invalid';
    case Duplicate = 'duplicate';
}
