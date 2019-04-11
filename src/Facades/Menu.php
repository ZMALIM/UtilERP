<?php
namespace UtilERP\Facades;

use Illuminate\Support\Facades\Facade;

class Menu extends Facade
{
    /**
     * Obtener el nombre registrado del componente.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'menu';
    }
}