<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class ImportIcd10 extends Model
{
    use HasUlids;

    protected $table = 'icd10';

    /**
     * Fillable fields untuk mass assignment
     */
    protected $fillable = [
        'parent_id',
        'code',       // Kode ICD-10 (misal: B51)
        'title',      // Judul / nama penyakit
        'chapter',    // Chapter / kategori
        'definition'  // Deskripsi / definisi penyakit
    ];

    /**
     * Jika mau pakai primary key selain 'id'
     * uncomment ini jika id default diganti
     */
    // protected $primaryKey = 'code';
    // public $incrementing = false;
    // protected $keyType = 'string';

    /**
     * Relasi parent-child
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Scope untuk filter kode range (misal B51-B70)
     */
    public function scopeCodeRange($query, $from, $to)
    {
        return $query->where('code', '>=', $from)
                     ->where('code', '<=', $to);
    }

    /**
     * Helper untuk set parent secara otomatis saat create
     */
    public static function createWithParent(array $data, ?self $parent = null)
    {
        if ($parent) {
            $data['parent_id'] = $parent->id;
        }
        return self::create($data);
    }
}
