<?php

namespace App\Models\Oficios;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DestinatarioOficio extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'destinatarios_oficio';
}
