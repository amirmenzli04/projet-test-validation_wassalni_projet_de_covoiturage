<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

/**
 * ANO-38 (R1, EX-35) : mise à jour ATOMIQUE du stock de places d'un trajet.
 *
 * L'ancien algorithme « lire le stock → vérifier → écrire stock − n » laissait deux requêtes
 * simultanées passer le contrôle avant que l'une ou l'autre n'écrive (surréservation, mise à jour perdue).
 * Ici, le contrôle et l'écriture sont une seule instruction SQL : la base verrouille la ligne,
 * et la seconde requête ne trouve plus assez de places.
 */
class StockPlaces
{
    /** Retire $nbPlaces du trajet si, et seulement si, le stock suffit au moment de l'écriture. */
    public function prendre(Connection $connection, int $trajetId, int $nbPlaces): bool
    {
        return $connection->executeStatement(
            'UPDATE trajet SET places_disponibles = places_disponibles - ? WHERE id = ? AND places_disponibles >= ?',
            [$nbPlaces, $trajetId, $nbPlaces]
        ) === 1;
    }

    /** Rend $nbPlaces au trajet (incrément atomique, sans relecture préalable). */
    public function rendre(Connection $connection, int $trajetId, int $nbPlaces): void
    {
        $connection->executeStatement(
            'UPDATE trajet SET places_disponibles = places_disponibles + ? WHERE id = ?',
            [$nbPlaces, $trajetId]
        );
    }
}
