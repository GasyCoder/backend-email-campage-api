<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    protected $fillable = [
        'workspace_id', 'email', 'first_name', 'last_name', 'status', 'source',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'contact_tag');
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(MailingList::class, 'list_contact', 'contact_id', 'mailing_list_id');
    }
}
