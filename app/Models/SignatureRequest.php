<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="SignatureRequest",
 *     type="object",
 *     title="Signature Request",
 *     required={"id", "document_id", "requester_id", "signer_id", "status"},
 *     @OA\Property(
 *         property="id",
 *         description="UUID of the signature request",
 *         type="string",
 *         example="f54c191a-143a-4130-84e4-5529db82f819"
 *     ),
 *     @OA\Property(
 *         property="document_id",
 *         description="UUID of the associated document",
 *         type="string",
 *         example="b11ded5b-86ea-4a97-890f-9d0561bd79de"
 *     ),
 *     @OA\Property(
 *         property="requester_id",
 *         description="ID of the user who requested the signature",
 *         type="string",
 *         example="1"
 *     ),
 *     @OA\Property(
 *         property="signer_id",
 *         description="ID of the user who will sign the document",
 *         type="string",
 *         example="2"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         description="Status of the signature request",
 *         type="string",
 *         example="pending"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         description="Creation timestamp",
 *         type="string",
 *         format="date-time",
 *         example="2024-08-19T14:00:00.000000Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         description="Last update timestamp",
 *         type="string",
 *         format="date-time",
 *         example="2024-08-19T14:00:00.000000Z"
 *     )
 *     )
 * )
 */
class SignatureRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id', 'requester_id', 'signer_id', 'status'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function signer()
    {
        return $this->belongsTo(User::class, 'signer_id');
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}
