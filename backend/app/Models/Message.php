<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'workspace_id','campaign_id','campaign_send_id','contact_id',
        'to_email','subject','from_name','from_email','reply_to',
        'html_body','text_body','status','provider','provider_message_id',
        'last_error','unsubscribe_signature','sent_at','delivered_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function send(): BelongsTo
    {
        return $this->belongsTo(CampaignSend::class, 'campaign_send_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function trackingLinks(): HasMany
    {
        return $this->hasMany(TrackingLink::class);
    }
}
