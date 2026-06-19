<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Attendance",
 *     type="object",
 *     title="Attendance",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="employee_id", type="string", example="EMP-001"),
 *     @OA\Property(property="date", type="string", format="date", example="2025-05-15"),
 *     @OA\Property(property="status", type="string", enum={"hadir","izin","sakit","alpha"}, example="hadir"),
 *     @OA\Property(property="note", type="string", nullable=true, example=""),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'status',
        'note',
        'created_by_email',
        'created_by_name',
        'local_role',
        'audit_status',
        'audit_receipt_number',
        'central_event_id',
        'event_routing_key',
        'event_published_at',
    ];

    protected $casts = [
        'date' => 'date',
        'event_published_at' => 'datetime',
    ];
}
