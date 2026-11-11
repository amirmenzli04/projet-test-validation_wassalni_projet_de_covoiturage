# Semaine 10 — Campagne de régression complète sur une même révision Git.
# Usage : powershell -NoProfile -ExecutionPolicy Bypass -File scripts/run-regression.ps1 [-Sortie preuves/semaine10/campagne-finale]
# Prérequis : vendor/ et node_modules/ installés, .env.test.local (WASSALNI_TEST_PASSWORD), MariaDB démarrée.
# Toutes les étapes utilisent exclusivement la base wassalni_test (garde-fous dans chaque outil).
param([string]$Sortie = "preuves/semaine10/campagne-finale")

$ErrorActionPreference = "Continue"
New-Item -ItemType Directory -Force -Path $Sortie | Out-Null
$sha = (git rev-parse --short HEAD).Trim()
$debut = Get-Date -Format "yyyy-MM-dd HH:mm:ss zzz"
$base = (php bin/console dbal:run-sql "SELECT DATABASE()" --env=test --no-ansi | Select-Object -Index 3).Trim()
if ($base -ne "wassalni_test") { Write-Error "Base inattendue : $base"; exit 2 }

$resultats = [ordered]@{}
function Etape([string]$nom, [string]$fichier, [scriptblock]$commande) {
    Write-Host "== $nom"
    $entete = "# $nom | $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss zzz') | revision $sha | base $base"
    $lignesSortie = & $commande 2>&1 | ForEach-Object { "$_" -replace "\x1B\[[0-9;]*m", "" }
    @($entete) + $lignesSortie | Out-File -Encoding utf8 (Join-Path $Sortie $fichier)
    $script:resultats[$nom] = @{ code = $LASTEXITCODE; fin = ($lignesSortie | Select-Object -Last 3) -join " | " }
}

Etape "PHPUnit regression (anomalies exclues)" "01-phpunit-regression.txt" { vendor/bin/phpunit --testdox --colors=never --log-junit "$Sortie/01-phpunit-regression-junit.xml" }
Etape "PHPUnit anomalies ouvertes" "02-phpunit-anomalies.txt" { vendor/bin/phpunit --testdox --colors=never --group anomalie-ouverte --log-junit "$Sortie/02-phpunit-anomalies-junit.xml" }
Etape "Playwright (navigateur)" "03-playwright.txt" { $env:PLAYWRIGHT_JSON_OUTPUT_NAME = "$Sortie/03-playwright-rapport.json"; npx playwright test --reporter=list,json | Where-Object { $_ -notmatch "WebServer" } }
Etape "Newman (API)" "04-newman.txt" { node scripts/run-newman.js "$Sortie/newman" }
Etape "k6 (performance)" "05-k6.txt" { node scripts/run-k6.js "$Sortie/k6" }

$fin = Get-Date -Format "yyyy-MM-dd HH:mm:ss zzz"
$lignes = @("# Synthèse de la campagne de régression", "Révision : $sha ; base : $base ; début : $debut ; fin : $fin", "")
foreach ($k in $resultats.Keys) { $lignes += "- $k : code de sortie $($resultats[$k].code) ; $($resultats[$k].fin)" }
$lignes | Out-File -Encoding utf8 (Join-Path $Sortie "00-synthese.txt")
$lignes
