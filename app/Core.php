<?php
/**
 * Micro Application Core
 *
 * @author Rob Tull <robmtull@me.com>
 */
date_default_timezone_set('America/Chicago');

define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);
define('BASE_PATH', dirname(dirname(__FILE__)));
define('APP_PATH', BASE_PATH . DS . 'app');         // Application base dir
define('TMPL_PATH', BASE_PATH . DS . 'template');   // Templates base dir
define('LIB_PATH', APP_PATH . DS . 'Libraries');    // Third party libraries

set_include_path(BASE_PATH . PS . get_include_path());
session_save_path(BASE_PATH . DS . Core::getConfig()->session->loc);

/**
 * Autoload classes
 *
 * @param string $identifier
 */
function autoLoad($identifier) {
    $class_file = APP_PATH . DS . str_replace('_', DS, $identifier) . '.php';

    if (file_exists($class_file)) {
        require_once($class_file);
    } else {
        die ("Class file $class_file does not exist!");
    }
}

/**
 * Register autoLoad method as autoloader
 */
spl_autoload_register('autoLoad');

final class Core {
    /**
     * Stores the application configuration
     */
    protected static $_config = NULL;

    /**
     * Stores various URL and request information
     */
    protected static $_domain = '';
    protected static $_url = '';
    protected static $_base_url = '';
    protected static $_request = '';

    /**
     * Stores singleton class instances
     */
    protected static $_singleton_cache = array();

    /**
     * Initializes the application
     */
    public static function init() {
        session_start();
        self::dispatchRequest();
    }

    /**
     * Returns the XML config
     *
     * @return object
     */
    public static function getConfig() {
        if (!self::$_config) {
            if (file_exists('etc/config.xml')) {
                self::$_config = simplexml_load_file('etc/config.xml');
            } else {
                die ('Config file does not exist!');
            }
        }
        return self::$_config;
    }

    /**
     * Returns the current URL
     *
     * @return string
     */
    public static function getUrl() {
        if(!self::$_url) {
            self::$_url = self::getDomain() . self::getRequest();
        }
        return self::$_url;
    }

    /**
     * Returns the current domain name
     *
     * @return string
     */
    public static function getDomain() {
        if (!self::$_domain) {
            if (isset($_SERVER['HTTPS']) && !strcasecmp($_SERVER['HTTPS'], 'on')) {
                $protocol = 'https';
            } else {
                $protocol = 'http';
            }
            self::$_domain = $protocol . '://' . $_SERVER['HTTP_HOST'];
        }
        return self::$_domain;
    }

    /**
     * Returns the base portion of the URL
     *
     * @return string
     */
    public static function getBaseUrl() {
        if (!self::$_base_url) {
            self::$_base_url = self::getDomain() . dirname($_SERVER['PHP_SELF']) . '/';
        }
        return self::$_base_url;
    }

    /**
     * Returns the current page request
     *
     * @return string
     */
    public static function getRequest() {
        if (!self::$_request) {
            self::$_request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }
        return self::$_request;
    }

    /**
     * Skin URL Helper
     *
     * Checks for the existence of the requested file, and returns the full
     * file path if found
     *
     * @param string $identifier
     * @return string
     */
    public static function getSkinUrl($identifier) {
        $skin_file = self::getBaseUrl() . 'skin/' . $identifier;
        return $skin_file;
    }

    /* Implement media url helper here… */

    /**
     * Return values from $_GET array, or null if requested key is not set
     *
     * @param string $key
     * @return mixed
     */
    public static function getGetVar($key = null) {
        if ($key) {
            if (array_key_exists($key, $_GET)) {
                return $_GET[$key];
            } else {
                return null;
            }
        }
        return $_GET;
    }

    /**
     * Return values from $_POST array, or null if requested key is not set
     *
     * @param string $key
     * @return mixed
     */
    public static function getPostVar($key = null) {
        if ($key) {
            if (array_key_exists($key, $_POST)) {
                return $_POST[$key];
            } else {
                return null;
            }
        }
        return $_POST;
    }

    /**
     * Dispatches the URL request to the appropriate controller
     *
     * If the controller is not specified, then by default the request will be
     * dispatched to the index controller, and the index actin will be called
     *
     * @return void
     * @todo Modify checking method exists with try/catch error handling.
     */
    public static function dispatchRequest() {
        $request_stack = preg_split('/\//', strtolower(self::getRequest()), null, PREG_SPLIT_NO_EMPTY);

        if (array_key_exists(0, $request_stack)) {
            $controllerHandle = ucwords($request_stack[0]);
        } else {
            $controllerHandle = 'Index';
        }

        $controller = self::getClass('Controller_' . $controllerHandle);

        if (array_key_exists(2, $request_stack)) {
            die ('Nested controllers are not supported at this time.');
        } elseif (array_key_exists(1, $request_stack)) {
            $action = $request_stack[1];
        } else {
            $action = 'index';
        }

        // if (!method_exists($controller, $action))
        //     die ("The action \"$action\" does not exist on the $controllerHandle controller.");

        $controller->$action();
    }

    /**
     * Instantiates a class, either as singleton or new instance
     *
     * @param string $identifier
     * @param bool $singleton
     * @return object
     */
    public static function getClass($identifier, $singleton = true) {
        if ($singleton && array_key_exists($identifier, self::$_singleton_cache)) {
            return self::$_singleton_cache[$identifier];
        } else {
            $class = str_replace(' ', '_', $identifier);

            if (!class_exists($class)) {
                die ("Class $class is undefined!");
            } elseif (!$singleton) {
                return new $class;
            } else {
                self::$_singleton_cache[$identifier] = new $class;
                return self::$_singleton_cache[$identifier];
            }
        }
    }

    /**
     * Loads third party libraries not conforming to app structure
     *
     * @param string $file_name Library file name without extension
     * @param bool $require_once Whether lib will require or require_once
     * @return object
     */
    public static function getLibrary($file_name, $require_once = true) {
        $lib_file = LIB_PATH . DS . $file_name . '.php';

        if (!$require_once) {
            require($lib_file);
        } else {
            require_once($lib_file);
        }
    }

    /**
     * Template File Loader
     *
     * Checks if the file exists, and loads file contents if found
     *
     * @param string $identifier
     * @return string
     */
    public static function getTemplate($identifier) {
        $template_file = TMPL_PATH . DS . str_replace('_', DS, $identifier) . '.phtml';

        if (!file_exists($template_file)) {
            die ("Template file $template_file does not exist!");
        }

        ob_start();
        require($template_file);
        $template = ob_get_contents();
        ob_end_clean();

        return $template;
    }
}
