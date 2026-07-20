<?php

namespace App\Enums;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case COMMISSIONER = 'commissioner';
    case DC = 'dc';
    case ACR = 'acr';
    case AC = 'ac';
    case ARO = 'aro';
    case BC = 'bc';
    case TEAMLEADER = 'teamleader';
    case SURVEYOR = 'surveyor';
}
