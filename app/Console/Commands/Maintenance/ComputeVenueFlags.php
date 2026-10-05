<?php

namespace App\Console\Commands\Maintenance;

use App\Models\Country;
use App\Models\RugbyMatch;
use Illuminate\Console\Command;

class ComputeVenueFlags extends Command
{
    protected $signature = 'xv:compute-venue-flags {--dry-run : Compter les changements sans les appliquer}';

    protected $description = 'Déduit domicile / extérieur / terrain neutre du pays du stade de chaque match';

    public function handle(): int
    {
        $franceId = Country::where('code', 'FRA')->value('id');
        $changed = [];

        foreach (RugbyMatch::with(['venue', 'opponent'])->orderBy('match_date')->get() as $match) {
            $match->applyVenueFlags($franceId);
            if (!$match->isDirty()) {
                continue;
            }

            $changed[] = [
                $match->match_date->format('Y-m-d'),
                $match->opponent->code,
                $match->venue->name,
                $match->is_neutral ? 'neutre' : ($match->is_home ? 'domicile' : 'extérieur'),
            ];
            if (!$this->option('dry-run')) {
                $match->save();
            }
        }

        if ($changed) {
            $this->table(['Date', 'Adversaire', 'Stade', 'Lieu'], $changed);
        }
        $this->info(count($changed) . ' match(es) corrigé(s)' . ($this->option('dry-run') ? ' (dry-run)' : '')
            . ', ' . RugbyMatch::where('is_neutral', true)->count() . ' sur terrain neutre en base.');

        return self::SUCCESS;
    }
}
