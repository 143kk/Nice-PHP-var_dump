<?php
/**
 * @author Egor Spivac. golden13@gmail.com
 *
 * Feel free to use code :)
 *
 */

/**
 * Simplifying method. Modified var_dump. Very nice and smart :)
 *
 * @param mix $value variables
 */
function vd()
{
    $args = func_get_args();
    $i = 0;
    foreach ($args AS $var) {
        NDUMPER::vd($var, $i++);
    }
}


// Configuration
$NDUMPER_LIB_CONSTRUCTOR = array(
    'config' => array(
        'expanded'        => true, // Show all levels expanded
        'recursion.limit' => 7,    // Limit of recursion levels (For Symfony2 use less than 15 :) )
        'items.limit'     => 1000, // Limit for items. Not works now. Will be added in next commit. 0 - without limit
    ),

    // Variable = {{varname}} will be replaced by values
    'templates' => array(
        'global.styles' => '<style>
                            .dbgBlock .s {color:#008000;}
                            .dbgBlock .i {color:#0000FF;}
                            .dbgBlock .a {color:#b94a48;}
                            .dbgBlock .o {color:#f89406;}
                            .dbgBlock .k {color:#A94F4F;}
                            .dbgBlock .ok {color:#000000;}
                            .dbgBlock .em {font-style:italic;color:#CCCCCC;}
                            .dbgBlock .n {font-style:italic;color:#CCCCCC;}
                            .dbgBlock {border-radius: 0px 5px 5px 5px;border:solid 1px #006582!important;margin:10px!important;padding:1px!important;font-size:9pt!important;background-color:#FFFFFF!important;color:#000000!important;font-weight:normal;}
                            .dbgBlock PRE {border:none;background-color:inherit;white-space:pre;font-weight:normal;box-shadow: none;font-size:9pt!important;}
                            .dbgBlock .m{display:block;width:auto;background-color:#006582;color:#FFFFFF;padding: 7px 5px 5px 5px;font-size:9pt;height:30px;font-family:Arial;text-shadow: 0 1px 0 #2BA6CB;}
                            .dbgBlock A {display:inline;background-color:#FFFFFF;color:blue;font-size:9pt;}
                            .dbgBlock DIV {margin-left:20px;display:{{hideShow}}}
                            .dbgBlock P {font-size:8pt;color:#222222;}
                            .dbgBlock DIV {border:solid 2px #FFFFFF;font-size:9pt;}
                            .dbgBlock DIV:hover {border:solid 2px #F5F5F5;}
                           </style>',

        'global.js' => '<script language="JavaScript">
                            function dshdbg(id, id2)
                            {
                                var el = document.getElementById(id);
                                var el2 = document.getElementById(id2);
                                if (el.style.display == "") {
                                    el.style.display = "{{hideShow}}";
                                }
                                if (el.style.display == "block" && el.style.display != "") {
                                    el.style.display = "none";
                                    if (typeof el2 != "undefined" && el2 != null) el2.innerHTML = "[+]";
                                } else {
                                    el.style.display = "block";
                                    if (typeof el2 != "undefined" && el2 != null) el2.innerHTML = "[-]";
                                }
                                return false;
                            }
                        </script>',


        'global.block' => '<div class="dbgBlock">
                            <a class="m" href="#" onclick="JavaScript:dshdbg(\'{{blockId}}\', \'{{linkId}}\');">{{name}} ({{type}}) {{size}}
                            <span id="{{linkId}}">[{{char}}]</span></a>
                            <div id="{{blockId}}">
                            <pre>{{content}}<br><span class="t">{{trace}}</span></pre></div>
                            </div>',

        'boolean.value' => '<span class="b">{{value}}</span>',
        'double.value'  => '<span class="d">{{value}}</span>',
        'integer.value' => '<span class="i">({{type}}) </span><span class="i">{{value}}</span>',
        'resource.value' => '<span class="r"}>{{value}}</span>',

        'null.value'    => '<span class="n">{{value}}</span>',
        'empty.value'   => '<span class="em">empty</span>',

    )
);

/**
 * Dumper class
 */
class NDUMPER
{
    // HTML Templates
    private static $_templates = array();

    // config
    private static $_config = array();

    // users Config
    private static $_userConfig = array();

    private static $_localCounter = 0;

    private static $_itemsCount = 0;

    private static $_break = false;

    public static function set($name, $value)
    {
        self::$_userConfig[$name] = $value;
    }
    
    /**
    * Get parameter from userConfig or config
    */
    public static function _get($name)
    {
        if (isset(self::$_userConfig[$name])) return self::$_userConfig[$name];
        if (isset(self::$_config[$name])) return self::$_config[$name];
        return null;
    }
    
    /**
     * Nice var_dump.
     *
     * @global $NVDUMPER_TEMPLATES
     * @param mix $value variable
     */
    public static function vd($value, $position)
    {
        global $NDUMPER_LIB_CONSTRUCTOR;

        self::$_templates = $NDUMPER_LIB_CONSTRUCTOR['templates'];
        self::$_config    = $NDUMPER_LIB_CONSTRUCTOR['config'];

        // replace expanded collapsed default state
        $expanded = self::_get('expanded');
        $replaceExpanded = array(
            '{{hideShow}}' => ($expanded? 'block' : 'none'),
        );
        self::$_templates['global.js'] = strtr(self::$_templates['global.js'], $replaceExpanded);
        self::$_templates['global.styles'] = strtr(self::$_templates['global.styles'], $replaceExpanded);

        $result = '';

        self::$_localCounter++;
        self::$_itemsCount = 0;
        
        if (self::$_localCounter == 1) {
            $result .= self::$_templates['global.styles'];
            $result .= self::$_templates['global.js'];
        }

        $traceArray = self::_getTraceAndName($position);
        $trace = $traceArray['trace'];
        $name  = $traceArray['name'];
        $ids   = self::_getIds(true);
        $type  = gettype($value);
        $size = 0;

        if ($type == 'object') {
            $classInfo = self::_getClassInfo($value);
            $type .= ' = ' . $classInfo['fullName'] . ', methods=' . $classInfo['methodsAllCount'];
            $size = $classInfo['methodsAllCount'];
        }
        if ($type == 'array'){
            $size = count($value);
        }

        $result2 = self::$_templates['global.block'];
        $result2 = strtr($result2, array(
            '{{blockId}}' => $ids['block'],
            '{{linkId}}'  => $ids['link'],
            '{{name}}'    => $name,
            '{{type}}'    => $type,
            '{{size}}'    => $size,
            '{{content}}' => self::_getVarContent($value, 0),
            '{{trace}}'   => $trace,
            '{{char}}'    => ($expanded? '-' : '+'),
        ));

        $result .= $result2;

        echo $result;
    }

    /**
     * Determine json data
     *
     * @param $string
     *
     * @return array('result'=>bool, 'data'=>array)
     */
    private static function _getJson($string)
    {
        //TODO: modify for better performance
        $data = json_decode($string);
        $isJson = false;
        if (json_last_error() === JSON_ERROR_NONE && $data!=null && $data != $string) {
            $isJson = true;
        }
        return array(
            'result' => $isJson,
            'data'   => $data
        );
    }

    /**
     * Trying determine variable name
     * @param $traceArray
     * @param $position
     *
     * @return string
     */
    private static function getVarName($traceArray, $position)
    {
        $result = '';
        if (isset($traceArray[2])) {
            $filename = $traceArray[2]['file'];
            $lineNumber = $traceArray[2]['line'] - 1;
            $fileContent = @file($filename);
            if (empty($fileContent)) {
                return $result;
            }

            if (isset($fileContent[$lineNumber])) {
                $line = $fileContent[$lineNumber];
                preg_match("/vd[\s]{0,}\((.{0,})\)/Ui", $line, $matches);
                if (isset($matches[1])) {
                    $arr = explode(',', $matches[1]);
                    if (count($arr) > 1 && isset($arr[$position])) {
                        $result = trim($arr[$position]);
                    } else {
                        $result = $matches[1];
                    }
                }
            }
        }
        if (strlen($result) > 200) {
            $result = substr($result, 0, 200);
        }
        return $result;
    }

    /**
     * Build id's for block and '+' label
     * @param bool $increment
     *
     * @return array
     */
    protected static function _getIds($increment = false)
    {
        if ($increment) {
            self::$_localCounter++;
        }
        $blockId = 'dbgb' . self::$_localCounter;
        $linkId  = 'dbgl' . self::$_localCounter;
        return array(
            'block' => $blockId,
            'link'  => $linkId
        );
    }

    /**
     * Get back trace
     * @param int $position
     * @return string
     */
    private static function _getTraceAndName($position)
    {
        $trace = debug_backtrace();
        $name = self::getVarName($trace, $position);

        $trace_text = '';
        if (!empty($trace)) {
            $trace = array_reverse($trace);
            $n = 1;
            foreach ($trace as $v) {
                if ($v['function'] == 'vd' && empty($v['class'])) {
                    $bs = '<b>';
                    $be = '</b>';
                } else {
                    $bs = $be = '';
                }
                $trace_text .=
                    ($n++) . '. ' . $bs .
                        ((isset($v['class']))? 'class ' . $v['class'] . '->' : '') . $v['function'] . " in " .
                        ((isset($v['file']))? $v['file'] . ' (' . $v['line'] . ')' : '') . $be . "\n";
            }
            $trace_text = "<b>Trace:</b>\n" . $trace_text;
        }
        return array('name' => $name, 'trace' => $trace_text);
    }

    private static function _getClassInfo($object)
    {
        $result = array(
            'smallName' => '',
            'fullName' => '',
            'methods' => '',
            'methodsAllCount' => 0,
        );
        $className = get_class($object);
        $result['fullName'] = $className;
        if ($className!=='') {
            $pos = strrpos($className, '\\');
            if ($pos !== false) {
                $result['smallName'] = substr($className, $pos + 1);
            } else {
                $result['smallName'] = $className;
            }

            $object = new ReflectionClass($className);
            $result['methodsAllCount'] = count($object->getMethods());
            foreach($object->getMethods() as $method) {
                $result['methods'][] = $method->getName();
            }
        }


        return $result;
    }

    /**
     * Build one var block.
     * @param array $arr
     * @param int   $level
     *
     * @return string
     */
    private static function _getVarContent($arr, $level = 0)
    {
        $str = '';
        $level2 = $level + 1;

        self::$_itemsCount++;
        if (self::_get('items.limit') !== 0 && self::$_itemsCount > self::_get('items.limit')) {
            self::$_break = true;
            $str .= '...items count limit (' . self::_get('items.limit') . ') reached...';
            return $str;
        }

        // limit of recursion
        if ($level > self::_get('recursion.limit')) {
            $str .= '...(too deep)...';
            return $str;
        }

        $varType = gettype($arr);

        switch ($varType) {
            case 'boolean':
                if ($arr === true) {
                    $value = 'true';
                } else {
                    $value = 'false';
                }
                $str2 = strtr(self::$_templates['boolean.value'], array('{{value}}' => $value));
                $str .= $str2;
                break;
                
            case 'double':
                $str2 = strtr(self::$_templates['double.value'], array('{{value}}' => $arr));
                $str .= $str2;
                break;

            case 'string':
                $len = strlen($arr);
                $jsonResult = self::_getJson($arr);

                // Determine json data
                $isJson = false;
                if ($jsonResult['result'] === true) {
                    $isJson = true;
                    $varType .= ', json';
                }
                $jsonArray = $jsonResult['data'];
                $str .= "<span>({$varType}) [{$len}] </span>";
                if ($isJson) {
                    $count = count($jsonArray);
                    //echo $count;
                    if ($count === 0) {
                        $str .= self::$_templates['empty.value'];
                        break;
                    }
                    $str .= '<span class="s">'.$arr.'</span>';
                    $ids = self::_getIds(true);
                    $str .= "<a href=\"#\" onClick=\"return dshdbg('{$ids['block']}', '{$ids['link']}');\"> json data: <span id=\"{$ids['link']}\">[".((self::_get('expanded'))? '-' : '+') . "]</span></a>\n";
                    $str .= "<div id=\"{$ids['block']}\">";
                    foreach ($jsonArray as $key => $value) {
                        $str .= '<span class="k">'.$key.'</span>';
                        $str .= ' => ';
                        $str .= self::_getVarContent($value, $level2) . "\n";
                        if (self::$_break) break;
                    }
                    $str .= "</div>";
                    $str .= "}";
                } else {
                    if ($len > 155) {
                        $title = '';
                        $value = $arr;
                        $ids = self::_getIds(true);
                        $str .= "{ <a href=\"#\" onClick=\"return dshdbg('{$ids['block']}', '{$ids['link']}');\">{$title}<span id=\"{$ids['link']}\">[".((self::_get('expanded'))? '-' : '+') . "]</span></a>\n";
                        $str .= "<div class=\"s\" id=\"{$ids['block']}\">" . $value . '</div>';
                        $str .= "}\n";
                    } else {
                        $value = str_replace("\n", '\n', $arr);
                        $str .= "<span class=\"s\">{$value}</span>";
                    }
                }
                break;

            case 'integer':
                $str2 = strtr(self::$_templates['integer.value'], array('{{type}}' => $varType, '{{value}}' => $arr));
                $str .= $str2;
                //$str .= "<span>({$varType}) </span>";
                //$str .= "<span class=\"i\">{$arr}</span>";
                break;

            case 'resource':
                $str2 = strtr(self::$_templates['resource.value'], array('{{value}}' => $arr));
                $str .= $str2;
                break;

            case 'NULL':
                $str2 = strtr(self::$_templates['null.value'], array('{{value}}' => $arr));
                $str .= $str2;
                break;

            case 'array':
                $count = count($arr);
                $str .= "<span class=\"a\">{$varType} [{$count}]</span> ";
                if ($count === 0) {
                    $str .= self::$_templates['empty.value'];
                    break;
                }
                $str .= "{ ";
                $ids = self::_getIds(true);
                $str .= "<a href=\"#\" onClick=\"return dshdbg('{$ids['block']}', '{$ids['link']}');\"><span id=\"{$ids['link']}\">[".((self::_get('expanded'))? '-' : '+') . "]</span></a>\n";
                $str .= "<div id=\"{$ids['block']}\">";
                foreach ($arr as $key => $value) {
                    $str .= '<span class="k">'.$key.'</span>';
                    $str .= ' => ';
                    $str .= self::_getVarContent($value, $level2) . "\n";
                    if (self::$_break) break;
                }
                $str .= "</div>";
                $str .= "}";
                break;

            case 'object':
                $objectVars = (array)$arr;
                //print_r($objectVars);
                $count = count($objectVars);

                //$classInfo = self::_getObjectStructure($arr);

                $classInfo = self::_getClassInfo($arr);

                $str .= "<span class=\"o\">{$varType} (<span title=\"{$classInfo['fullName']}\">{$classInfo['smallName']}</span>) [{$count}]</span> ";
                if ($count === 0) {
                    $str .= self::$_templates['empty.value'];
                    break;
                }
                $str .= "{ ";
                $ids = self::_getIds(true);
                $str .= "<a href=\"#\" onClick=\"return dshdbg('{$ids['block']}', '{$ids['link']}');\"><span id=\"{$ids['link']}\">[".((self::_get('expanded'))? '-' : '+') . "]</span></a>\n";
                $str .= "<div id=\"{$ids['block']}\">";
                foreach ($objectVars as $key => $value) {
                    $str .= '<span class="ok">'.$key.'</span>';
                    $str .= ' => ';
                    $str .= self::_getVarContent($value, $level2) . "\n";
                    if (self::$_break) break;
                }
                $str .= "</div>";
                $str .= "}\n";
                //$str .= '<div>'.$classInfo.'</div>';
                break;

            default: // "unknown type"
                $str .= "<span>({$varType})</span> ";
                $str .= "<span class=\"u\">{$arr}</span>";
                break;
        }
        return $str;
    }
}

// This is the end. Param param pam! Piiuu!
