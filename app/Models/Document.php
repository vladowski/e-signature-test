<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Document",
 *     type="object",
 *     title="Document",
 *     required={"id", "file_path", "user_id", "status"},
 *     @OA\Property(
 *         property="id",
 *         description="UUID of the document",
 *         type="string",
 *         example="1"
 *     ),
 *     @OA\Property(
 *         property="file_path",
 *         description="File path of the document",
 *         type="string",
 *         example="documents/yourfile.pdf"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         description="ID of the user who owns the document",
 *         type="string",
 *         example="1"
 *     ),
 *     @OA\Property(
 *         property="signed_file_path",
 *         description="File path of the signed document",
 *         type="string",
 *         nullable=true,
 *         example="null"
 *     ),
 *     @OA\Property(
 *         property="signed_at",
 *         description="Signing timestamp",
 *         type="string",
 *         nullable=true,
 *         format="date-time",
 *         example="null"
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
 * )
 */
class Document extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'file_path', 'signed_file_path', 'signed_at'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public $incrementing = false;
    protected $keyType = 'string';

    public function signatureRequests()
    {
        return $this->hasMany(SignatureRequest::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
