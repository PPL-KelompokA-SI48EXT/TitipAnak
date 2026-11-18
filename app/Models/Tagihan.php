<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['pendaftaran_id', 'nomor_tagihan', 'jumlah', 'status', 'diterbitkan_pada', 'jatuh_tempo', 'lunas_pada'])]
class Tagihan extends Model
{
    use SoftDeletes;

    protected $table = 'tagihan';

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'diterbitkan_pada' => 'datetime',
            'jatuh_tempo' => 'date',
            'lunas_pada' => 'datetime',
        ];
    }
}
