<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use BadMethodCallException;

abstract class ReadOnlyViewModel extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = ['*'];

    public function save(array $options = [])
    {
        throw new BadMethodCallException('Akses Ditolak: Operasi penulisan (save) tidak diizinkan pada Database View.');
    }

    public function delete()
    {
        throw new BadMethodCallException('Akses Ditolak: Operasi penghapusan (delete) tidak diizinkan pada Database View.');
    }

    public function newEloquentBuilder($query): Builder
    {
        return new class($query) extends Builder {
            public function update(array $values)
            {
                throw new BadMethodCallException('Akses Ditolak: Operasi mass update tidak diizinkan pada Database View.');
            }

            public function delete()
            {
                throw new BadMethodCallException('Akses Ditolak: Operasi mass delete tidak diizinkan pada Database View.');
            }
        };
    }
}
