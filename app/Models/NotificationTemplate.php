<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplate extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'category',
        'event_type',
        'channel',
        'subject',
        'body_template',
        'action_url',
        'is_mandatory',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Substitute template placeholders with given parameters.
     * e.g. {employee_name}, {company_name}, {action_url}, {details}
     */
    public function render(array $params): array
    {
        $subject = $this->subject;
        $body = $this->body_template;
        $actionUrl = $this->action_url;

        foreach ($params as $key => $value) {
            $placeholder = '{' . $key . '}';
            $valStr = (string) $value;
            if ($subject) {
                $subject = str_replace($placeholder, $valStr, $subject);
            }
            $body = str_replace($placeholder, $valStr, $body);
            if ($actionUrl) {
                $actionUrl = str_replace($placeholder, $valStr, $actionUrl);
            }
        }

        return [
            'subject' => $subject,
            'body' => $body,
            'action_url' => $actionUrl,
        ];
    }
}

