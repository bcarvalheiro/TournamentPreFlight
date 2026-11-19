<?php

namespace App\Models;

use Database\Factories\ImportRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One nightly sync run (M10), written once when it finishes.
 *
 * @property int $id
 * @property string $source pokedata
 * @property Carbon $started_at
 * @property Carbon $finished_at
 * @property string $status ok, failed or aborted
 * @property int $events_created
 * @property int $events_updated
 * @property int $events_flagged
 * @property int $events_unchanged
 * @property string|null $error
 * @property array<string, mixed>|null $payload_sample
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'source', 'started_at', 'finished_at', 'status',
    'events_created', 'events_updated', 'events_flagged', 'events_unchanged',
    'error', 'payload_sample',
])]
class ImportRun extends Model
{
    /** @use HasFactory<ImportRunFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'events_created' => 'integer',
            'events_updated' => 'integer',
            'events_flagged' => 'integer',
            'events_unchanged' => 'integer',
            'payload_sample' => 'array',
        ];
    }
}
