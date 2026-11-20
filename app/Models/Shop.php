<?php

namespace App\Models;

use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A host shop (not a shop account).
 *
 * @property int $id
 * @property string $name
 * @property string|null $address
 * @property string $city
 * @property string|null $district
 * @property string $official_name_key
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'address', 'city', 'district', 'official_name_key'])]
class Shop extends Model
{
    /** @use HasFactory<ShopFactory> */
    use HasFactory;

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
