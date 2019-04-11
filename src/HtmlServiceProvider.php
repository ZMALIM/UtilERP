<?php
namespace UtilERP;

use UtilERP\Menu\Menu;
use UtilERP\Menu\MenuGenerador;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Contracts\Auth\Access\Gate;
use Collective\Html\HtmlServiceProvider as ServiceProvider;

class HtmlServiceProvider extends ServiceProvider
{
    /**
     * Array of options taken from the configuration file (config/html.php) and
     * the default package configuration.
     *
     * @var array
     */
    protected $options;
    /**
     * @var AccessHandler
     */
    protected $accessHandler = null;
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function register()
    {
        parent::register();
        $this->registerMenuGenerator();
    }

    /**
     * Registrar la instancia del generador de menú.
     */
    protected function registerMenuGenerator()
    {
        $this->app->bind('menu', function ($app) {

            $menu = new MenuGenerador(
                $app['url'],
                $app['config']
            );

            if ($this->options['control_access']) {
                $menu->setAccessHandler($app[AccessHandler::class]);
            }

            if ($this->options['translate_texts']) {
                $menu->setLang($app['translator']);
            }

            return $menu;
        });
    }

    /**
     * Obtenga los servicios prestados por el proveedor.
     *
     * @return array
     */
    public function provides()
    {
        return [
            Menu::class,
            'menu'
        ];
    }
}
