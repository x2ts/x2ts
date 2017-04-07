<?php

namespace {

    use x2ts\Toolkit;

    class AirTPL {
        public static function conf($conf = array()) {
            foreach ($conf as $name => $value)
                if (property_exists(__CLASS__, $name)) {
                    self::$$name = $value;
                }
        }

        protected $is_include = false;

        // -------------------------
        // CONFIGURATION
        // -------------------------

        /**
         * Template directory
         *
         * @var string
         */
        protected static $tpl_dir = "tpl";

        /**
         * ICache directory. Is the directory where RainTPL will compile the template and save the cache
         *
         * @var string
         */
        protected static $compile_dir = "tmp";

        /**
         * Template extension.
         *
         * @var string
         */
        protected static $tpl_ext = "hail";

        /**
         * You can define in the black list what string are disabled into the template tags
         *
         * @var array
         */
        protected static $black_list = array('\$this', 'raintpl::', 'self::', '_SESSION', '_SERVER', '_ENV', 'eval', 'exec', 'unlink', 'rmdir');

        /**
         * Store the runtime clips
         *
         * @var array
         */
        protected static $clips = array();

        /**
         * Is the array where RainTPL keep the variables assigned
         *
         * @var array
         */
        public $var = array();

        protected $tpl = array(); // variables to keep the template directories and info

        protected static $config_name_sum = array(); // takes all the config to create the md5 of the file

        // -------------------------


        const CACHE_EXPIRE_TIME = 3600; // default cache expire time = hour

        /**
         * Assign variable
         * eg.    $t->assign('name','mickey');
         *
         * @param string|array $variable
         * @param mixed        $value value assigned to this variable. Not set if variable_name is an associative array
         *
         * @return $this
         */

        public function assign($variable, $value) {
            if (is_array($variable)) {
                $this->var = array_merge($this->var, $variable);
            } else {
                $this->var[(string) $variable] = $value;
            }
            return $this;
        }

        /**
         * @param array $var
         *
         * @return $this
         */
        public function assignRefresh($var) {
            if (is_array($var)) {
                $this->var = $var;
            }
            return $this;
        }

        /**
         * Draw the template
         * eg.    $html = $tpl->draw( 'demo', TRUE ); // return template in string
         * or    $tpl->draw( $tpl_name ); // echo the template
         *
         * @param string $tpl_name template to load
         * @param bool   $is_include
         */

        public function draw(string $tpl_name, bool $is_include = false) {
            $this->is_include = $is_include;
            try {
                // compile the template if necessary and set the template filepath
                $this->check_template($tpl_name);
            } catch (AirTpl_Exception $e) {
                echo $this->printDebug($e);
                return;
            }

            /**
             * The flow of clip is:
             * 1. init the $clips variable to empty array
             * 2. render the entry template
             * 3. find out the clip tag, record its name and convert to placeholder
             * 4. draw the included template with argument $is_include=true
             * 5. capture runtime html partial between clipdef and /clipdef
             * 6. save the captured string into $clips array, use the name of the clip as key
             * 7. after rendering whole page, str_replace all clip tag with the values in $clips referenced by clip name
             * 8. output the replaced string.
             */
            if (!$is_include) {
                self::$clips = array();
                ob_start();
                $this->includeCompiled($this->var, $this->tpl['compiled_filename']);
                $html = ob_get_contents();
                ob_end_clean();
                if (count(self::$clips) > 0) {
                    $html = $this->clip_replace($html);
                }
                echo $html;
            } else {
                $this->includeCompiled($this->var, $this->tpl['compiled_filename']);
            }

            unset($this->tpl);
        }

        /**
         * check if has to compile the template
         *
         * @param $tpl_name
         *
         * @return bool return true if the template has changed
         * @throws AirTpl_Exception
         */
        protected function check_template($tpl_name) {
            if (strpos($tpl_name, '/') === 0) {
                $tpl_name = substr($tpl_name, 1);
            }
            $this->tpl['tpl_filename'] = self::$tpl_dir . '/' . $tpl_name . '.' . self::$tpl_ext;
            $this->tpl['compiled_filename'] = self::$compile_dir . '/' . rawurlencode($tpl_name) . '.hail.php';

            // if the template doesn't exist throw an error
            if (!file_exists($this->tpl['tpl_filename']) || !is_readable($this->tpl['tpl_filename'])) {
                $e = new AirTpl_NotFoundException('Template ' . $tpl_name . ' not exists or not readable!');
                throw $e->setTemplateFile($this->tpl['tpl_filename']);
            }
            // create directories
            if (!@mkdir(self::$compile_dir, 0755, true) && !is_dir(self::$compile_dir)) {
                Toolkit::log(error_get_last(), X_LOG_ERROR);
                throw new AirTpl_Exception('Cannot create compile dir');
            }
            // if the output dir is not writable throw an error
            if (!is_writable(self::$compile_dir)) {
                throw new AirTpl_Exception ('ICache directory ' . self::$compile_dir . 'doesn\'t have write permission. Set write permission or set RAINTPL_CHECK_TEMPLATE_UPDATE to false. More details on http://www.raintpl.com/Documentation/Documentation-for-PHP-developers/Configuration/');
            }

            // compile the template if the compiled-file doesn't exist or the template is updated
            if (!file_exists($this->tpl['compiled_filename']) || filemtime($this->tpl['compiled_filename']) < filemtime($this->tpl['tpl_filename'])) {
                $this->compileFile($this->tpl['tpl_filename'], $this->tpl['compiled_filename']);
                return true;
            }
            return false;
        }

        /**
         * execute stripslaches() on the xml block. Invoqued by preg_replace_callback function below
         *
         * @access protected
         */
        protected function xml_reSubstitution($capture) {
            return "<?php echo '<?xml " . stripslashes($capture[1]) . " ?>'; ?>";
        }

        /**
         * Compile and write the compiled template file
         *
         * @access protected
         *
         * @param string $tpl_filename      template source
         * @param string $compiled_filename compiled template
         *
         * @throws AirTpl_Exception
         */
        protected function compileFile($tpl_filename, $compiled_filename) {

            //read template file
            $this->tpl['source'] = $template_code = file_get_contents($tpl_filename);

            //xml substitution
            $template_code = preg_replace("/<\?xml(.*?)\?>/s", "##XML\\1XML##", $template_code);

            //disable php tag
            $template_code = str_replace(array("<?", "?>"), array("&lt;?", "?&gt;"), $template_code);

            //xml re-substitution
            $template_code = preg_replace_callback("/##XML(.*?)XML##/s", array($this, 'xml_reSubstitution'), $template_code);

            //compile template
            $template_compiled = "<?php if(!class_exists('AirTPL')){exit;}?>" . $this->compileTemplate($template_code);


            // fix the php-eating-newline-after-closing-tag-problem
            $template_compiled = str_replace("?>\n", "?>\n\n", $template_compiled);


            //write compiled file
            file_put_contents($compiled_filename, $template_compiled);
        }

        /**
         * Compile template
         *
         * @access protected
         */
        protected function compileTemplate($template_code) {

            //tag list
            $tag_regexp = array(
                'noparse'       => '(\{noparse\})',
                'noparse_close' => '(\{\/noparse\})',
                'include'       => '(\{include(?: file){0,1}="[^"]*"(?: cache="[^"]*")?\})',
                'clipdef'       => '(\{clipdef(?: name){0,1}="[\w\-]*"\})',
                'clipdef_close' => '(\{\/clipdef\})',
                'clip'          => '(\{clip(?: name){0,1}="[\w\-]*"\})',
                'exported_vars' => '(\$php\.tpl\.exported_vars)',
            );

            $tag_regexp = "/" . implode("|", $tag_regexp) . "/";

            //split the code with the tags regexp
            $template_code = preg_split($tag_regexp, $template_code, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            //compile the code
            return $this->compileCode($template_code);
        }

        /**
         * Compile the code
         *
         * @access protected
         */
        protected function compileCode($parsed_code) {

            //variables initialization
            $compiled_code = $open_if = $no_parse_is_open = $ignore_is_open = null;
            $loop_level = 0;
            $clip_stack = array();

            //read all parsed code
            while ($html = array_shift($parsed_code)) {
                if (strpos($html, '{/noparse}') !== false) {
                    $no_parse_is_open = false;
                } elseif ($no_parse_is_open) {
                    $compiled_code .= $html;
                }//noparse
                elseif (strpos($html, '{noparse}') !== false) {
                    $no_parse_is_open = true;
                }//include
                elseif (preg_match('/\{include(?: file){0,1}="([^"]*)"(?: cache="([^"]*)"){0,1}\}/', $html, $code)) {
                    //variables substitution
                    $include_var = $this->var_replace($code[1], $left_delimiter = null, $right_delimiter = null, $php_left_delimiter = '".', $php_right_delimiter = '."', $loop_level);

                    //dynamic include
                    $compiled_code .= '<?php $tpl = new ' . get_class($this) . ';' .
                        '$tpl_dir_temp = self::$tpl_dir;' .
                        '$tpl->assign( $this->var, null );' .
                        (!$loop_level ? null : '$tpl->assign( "key", $key' . $loop_level . ' ); $tpl->assign( "value", $value' . $loop_level . ' );') .
                        '$tpl->draw( dirname("' . $include_var . '") . ( substr("' . $include_var . '",-1,1) != "/" ? "/" : "" ) . basename("' . $include_var . '"), true );' .
                        '?>';
                } // clipdef
                elseif (preg_match('/\{clipdef(?: name){0,1}="([\w\-]*)"\}/', $html, $code)) {
                    if (empty($code[1])) {
                        $e = new AirTpl_SyntaxException('You must define the clip name for clipdef in ' . $this->tpl['tpl_filename'] . ' template');
                        throw $e->setTemplateFile($this->tpl['tpl_filename']);
                    }

                    array_push($clip_stack, $code[1]);
                    $compiled_code .= '<?php ob_start();?>';
                }// close clipdef tag
                elseif (strpos($html, '{/clipdef}') !== false) {
                    $name = array_pop($clip_stack);
                    if (empty($name)) {
                        $e = new AirTpl_SyntaxException('clipdef closing tag count mismatch the open tag count in ' . $this->tpl['tpl_filename'] . ' template');
                        throw $e->setTemplateFile($this->tpl['tpl_filename']);
                    }

                    $compiled_code .= '<?php self::$clips[\'' . $name . '\'] .= ob_get_contents(); ob_end_clean();?>';
                } // clip output
                elseif (preg_match('/\{clip(?: name){0,1}="([\w\-]*)"\}/', $html, $code)) {
                    $name = $code[1];
                    if (empty($name)) {
                        $e = new AirTpl_SyntaxException('You must provide clip name in ' . $this->tpl['tpl_filename'] . ' template');
                        throw $e->setTemplateFile($this->tpl['tpl_filename']);
                    }
                    $compiled_code .= '<?php if(!isset(self::$clips["' . $name . '"])) self::$clips["' . $name . '"]=""; echo \'' . $code[0] . '\';?>';
                } // exported_vars
                elseif ($html === '$php.tpl.exported_vars') {
                    $compiled_code .= $this->is_include ? $html : '<?php echo json_encode($this->var);?>';
                } //all html code
                else {
                    $compiled_code .= $html;
                }
            }

            if ($open_if > 0) {
                $e = new AirTpl_SyntaxException('Error! You need to close an {if} tag in ' . $this->tpl['tpl_filename'] . ' template');
                throw $e->setTemplateFile($this->tpl['tpl_filename']);
            }
            if (0 !== count($clip_stack)) {
                $e = new AirTpl_SyntaxException('clipdef closing tag count mismatch the open tag count in ' . $this->tpl['tpl_filename'] . ' template');
                throw $e->setTemplateFile($this->tpl['tpl_filename']);
            }
            return $compiled_code;
        }

        /**
         * Reduce a path, eg. www/library/../filepath//file => www/filepath/file
         *
         * @param string $path
         *
         * @return string
         */
        protected function reduce_path($path) {
            $path = str_replace("://", "@not_replace@", $path);
            $path = str_replace("//", "/", $path);
            $path = str_replace("@not_replace@", "://", $path);
            return preg_replace('/\w+\/\.\.\//', '', $path);
        }

        private function var_replace($html, $tag_left_delimiter, $tag_right_delimiter, $php_left_delimiter = null, $php_right_delimiter = null, $loop_level = null, $echo = null) {

            //all variables
            if (preg_match_all('/' . $tag_left_delimiter . '\$(\w+(?:\.\${0,1}[A-Za-z0-9_]+)*(?:(?:\[\${0,1}[A-Za-z0-9_]+\])|(?:\-\>\${0,1}[A-Za-z0-9_]+))*)(.*?)' . $tag_right_delimiter . '/', $html, $matches)) {

                for ($parsed = array(), $i = 0, $n = count($matches[0]); $i < $n; $i++)
                    $parsed[$matches[0][$i]] = array('var' => $matches[1][$i], 'extra_var' => $matches[2][$i]);

                foreach ($parsed as $tag => $array) {

                    //variable name ex: news.title
                    $var = $array['var'];

                    //function and parameters associate to the variable ex: substr:0,100
                    $extra_var = $array['extra_var'];

                    // check if there's any function disabled by black_list
                    $this->function_check($tag);

                    $extra_var = $this->var_replace($extra_var, null, null, null, null, $loop_level);

                    // check if there's an operator = in the variable tags, if there's this is an initialization so it will not output any value
                    $is_init_variable = preg_match("/^[a-z_A-Z\.\[\](\-\>)]*=[^=]*$/", $extra_var);

                    //function associate to variable
                    $function_var = ($extra_var and $extra_var[0] === '|') ? substr($extra_var, 1) : null;

                    //variable path split array (ex. $news.title o $news[title]) or object (ex. $news->title)
                    $temp = preg_split("/\.|\[|\-\>/", $var);

                    //variable name
                    $var_name = $temp[0];

                    //variable path
                    $variable_path = substr($var, strlen($var_name));

                    //parentesis transform [ e ] in [" e in "]
                    $variable_path = str_replace(['[', ']'], ['["', '"]'], $variable_path);

                    //transform .$variable in ["$variable"] and .variable in ["variable"]
                    $variable_path = preg_replace('/\.(\$?\w+)/', '["\\1"]', $variable_path);

                    // if is an assignment also assign the variable to $this->var['value']
                    if ($is_init_variable) {
                        $extra_var = "=\$this->var['{$var_name}']{$variable_path}" . $extra_var;
                    }


                    //if there's a function
                    if ($function_var) {

                        // check if there's a function or a static method and separate, function by parameters
                        $function_var = str_replace("::", "@double_dot@", $function_var);


                        // get the position of the first :
                        if ($dot_position = strpos($function_var, ":")) {

                            // get the function and the parameters
                            $function = substr($function_var, 0, $dot_position);
                            $params = substr($function_var, $dot_position + 1);

                        } else {

                            //get the function
                            $function = str_replace("@double_dot@", "::", $function_var);
                            $params = null;

                        }

                        // replace back the @double_dot@ with ::
                        $function = str_replace("@double_dot@", "::", $function);
                        $params = str_replace("@double_dot@", "::", $params);
                    } else {
                        $function = $params = null;
                    }

                    //if it is inside a loop
                    if ($loop_level) {
                        //verify the variable name
                        if ($var_name === 'key') {
                            $php_var = '$key' . $loop_level;
                        } elseif ($var_name === 'value') {
                            $php_var = '$value' . $loop_level . $variable_path;
                        } elseif ($var_name === 'counter') {
                            $php_var = '$counter' . $loop_level;
                        } else {
                            $php_var = '$' . $var_name . $variable_path;
                        }
                    } else {
                        $php_var = '$' . $var_name . $variable_path;
                    }

                    // compile the variable for php
                    if (null !== $function) {
                        $php_var = $php_left_delimiter . (!$is_init_variable && $echo ? 'echo ' : null) . ($params ? "( $function( $php_var, $params ) )" : "$function( $php_var )") . $php_right_delimiter;
                    } else {
                        $php_var = $php_left_delimiter . (!$is_init_variable && $echo ? 'echo ' : null) . $php_var . $extra_var . $php_right_delimiter;
                    }
                    $html = str_replace($tag, $php_var, $html);
                }
            }

            return $html;
        }

        /**
         * Check if function is in black list (sandbox)
         *
         * @param string $code
         *
         * @throws \AirTpl_SyntaxException
         * @internal param string $tag
         */
        protected function function_check($code) {

            $preg = '#(\W|\s)' . implode('(\W|\s)|(\W|\s)', self::$black_list) . '(\W|\s)#';

            // check if the function is in the black list (or not in white list)
            if (count(self::$black_list) && preg_match($preg, $code, $match)) {

                // find the line of the error
                $rows = explode("\n", $this->tpl['source']);
                for ($line = 0; $line < count($rows) && strpos($rows[$line], $code) === false; $line++) /** @noinspection SuspiciousSemicolonInspection */
                    ;

                // stop the execution of the script
                $e = new AirTpl_SyntaxException('Unallowed syntax in ' . $this->tpl['tpl_filename'] . ' template');
                throw $e->setTemplateFile($this->tpl['tpl_filename'])
                    ->setTag($code)
                    ->setTemplateLine($line);
            }

        }

        /**
         * Prints debug info about exception or passes it further if debug is disabled.
         *
         * @param AirTpl_Exception $e
         *
         * @throws AirTpl_Exception
         * @return string
         */
        protected function printDebug(AirTpl_Exception $e) {
            $output = sprintf('<h2>Exception: %s</h2><h3>%s</h3><p>template: %s</p>',
                get_class($e),
                $e->getMessage(),
                $e->getTemplateFile()
            );
            if ($e instanceof AirTpl_SyntaxException) {
                if (null != $e->getTemplateLine()) {
                    $output .= '<p>line: ' . $e->getTemplateLine() . '</p>';
                }
                if (null != $e->getTag()) {
                    $output .= '<p>in tag: ' . htmlspecialchars($e->getTag()) . '</p>';
                }
                if (null != $e->getTemplateLine() && null != $e->getTag()) {
                    $rows = explode("\n", htmlspecialchars($this->tpl['source']));
                    $rows[$e->getTemplateLine()] = '<font color=red>' . $rows[$e->getTemplateLine()] . '</font>';
                    $output .= '<h3>template code</h3>' . implode('<br />', $rows) . '</pre>';
                }
            }
            $output .= sprintf('<h3>trace</h3><p>In %s on line %d</p><pre>%s</pre>',
                $e->getFile(), $e->getLine(),
                nl2br(htmlspecialchars($e->getTraceAsString()))
            );
            return $output;
        }

        private $included_file_mt = array();

        private function includeCompiled($var, $file) {
            extract($var, EXTR_OVERWRITE);
            if (function_exists('opcache_invalidate')
                && (!isset($this->included_file_mt[$file]) || $this->included_file_mt[$file] < filemtime($file))
            ) {
                $this->included_file_mt[$file] = filemtime($file);
                opcache_invalidate($file);
            }
            include($file);
        }

        /**
         * @param $html
         *
         * @return mixed
         */
        protected function clip_replace($html) {
            $search = $replace = array();
            foreach (self::$clips as $name => $part) {
                $search[] = '{clip="' . $name . '"}';
                $replace[] = $part;
                $search[] = '{clip name="' . $name . '"}';
                $replace[] = $part;
            }
            $html = str_replace($search, $replace, $html);
            return $html;
        }
    }

    /**
     * Basic Rain tpl exception.
     */
    class AirTpl_Exception extends Exception {
        /**
         * Path of template file with error.
         */
        protected $templateFile = '';

        /**
         * Returns path of template file with error.
         *
         * @return string
         */
        public function getTemplateFile() {
            return $this->templateFile;
        }

        /**
         * Sets path of template file with error.
         *
         * @param string $templateFile
         *
         * @return $this
         */
        public function setTemplateFile($templateFile) {
            $this->templateFile = (string) $templateFile;
            return $this;
        }
    }

    /**
     * Exception thrown when template file does not exists.
     */
    class AirTpl_NotFoundException extends AirTpl_Exception {
    }

    /**
     * Exception thrown when syntax error occurs.
     */
    class AirTpl_SyntaxException extends AirTpl_Exception {
        /**
         * Line in template file where error has occured.
         *
         * @var int | null
         */
        protected $templateLine = null;

        /**
         * Tag which caused an error.
         *
         * @var string | null
         */
        protected $tag = null;

        /**
         * Returns line in template file where error has occured
         * or null if line is not defined.
         *
         * @return int | null
         */
        public function getTemplateLine() {
            return $this->templateLine;
        }

        /**
         * Sets  line in template file where error has occured.
         *
         * @param int $templateLine
         *
         * @return $this
         */
        public function setTemplateLine($templateLine) {
            $this->templateLine = (int) $templateLine;
            return $this;
        }

        /**
         * Returns tag which caused an error.
         *
         * @return string
         */
        public function getTag() {
            return $this->tag;
        }

        /**
         * Sets tag which caused an error.
         *
         * @param string $tag
         *
         * @return $this
         */
        public function setTag($tag) {
            $this->tag = (string) $tag;
            return $this;
        }
    }
}

namespace x2ts\view {

    use AirTPL;
    use x2ts\Component;
    use x2ts\ComponentFactory;

    class Simple extends Component implements IView {
        /**
         * @var AirTPL
         */
        private $airTPL;

        private $_layout = 'layout';

        protected static $_conf = [
            'tpl_dir'       => '',
            'tpl_ext'       => 'html',
            'compile_dir'   => '',
            'cacheId'       => 'cache', // string to cache component id or false to disable cache
            'cacheDuration' => 60, // second
        ];

        public function init() {
            AirTPL::conf($this->conf);
            $this->airTPL = new AirTPL();
        }

        /**
         * @param string $title
         *
         * @return $this
         */
        public function setPageTitle(string $title) {
            $this->airTPL->assign('_page_title', $title);
            return $this;
        }

        /**
         * @param string $layout
         *
         * @return $this
         */
        public function setLayout(string $layout) {
            $this->_layout = $layout;
            return $this;
        }

        public function getLayout(): string {
            return $this->_layout;
        }

        /**
         * @param string $tpl
         * @param array  $params  [optional]
         * @param string $cacheId [optional]
         *
         * @return string
         */
        public function render(string $tpl, array $params = [], string $cacheId = '') {
            $useCache = null !== $cacheId && $this->conf['cacheId'];
            if ($useCache) {
                /**
                 * @var \x2ts\cache\ICache $cache
                 */
                $cache = ComponentFactory::getComponent($this->conf['cacheId']);
                $key = "html|$tpl|$cacheId";
                if ($html = $cache->get($key)) {
                    return $html;
                }

                $html = $this->realRender($tpl, $params);
                $cache->set($key, $html, $this->conf['cacheDuration']);
                return $html;
            }

            return $this->realRender($tpl, $params);
        }

        private function realRender($tpl, $params = array()) {
            $this->assign('_content_template', $tpl);
            if (!isset($this->airTPL->var['_page_title'])) {
                $this->airTPL->assign('_page_title', '');
            }
            $this->assignAll($params);
            ob_start();
            $this->airTPL->draw($this->_layout);
            $html = ob_get_contents();
            ob_end_clean();
            return $html;
        }

        /**
         * @param string $name
         * @param mixed  $value
         *
         * @return $this
         */
        public function assign(string $name, $value) {
            $this->airTPL->assign($name, $value);
            return $this;
        }

        /**
         * @param array $params
         *
         * @return $this
         */
        public function assignAll(array $params) {
            $this->airTPL->assign($params, null);
            return $this;
        }
    }
}