<?php

namespace x2ts;

abstract class Toolkit {
    private static $camelCase = ['t' => [], 'f' => []];

    private static $snake_case = ['t' => [], 'f' => []];

    /**
     * @param $var
     *
     * @throws UncompilableException
     * @return string
     */
    public static function compile($var) {
        if (is_resource($var)) {
            throw new UncompilableException('Resource cannot be compiled to PHP code');
        }

        if (is_object($var) && !$var instanceof ICompilable) {
            throw new UncompilableException('The object is not compilable');
        }
        return var_export($var, true);
    }

    public static function isIndexedArray(array $arr): bool {
        return array_keys($arr) === range(0, count($arr) - 1);
    }

    /**
     * @param array $dst
     * @param array $src
     *
     * @return array
     */
    public static function &override(&$dst, $src) {
        foreach ($src as $key => $value) {
            if (!array_key_exists($key, $dst)) {
                $dst[$key] = $value;
            } else if (is_array($value) && is_array($dst[$key])) {
                if (self::isIndexedArray($dst[$key])) {
                    $dst[$key] = $value;
                } else {
                    self::override($dst[$key], $value);
                }
            } else if (is_int($key)) {
                $dst[] = $value;
            } else {
                $dst[$key] = $value;
            }
        }
        return $dst;
    }

    /**
     * Convert $name to camelCase
     *
     * @param string  $name
     * @param boolean $Pascal
     *
     * @return string
     */
    public static function toCamelCase($name, $Pascal = false) {
        $p = $Pascal ? 't' : 'f';
        if (!isset(self::$camelCase[$p][$name])) {
            $r = self::extractWords($name);
            $r = ucwords($r);
            if (!$Pascal) {
                $r = lcfirst($r);
            }
            $r = strtr($r, array(' ' => ''));
            self::$camelCase[$p][$name] = $r;
        }
        return self::$camelCase[$p][$name];
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private static function extractWords($name) {
        /** @noinspection ReturnFalseInspection */
        if (false === strpos($name, '_')) {
            $r = preg_replace('#[A-Z]#', ' $0', $name);
        } else {
            $r = strtr($name, array('_' => ' '));
        }
        $r = strtolower(ltrim($r));
        return $r;
    }

    /**
     * Convert $name to snake_case
     *
     * @param string  $name
     * @param boolean $Upper_First_Letter
     *
     * @return string
     */
    public static function to_snake_case($name, $Upper_First_Letter = false) {
        $u = $Upper_First_Letter ? 't' : 'f';
        if (!isset(self::$snake_case[$u][$name])) {
            $r = self::extractWords($name);
            if ($Upper_First_Letter) {
                $r = ucwords($r);
            }
            $r = strtr($r, array(' ' => '_'));
            self::$snake_case[$u][$name] = $r;
        }

        return self::$snake_case[$u][$name];
    }

    /**
     * @param $word
     *
     * @return mixed|string
     */
    public static function pluralize($word) {
        if ('' === $word || null === $word) {
            return false;
        }
        $plural = array(
            '/(quiz)$/i'               => '$1zes',
            '/^(ox)$/i'                => '$1en',
            '/([m|l])ouse$/i'          => '$1ice',
            '/(matr|vert|ind)ix|ex$/i' => '$1ices',
            '/(x|ch|ss|sh)$/i'         => '$1es',
            '/([^aeiouy]|qu)ies$/i'    => '$1y',
            '/([^aeiouy]|qu)y$/i'      => '$1ies',
            '/(hive)$/i'               => '$1s',
            '/(?:([^f])fe|([lr])f)$/i' => '$1$2ves',
            '/sis$/i'                  => 'ses',
            '/([ti])um$/i'             => '$1a',
            '/(buffal|tomat)o$/i'      => '$1oes',
            '/(bu)s$/i'                => '$1ses',
            '/(alias|status)/i'        => '$1es',
            '/(octop|vir)us$/i'        => '$1i',
            '/(ax|test)is$/i'          => '$1es',
            '/s$/i'                    => 's',
        );

        $uncountableNouns = [
            'air',
            'anger',
            'beauty',
            'equipment',
            'evidence',
            'fish',
            'information',
            'knowledge',
            'love',
            'money',
            'news',
            'research',
            'rice',
            'safety',
            'series',
            'sheep',
            'species',
            'sugar',
            'tea',
            'water',
        ];

        $irregular = [
            'child'  => 'children',
            'leaf'   => 'leaves',
            'man'    => 'men',
            'move'   => 'moves',
            'person' => 'people',
            'sex'    => 'sexes',
        ];

        $lowerWord = strtolower($word);
        foreach ($uncountableNouns as $noun) {
            if (substr($lowerWord, -strlen($noun)) === $noun) {
                return $word . '_list';
            }
        }

        foreach ($irregular as $_singular => $_plural) {
            $length = strlen($_singular);
            if (substr($lowerWord, -$length) === $_singular) {
                return substr($word, 0, -$length) . $_plural;
            }
        }

        foreach ($plural as $search => $replacement) {
            if (($r = preg_replace($search, $replacement, $word)) !== $word) {
                return $r;
            }
        }
        return $word . 's';
    }

    public static function trace($msg) {
        /** @var Logger $logger */
        $logger = ComponentFactory::getComponent('logger');
        $logger->log($msg, X_LOG_DEBUG, 2);
    }

    public static function log($msg, $logLevel = X_LOG_DEBUG) {
        /** @var Logger $logger */
        $logger = ComponentFactory::getComponent('logger');
        $logger->log($msg, $logLevel, 2);
    }

    public static function randomChars(int $length): string {
        return substr(
            str_replace(['+', '/', '='], '', base64_encode(
                random_bytes($length << 1)
            )),
            0,
            $length
        );
    }
}
