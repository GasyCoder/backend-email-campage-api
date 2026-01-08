<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Announcement (Simple)',
                'category' => 'announcement',
                'html' => $this->wrapHtml('<h1>{{title}}</h1><p>Hello {{first_name}},</p><p>{{message}}</p><p>— {{signature}}</p>'),
            ],
            [
                'name' => 'Newsletter (Basic)',
                'category' => 'newsletter',
                'html' => $this->wrapHtml('<h1>{{title}}</h1><p>Hi {{first_name}},</p><p>{{content}}</p><hr><p><a href="{{cta_url}}">{{cta_text}}</a></p>'),
            ],
            [
                'name' => 'Promotion (CTA)',
                'category' => 'promo',
                'html' => $this->wrapHtml('<h1>{{offer_title}}</h1><p>Hi {{first_name}},</p><p>{{offer_details}}</p><p><strong>{{price}}</strong></p><p><a href="{{cta_url}}">{{cta_text}}</a></p>'),
            ],
            [
                'name' => 'Event Invitation',
                'category' => 'event',
                'html' => $this->wrapHtml('<h1>{{event_name}}</h1><p>Hello {{first_name}},</p><p>Date: {{event_date}}</p><p>Location: {{event_location}}</p><p>{{event_details}}</p><p><a href="{{cta_url}}">{{cta_text}}</a></p>'),
            ],
            [
                'name' => 'Plain Text-ish',
                'category' => 'plain',
                'html' => $this->wrapHtml('<p>Hello {{first_name}} {{last_name}},</p><p>{{message}}</p><p>Regards,<br>{{signature}}</p>'),
            ],
        ];

        foreach ($templates as $t) {
            EmailTemplate::updateOrCreate(
                ['workspace_id' => null, 'name' => $t['name']],
                ['category' => $t['category'], 'html' => $t['html']]
            );
        }
    }

    private function wrapHtml(string $body): string
    {
        return '<!doctype html><html><head><meta charset="utf-8"></head><body style="font-family:Arial,sans-serif;">'
            . $body
            . '<hr><p style="font-size:12px;color:#666;">If you no longer want to receive these emails, you can unsubscribe.</p>'
            . '</body></html>';
    }
}
