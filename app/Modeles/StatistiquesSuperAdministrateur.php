<?php

declare(strict_types=1);

namespace Application\Modeles;

class StatistiquesSuperAdministrateur extends Modele
{
    public function resume(): array
    {
        return [
            'facultes' => $this->compter('facultes'),
            'promotions' => $this->compter('promotions'),
            'etudiants' => $this->compter('etudiants'),
            'utilisateurs' => $this->compter('utilisateurs'),
        ];
    }

    private function compter(string $table): int
    {
        $tablesAutorisees = ['facultes', 'promotions', 'etudiants', 'utilisateurs'];

        if (!in_array($table, $tablesAutorisees, true)) {
            return 0;
        }

        $requete = $this->db->query('SELECT COUNT(*) AS total FROM ' . $table);

        return (int) $requete->fetch()['total'];
    }
}
