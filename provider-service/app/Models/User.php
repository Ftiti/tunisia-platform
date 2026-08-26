<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modèle User minimal pour le Provider Service.
 * Pointe sur la DB de l'Auth Service (pgsql_auth → tunisia_platform)
 * afin que Sanctum puisse résoudre le tokenable_type = App\Models\User.
 */
class User extends Authenticatable
{
    protected $connection = 'pgsql_auth';
    protected $table      = 'users';

    protected $fillable   = ['name', 'email', 'role'];
    protected $hidden     = ['password', 'remember_token'];

    use HasApiTokens;
}
