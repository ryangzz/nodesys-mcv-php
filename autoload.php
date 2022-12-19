<?php
function custom_autoloader_controllers($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    if(stream_resolve_include_path ( DOC_ROOT.'/' . $class . '.php'))require_once( __DIR__.'/' . $class . '.php');
}
   
spl_autoload_register('custom_autoloader_controllers');

