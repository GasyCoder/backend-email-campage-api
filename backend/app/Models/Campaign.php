<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Campaign extends Model
{
    protected $fillable = [
        'workspace_id','name','status','subject','preheader',
        'from_name','from_email','reply_to',
        'html_body','text_body','email_template_id','scheduled_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(MailingList::class, 'campaign_lists', 'campaign_id', 'mailing_list_id');
    }
}
