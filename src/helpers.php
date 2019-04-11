<?php
if(!function_exists('menu')) {
    /**
     * Generates a new menu (alias of Menu::make)
     *
     * @param $items
     * @param string|null $classes
     * @return string
     */
    function menu($items, $classes = null) {
        return App::make('menu')->make($items, $classes);
    }
}