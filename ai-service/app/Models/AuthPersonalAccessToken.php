<?php
namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

/**
 * Surcharge Sanctum : lit les tokens dans la DB de l'Auth Service
 * (tunisia_platform → table personal_access_tokens).
 */
class AuthPersonalAccessToken extends SanctumToken
{
    protected $connection = 'pgsql_auth';
    protected $table      = 'personal_access_tokens';
}
