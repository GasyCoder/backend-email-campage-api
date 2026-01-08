<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MailingList extends Model
{
    protected $table = 'mailing_lists';

    protected $fillable = ['workspace_id', 'name', 'description'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'list_contact', 'mailing_list_id', 'contact_id');
    }
}
