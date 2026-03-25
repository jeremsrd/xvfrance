<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Models\RugbyMatch;
use Illuminate\Console\Command;

class GenerateSlugs extends Command
{
    protected $signature = 'slugs:generate {--force : Régénérer tous les slugs, même ceux déjà existants}';
    protected $description = 'Génère les slugs pour tous les matches et joueurs';

    public function handle(): int
    {
        $force = $this->option('force');

        $this->generateMatchSlugs($force);
        $this->generatePlayerSlugs($force);

        return self::SUCCESS;
    }

    private function generateMatchSlugs(bool $force): void
    {
        $query = RugbyMatch::with('opponent');
        if (!$force) {
            $query->whereNull('slug');
        }

        $matches = $query->get();
        $this->info("Matches à traiter : {$matches->count()}");

        $used = [];
        $bar = $this->output->createProgressBar($matches->count());

        foreach ($matches as $match) {
            $slug = $match->generateSlug();

            // Gérer les doublons (ex: 2 matches le même jour contre le même adversaire)
            if (isset($used[$slug])) {
                $used[$slug]++;
                $slug .= '-' . $used[$slug];
            } else {
                $used[$slug] = 1;
            }

            $match->update(['slug' => $slug]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Slugs matches générés : {$matches->count()}");
    }

    private function generatePlayerSlugs(bool $force): void
    {
        $query = Player::query();
        if (!$force) {
            $query->whereNull('slug');
        }

        $players = $query->get();
        $this->info("Joueurs à traiter : {$players->count()}");

        $used = [];
        $bar = $this->output->createProgressBar($players->count());

        foreach ($players as $player) {
            $slug = $player->generateSlug();

            // Gérer les homonymes
            if (isset($used[$slug])) {
                $used[$slug]++;
                $slug .= '-' . $used[$slug];
            } else {
                $used[$slug] = 1;
            }

            $player->update(['slug' => $slug]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Slugs joueurs générés : {$players->count()}");
    }
}
