<?php
namespace App;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Vendor extends Authenticatable
{
    use Notifiable;
	protected $guard = 'vendor';
    protected $table = 'vendors';

    protected $fillable = ['email',  'password','name'];

    protected $hidden = ['password',  'remember_token'];


}