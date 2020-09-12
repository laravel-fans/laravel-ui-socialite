<?php

namespace LaravelFans\UiSocialite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Socialite\AbstractUser;

class SocialAccount extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'raw' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function sync(AbstractUser $remoteUser)
    {
        $this->nickname = $remoteUser->getNickname();
        $this->name = $remoteUser->getName();
        $this->email = $remoteUser->getEmail();
        $this->avatar = $remoteUser->getAvatar();
        $this->raw = $remoteUser->getRaw();
        $this->access_token = $remoteUser->token;
        $this->refresh_token = $remoteUser->refreshToken; // not always provided
        $this->expires_in = $remoteUser->expiresIn;
        $this->save();
    }
}
