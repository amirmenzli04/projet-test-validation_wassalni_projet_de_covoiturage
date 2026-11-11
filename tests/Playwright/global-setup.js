const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');

/**
 * Préparation et garde-fou exécutés une fois avant les tests navigateur :
 * 1. (re)crée les données fictives dans wassalni_test via la commande Symfony (elle refuse toute autre base) ;
 * 2. vérifie que le serveur HTTP sert bien l'environnement « test » : pas de barre de débogage,
 *    et le trajet de fixture (présent uniquement dans wassalni_test) est trouvé par la recherche.
 * En cas de doute, l'exécution est interrompue avant toute action sur l'application.
 */
module.exports = async (config) => {
    const root = path.resolve(__dirname, '..', '..');
    const output = execFileSync('php', ['bin/console', 'app:tests:prepare-week7-9', '--env=test', '--no-ansi'], { cwd: root, encoding: 'utf8' });
    const fixture = JSON.parse(output.slice(output.indexOf('{')));
    if (fixture.database !== 'wassalni_test') {
        throw new Error(`Base inattendue pour les données de test : ${fixture.database}`);
    }

    const baseURL = config.projects[0].use.baseURL;
    const response = await fetch(`${baseURL}/trajet/recherche?villeDepart=Tunis&destination=Sfax&dateDepart=2099-12-01`);
    const html = await response.text();
    if (response.headers.get('x-debug-token') || !html.includes(`data-trajet-id="${fixture.tripId}"`)) {
        throw new Error('Garde-fou : le serveur ne sert pas la base wassalni_test en environnement test. Arrêt.');
    }

    fs.mkdirSync(path.join(root, 'test-results'), { recursive: true });
    fs.writeFileSync(path.join(root, 'test-results', 'fixture.json'), JSON.stringify(fixture, null, 2));
    process.env.WASSALNI_FIXTURE_TRIP_ID = String(fixture.tripId);
};
