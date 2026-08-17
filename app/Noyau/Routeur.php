<?php

declare(strict_types=1);

namespace Application\Noyau;

class Routeur
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $chemin, array|callable $action): void
    {
        $this->routes['GET'][$this->normaliser($chemin)] = $action;
    }

    public function post(string $chemin, array|callable $action): void
    {
        $this->routes['POST'][$this->normaliser($chemin)] = $action;
    }

    public function resoudre(string $methode, string $uri): void
    {
        $chemin = parse_url($uri, PHP_URL_PATH) ?: '/';
        $chemin = $this->normaliser($chemin);
        $methode = strtoupper($methode);

        $action = $this->routes[$methode][$chemin] ?? null;

        if ($action === null) {
            http_response_code(404);
            require CHEMIN_RACINE . '/app/Vues/erreurs/404.php';
            return;
        }

        if (is_array($action)) {
            [$classe, $methodeControleur] = $action;
            $controleur = new $classe();
            $controleur->$methodeControleur();
            return;
        }

        $action();
    }

    private function normaliser(string $chemin): string
    {
        $chemin = '/' . trim($chemin, '/');

        return $chemin === '/' ? '/' : rtrim($chemin, '/');
    }
}
