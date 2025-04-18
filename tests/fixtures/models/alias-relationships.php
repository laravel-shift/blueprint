<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Salesman extends Model
{
    /** @use HasFactory<\Database\Factories\SalesmanFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'belongs_alias_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'belongs_alias_id' => 'integer',
        ];
    }

    public function lead(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function manyAliases(): HasMany
    {
        return $this->hasMany(ManyModel::class);
    }

    public function belongsAlias(): BelongsTo
    {
        return $this->belongsTo(BelongsModel::class);
    }
}
