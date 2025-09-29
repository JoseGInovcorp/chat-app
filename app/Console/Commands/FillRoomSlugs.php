<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Room;
use Illuminate\Support\Str;

class FillRoomSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rooms:fill-slugs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Preenche os slugs das salas que ainda não têm slug definido';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $rooms = Room::whereNull('slug')->orWhere('slug', '')->get();

        if ($rooms->isEmpty()) {
            $this->info('Todas as salas já têm slug.');
            return;
        }

        foreach ($rooms as $room) {
            $room->slug = Str::slug($room->name) . '-' . Str::random(6);
            $room->save();

            $this->line("Slug criado para sala [{$room->id}] → {$room->slug}");
        }

        $this->info('Slugs preenchidos com sucesso!');
    }
}
