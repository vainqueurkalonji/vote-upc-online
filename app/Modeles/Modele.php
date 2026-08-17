<?php

declare(strict_types=1);

namespace Application\Modeles;

use Application\Noyau\BaseDeDonnees;
use PDO;

abstract class Modele
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = BaseDeDonnees::connexion();
    }
}
