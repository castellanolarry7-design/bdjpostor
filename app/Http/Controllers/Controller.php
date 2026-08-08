<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controlador base.
 *
 * El trait AuthorizesRequests es lo que da acceso a $this->authorize(...).
 * Sin él, cada llamada a authorize() lanzaba BadMethodCallException y devolvía
 * un 500, dejando la gestión de usuarios inutilizable.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
