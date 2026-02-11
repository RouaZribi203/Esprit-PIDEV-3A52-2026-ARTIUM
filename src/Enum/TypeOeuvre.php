<?php

namespace App\Enum;

enum TypeOeuvre: string
{
    case PEINTURE = 'Peinture';
    case SCULPTURE = 'Sculpture';
    case PHOTOGRAPHIE = 'Photographie';
    case MUSIQUE = 'Musique';
    case LIVRE = 'Livre';
}