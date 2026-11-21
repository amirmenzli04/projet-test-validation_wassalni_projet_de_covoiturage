<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Garde-fou d'architecture pour ANO-38 (R1, EX-35).
 *
 * IT-07 prouve que StockPlaces est atomique, mais pas que les contrôleurs l'utilisent.
 * Ce test vérifie que le contrôleur de réservation ne recalcule plus le stock en PHP (lecture puis écriture),
 * ce qui réintroduirait la surréservation en cas de requêtes simultanées. (L'initialisation du stock à la
 * publication d'un trajet, dans TrajetController, n'est pas un mouvement concurrent : elle n'est pas visée.)
 */
final class StockPlacesArchitectureTest extends TestCase
{
    #[Group('ANO-38')]
    #[TestDox('ANO-38 — le contrôleur de réservation ne modifie plus le stock de places en PHP (setPlacesDisponibles)')]
    public function testLeControleurDeReservationNeModifiePasLeStockEnPhp(): void
    {
        $fautifs = [];
        foreach ([dirname(__DIR__, 2) . '/src/Controller/ReservationController.php'] as $fichier) {
            foreach (file($fichier) as $numero => $ligne) {
                if (str_contains($ligne, 'setPlacesDisponibles(')) {
                    $fautifs[] = basename($fichier) . ':' . ($numero + 1);
                }
            }
        }

        self::assertSame([], $fautifs, 'Stock modifié en PHP (non atomique) : ' . implode(', ', $fautifs));
    }

    #[Group('ANO-38')]
    #[TestDox('ANO-38 — les voies de réservation et d\'annulation passent par StockPlaces')]
    public function testLeControleurDeReservationUtiliseStockPlaces(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Controller/ReservationController.php');

        self::assertStringContainsString('$this->stock->prendre(', $source);
        self::assertStringContainsString('$this->stock->rendre(', $source);
    }
}
