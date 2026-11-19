<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class MailSettings extends Model
{
    protected $fillable = [
        'smtp_username',
        'smtp_password',
    ];

    protected $hidden = [
        'smtp_password',
    ];

    protected function casts(): array
    {
        return [
            'smtp_password' => 'encrypted',
        ];
    }

    public static function current(): ?self
    {
        return static::query()->find(1);
    }

    public function configureMailer(): void
    {
        Mail::purge('smtp');
        Config::set([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => 'smtp',
            'mail.mailers.smtp.url' => null,
            'mail.mailers.smtp.host' => 'smtp.gmail.com',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.smtp.username' => $this->smtp_username,
            'mail.mailers.smtp.password' => $this->smtp_password,
            'mail.from.address' => $this->smtp_username,
            'mail.from.name' => config('app.name'),
        ]);
    }
}
