<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Error de negocio esperado: no hay stock para completar la venta.
 *
 * Existe para poder distinguirlo de un fallo interno. Su mensaje SÍ se muestra
 * al usuario; el de cualquier otra excepción no, porque los mensajes de la base
 * de datos filtran nombres de tablas y columnas.
 */
class StockInsuficienteException extends RuntimeException
{
}
