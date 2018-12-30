<?php

namespace sinkcup\LaravelMakeAuthSocialite;

use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}
