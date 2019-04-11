<?php

namespace UtilERP\Menu;

use Closure;
use UtilERP\Str;
use Illuminate\Translation\Translator as Lang;
use Illuminate\Contracts\Routing\UrlGenerator as Url;

class Menu
{
    /**
     * @var \Illuminate\Contracts\Routing\UrlGenerator
     */
    protected $url;
    /**
     * @var \Illuminate\Translation\Translator
     */
    protected $lang;
    /**
     * Default CSS class(es) for the menu
     *
     * @var string
     */ 
    protected $activeClass = 'active';
    /**
     * Default CSS class(es) for the sub-menus
     *
     * @var string
     */
    protected $items;
    /**
     * Current item's id (active menu item), it will be obtained after the menu
     * is rendered.
     *
     * @var string
     */
    protected $currentId;
    /**
     * Whether all URLs should be secure (https) or not (http) by default
     *
     * @var bool
     */
    protected $defaultSecure = false;
    /**
     * Active URL (this will be taken from the Url::current method by default)
     *
     * @var string
     */
    protected $activeUrl;
    /**
     * Allow dynamic parameters for routes and actions.
     *
     * @var array
     */
    protected $params = array();

    /**
     * 
     *Almacenar una resolución de URL activa personalizada opcional.
     *
     * @var \Closure
     */
    protected $activeUrlResolver;

    /**
     * Crear instancia Menu
     *
     * Normalmente se creará un menú desde la clase del generador de menús hasta
     * La fachada del menú (Menu :: make).
     *
     * @param Url $url
     * @param $items
     */
    public function __construct(URL $url, $items)
    {
        $this->url = $url;
        $this->items = $items;
        $this->activeUrl = $this->url->current();
        $this->baseUrl = $this->url->to('');
    }

    /**
     * 
     * Llame al método de renderizado si alguien intenta imprimir el método Menu :: make.
     *
     * Ejemplo: {!! Menu::make('items') !!}
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Establecer el componente traductor opcional
     *
     * @param Lang $lang
     * @return $this
     */
    public function setLang(Lang $lang)
    {
        $this->lang = $lang;
        return $this;
    }

     /**
     * Establecer los parámetros dinámicos para las rutas y URLs.
     *
     * @param array $values
     * @return \Styde\Html\Menu\Menu $this
     */
    public function setParams(array $values = array())
    {
        $this->params = $values;
        return $this;
    }

    /**
     * Establecer un parámetro dinámico para las rutas y URLs.
     *
     * @param $key
     * @param $value
     * @return \Styde\Html\Menu\Menu $this
     */
    public function setParam($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * Establecer las clases CSS para el elemento activo
     *
     * @param $value
     * @return \Styde\Html\Menu\Menu $this
     */
    public function setActiveClass($value)
    {
        $this->activeClass = $value;
        return $this;
    }

    /**
     * Establecer si todas las URL deben ser seguras (https) de forma predeterminada o no (http)
     *
     * @param $value
     * @return \Styde\Html\Menu\Menu $this
     */
    public function setDefaultSecure($value)
    {
        $this->defaultSecure = $value;
        return $this;
    }

    /**
     * Establezca una devolución de llamada personalizada para resolver la lógica y determinar si una URL está activa o no.
     *
     * @param Closure $closure
     */
    public function setActiveUrlResolver(Closure $closure)
    {
        $this->activeUrlResolver = $closure;
    }

    /**
     * Establecer la URL actual
     *
     * @param $value
     */
    public function setActiveUrl($value)
    {
        $this->activeUrl = $value;
    }

    /**
     * Establecer la URL base
     *
     * @param $value
     */
    public function setBaseUrl($value)
    {
        $this->baseUrl = $value;
    }

    /**
     * Permítanos obtener el ID del artículo activo.
     *
     * Esto estará disponible solo después de que se haya renderizado el menú.
     *
     * @return string
     */
    public function getCurrentId()
    {
        return $this->currentId;
    }

    /**
     * Representa un nuevo menú.
     *
     * @param string|null $customTemplate
     * @return string the menu's HTML
     */
    public function render($customTemplate = null)
    {
        $items = $this->generateItems($this->items);

        // return $this->theme->render(
        //     $customTemplate,
        //     ['items' => $items, 'class' => $this->class],
        //     'menu'
        // );
    }

    /**
     * Genere y obtenga la matriz de elementos del menú, pero no renderizará el menú
     *
     * @return array
     */
    public function getItems()
    {
        return $this->generateItems($this->items);
    }

    public function checkAccess(array $options)
    {
        if ($this->accessHandler==null) {
            return true;
        }

        foreach (['allows', 'check', 'denies'] as $gateOption) {
            if (isset($options[$gateOption]) && is_array($options[$gateOption])) {
                $options[$gateOption] = $this->replaceDynamicParameters($options[$gateOption]);
            }
        }

        return $this->traitCheckAccess($options);
    }

     /**
     * Genera los elementos para un menú o submenú.
     *
     * Este método se llamará a sí mismo si un elemento tiene una tecla 'submenú'.
     *
     * @param array $items
     * @return array
     */
    protected function generateItems($items)
    {
        foreach ($items as $id => &$values) {
            $values = $this->setDefaultValues($id, $values);

            if (!$this->checkAccess($values)) {
                unset($items[$id]);
                continue;
            }

            $values['title'] = $this->getTitle($id, $values['title']);

            $values['url'] = $this->generateUrl($values);

            if (isset($values['submenu'])) {
                $values['submenu'] = $this->generateItems($values['submenu']);
            }

            if ($this->isActiveUrl($values)) {
                $values['active'] = true;
                $this->currentId = $id;
            } elseif (isset ($values['submenu'])) {
                // Check if there is an active item in the submenu, if
                // so it'll mark the current item as active as well.
                foreach ($values['submenu'] as $subitem) {
                    if ($subitem['active']) {
                        $values['active'] = true;
                        break;
                    }
                }
            }

            if ($values['active']) {
                $values['class'] .= ' '.$this->activeClass;
            }

            // if ($values['submenu']) {
            //     $values['class'] .= ' '.$this->dropDownClass;
            // }

            $values['class'] = trim($values['class']);

            unset(
                $values['callback'], $values['logged'], $values['roles'], $values['secure'],
                $values['params'], $values['route'], $values['action'], $values['full_url'],
                $values['allows'], $values['check'], $values['denies'], $values['exact']
            );
        }

        return $items;
    }

    /**
     * Combinar los valores predeterminados para un elemento de menú
     *
     * @param $id
     * @param array $values
     * @return array
     */
    protected function setDefaultValues($id, array $values)
    {
        return array_merge([
            'class'   => '',
            'submenu' => null,
            'id'      => $id,
            'active'  => false
        ], $values);
    }

    /**
     * Comprueba si esta es la URL actual o no
     *
     * @param array $values
     * @return bool
     */
    protected function isActiveUrl(array $values)
    {
        // Do we have a custom resolver? If so, use it:
        if($activeUrlResolver = $this->activeUrlResolver) {
            return $activeUrlResolver($values);
        }

        // If the current URL is the base URL or the exact attribute is set to true, then check for the exact URL
        if ($values['exact'] ?? false || $values['url'] == $this->baseUrl) {
            return $this->activeUrl === $values['url'];
        }

        // Otherwise use the default resolver:
        return strpos($this->activeUrl, $values['url']) === 0;
    }

    /**
     * Returns the menu's title. The title is determined following this order:
     *
     * 1. If a title is set then it will be returned and used as the menu title.
     * 2. If a translator is set this function will rely on the translateTitle
     * method (see below).
     * 3. Otherwise it will transform the item $key string to title format.
     *
     * @param $key
     * @param $title
     * @return string
     */
    protected function getTitle($key, &$title)
    {
        if (isset($title)) {
            return $title;
        }

        if(!is_null($this->lang)) {
            return $this->translateTitle($key);
        }

        return Str::title($key);
    }

    /**
     * Traduce y devuelve un título para un elemento del menú.
     *
     * Este método intentará encontrar un "menu.key_item" a través del traductor
     * componente. Si no se encuentra ninguna traducción para este artículo, intentará
     * transformar la cadena $ key del elemento en un formato legible por el título.
     *
     * @param $key
     * @return string
     */
    protected function translateTitle($key)
    {
        $translation = $this->lang->get('menu.'.$key);

        if ($translation != 'menu.'.$key) {
            return $translation;
        }

        return Str::title($key);
    }

    /**
     * Retrieve a route or action name and its parameters
     *
     * If $params is a string, then it returns it as the name of the route or
     * action and the parameters will be an empty array.
     *
     * If it is an array then it takes the first element as the name of the
     * route or action and the other elements as the parameters.
     *
     * Then it will try to replace any dynamic parameters (relying on the
     * replaceDynamicParameters method, see below)
     *
     * Finally it will return an array where the first value will be the name of
     * the route or action and the second value will be the array of parameters.
     *
     * @param $params
     * @return array
     */
    protected function getRouteAndParameters($params)
    {
        if (is_string($params)) {
            return [$params, []];
        }

        return [
            // The first position in the array is the route or action name
            array_shift($params),
            // After that they are parameters and they could be dynamic
            $this->replaceDynamicParameters($params)
        ];
    }

    /**
     * Permite parámetros variables o dinámicos para todas las rutas y URLs del menú.
     *
     * Simplemente precede el nombre del parámetro con ":"
     * Por ejemplo:: user_id
     *
     * Este método recorrerá todos los parámetros y reemplazará la dinámica.
     * Los que tienen sus valores correspondientes almacenados a través de setParams y
     * setParam methods,
     *
     * Si no se encuentra un valor dinámico, se devolverá el valor literal.
     *
     * @param array $params
     * @return array
     */
    protected function replaceDynamicParameters(array $params)
    {
        foreach ($params as &$param) {
            if (strpos($param, ':') !== 0) {
                continue;
            }
            $name = substr($param, 1);
            if (isset($this->params[$name])) {
                $param = $this->params[$name];
            }
        }

        return $params;
    }

    /**
     * Genera la URL del elemento del menú, utilizando cualquiera de las siguientes opciones, en orden:
     *
     * Si pasa una clave 'full_url' dentro de la configuración del elemento, en ese caso
     * lo devolverá como la URL sin ninguna acción adicional.
     *
     * Si pasa una clave 'url', llamará al método Url :: to para completar
     * la URL base, también puede especificar una clave 'segura' para indicar si
     * Esta URL debe ser segura o no. De lo contrario, la opción defaultSecure
     * ser usado.
     *
     * Si pasa una clave de 'ruta', llamará Url :: ruta
     *
     * Si pasa una 'acción', llamará al método Url :: action en su lugar.
     *
     * Si necesita pasar parámetros para la url, la ruta o la acción, simplemente especifique
     * una matriz donde la primera posición será la url, la ruta o el nombre de la acción
     * y el resto de la matriz contendrá los parámetros. Puede especificar
     * Parámetros dinámicos (ver métodos anteriores).
     *
     * Si no se encuentra ninguna de estas opciones, esta función simplemente regresará
     * un marcador de posición (#).
     *
     * @param $values
     * @return mixed
     */
    protected function generateUrl($values)
    {
        if (isset($values['full_url'])) {
            return $values['full_url'];
        }

        if (isset($values['url'])) {
            list($url, $params) = $this->getRouteAndParameters($values['url']);
            $secure = isset($values['secure']) ? $values['secure'] : $this->defaultSecure;
            return $this->url->to($url, $params, $secure);
        }

        if (isset($values['route'])) {
            list($route, $params) = $this->getRouteAndParameters($values['route']);
            return $this->url->route($route, $params);
        }

        if (isset($values['action'])) {
            list($route, $params) = $this->getRouteAndParameters($values['action']);
            return $this->url->action($route, $params);
        }

        return '#';
    }
}