<?PHP

namespace ReportApi;

class ApiModule
{
    private static $modules = null;

    public static function register($name, $className)
    {
        if (self::$modules == null) {
            self::$modules = array();
        }
        self::$modules[$name] = '\\ReportApi\\' . $className;
    }

    public static function find($name)
    {
        if (empty($name)) {
            $name = "help";
        }

        if (isset(self::$modules[$name])) {
            $cls = self::$modules[$name];
            return new $cls();
        }
    }

    public function header()
    {
        header('Content-Type: application/json');
    }

    public function content()
    {
        return json_encode(array(
            'error' => 'unknown_action',
            'error_message' => 'The requested action is unknown',
        ));
    }

    public function footer()
    {
    }
}
