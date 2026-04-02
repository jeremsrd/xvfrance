<?php

namespace App\Console\Commands;

use App\Services\MatchDataValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ValidateMatchData extends Command
{
    protected $signature = 'validate:match-data
        {path : Chemin vers un fichier JSON ou un dossier}';

    protected $description = 'Valide les fichiers JSON de données de matchs sans importer';

    public function handle(): int
    {
        $path = $this->argument('path');
        $files = $this->resolveFiles($path);

        if (empty($files)) {
            $this->components->error("Aucun fichier JSON trouvé : {$path}");
            return self::FAILURE;
        }

        $this->components->info(count($files) . ' fichier(s) à valider');

        $validator = new MatchDataValidator();
        $totalErrors = 0;
        $totalWarnings = 0;
        $totalMatches = 0;

        foreach ($files as $file) {
            $this->newLine();
            $this->components->twoColumnDetail('Fichier', basename($file));

            $content = file_get_contents($file);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->components->error('  JSON invalide : ' . json_last_error_msg());
                $totalErrors++;
                continue;
            }

            // Multi-match or single match
            $matches = isset($data['match_date']) ? [$data] : (is_array($data) ? $data : []);

            foreach ($matches as $i => $matchData) {
                $totalMatches++;
                $label = ($matchData['match_date'] ?? '?') . ' vs ' . ($matchData['opponent_code'] ?? '?');

                $valid = $validator->validate($matchData);

                if ($valid && empty($validator->warnings())) {
                    $this->line("  <info>[OK]</info>   {$label}");
                } else {
                    foreach ($validator->errors() as $err) {
                        $this->line("  <error>[ERR]</error>  {$label} — {$err}");
                        $totalErrors++;
                    }
                    foreach ($validator->warnings() as $warn) {
                        $this->line("  <comment>[WARN]</comment> {$label} — {$warn}");
                        $totalWarnings++;
                    }
                }
            }
        }

        $this->newLine();
        $this->components->twoColumnDetail('Matchs validés', (string) $totalMatches);
        $this->components->twoColumnDetail('Erreurs', (string) $totalErrors);
        $this->components->twoColumnDetail('Warnings', (string) $totalWarnings);

        return $totalErrors > 0 ? self::FAILURE : self::SUCCESS;
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
