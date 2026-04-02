<?php

namespace App\Console\Commands;

use App\Services\MatchDataValidator;
use App\Services\MatchImportService;
use App\Services\PlayerResolverService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportMatchData extends Command
{
    protected $signature = 'import:match-data
        {path : Chemin vers un fichier JSON ou un dossier}
        {--dry-run : Valide sans écrire en BDD}
        {--force : Écrase les données existantes}
        {--skip-existing : Ne touche pas aux matchs déjà remplis}';

    protected $description = 'Importe les données détaillées de matchs (lineups, events, substitutions) depuis des fichiers JSON';

    public function handle(): int
    {
        $path = $this->argument('path');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $skipExisting = $this->option('skip-existing');

        if ($dryRun) {
            $this->components->info('Mode DRY-RUN activé — aucune écriture en base');
        }

        $files = $this->resolveFiles($path);
        if (empty($files)) {
            $this->components->error("Aucun fichier JSON trouvé : {$path}");
            return self::FAILURE;
        }

        $this->components->info(count($files) . ' fichier(s) JSON trouvé(s)');

        $service = new MatchImportService(
            new PlayerResolverService(),
            new MatchDataValidator(),
        );

        foreach ($files as $file) {
            $this->components->twoColumnDetail('Fichier', basename($file));
            $service->importFile($file, $dryRun, $force, $skipExisting);

            // Afficher les messages au fur et à mesure
            foreach ($service->flushMessages() as [$level, $message]) {
                match ($level) {
                    'info' => $this->line("  [OK]   {$message}"),
                    'warn' => $this->line("  <comment>[WARN]</comment> {$message}"),
                    'error' => $this->line("  <error>[ERR]</error>  {$message}"),
                };
            }
        }

        $this->newLine();
        $this->components->twoColumnDetail('=== Résumé ===', '');
        $this->components->twoColumnDetail('Matchs traités', (string) $service->getMatchesProcessed());
        $this->components->twoColumnDetail('Matchs importés', (string) $service->getMatchesImported());
        $this->components->twoColumnDetail('Matchs skippés', (string) $service->getMatchesSkipped());
        $this->components->twoColumnDetail('Joueurs créés', (string) $service->getPlayersCreated());
        $this->components->twoColumnDetail('Warnings', (string) $service->getWarningCount());
        $this->components->twoColumnDetail('Erreurs', (string) $service->getErrorCount());

        return $service->getErrorCount() > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolveFiles(string $path): array
    {
        if (is_file($path) && str_ends_with($path, '.json')) {
            return [$path];
        }

        if (is_dir($path)) {
            return collect(File::allFiles($path))
                ->filter(fn ($f) => $f->getExtension() === 'json')
                ->map(fn ($f) => $f->getRealPath())
                ->sort()
                ->values()
                ->toArray();
        }

        return [];
    }
}
