<?php

use SilverStripe\Core\Environment;
use Toast\SocialPoster\Helpers\SocialPoster;

if ($token = Environment::getEnv('SOCIAL_POSTER_TOKEN')) {
    SocialPoster::config()->access_token = $token;
}