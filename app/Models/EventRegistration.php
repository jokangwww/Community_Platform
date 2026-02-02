<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'posting_id',
        'student_id',
    ];

    public function posting()
    {
        return $this->belongsTo(Posting::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
