<?php
/**
 * @author Egor Spivac. golden13@gmail.com
 *
 * Feel free to use code
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
$NDUMPER_CONFIG = array(
    'expanded' => true, // Show all levels expanded
);


// HTML Templates
$NVDUMPER_TEMPLATES = array(
    'main-styles' => '<style>
                        .dbgBlock .s {color:#008000;}
                        .dbgBlock .i {color:#0000FF;}
                        .dbgBlock .a {color:#b94a48;}
                        .dbgBlock .o {color:#f89406;}
                        .dbgBlock .k {color:#A94F4F;}
                        .dbgBlock .ok {color:#000000;}
                        .dbgBlock {border:solid 1px #CCCCCC!important;padding:10px!important;font-size:9pt!important;background-color:#FFFFFF!important;color:#000000!important;}
                        .dbgBlock PRE {border:none;background-color:inherit;}
                        .dbgBlock .m{display:block;width:auto;background-color:#585858;color:#dcf356;padding:5px;font-size:10pt;height:20px;font-weight:bold;font-family:Arial;}
                        .dbgBlock A {display:inline;background-color:#FFFFFF;color:blue;}
                        .dbgBlock DIV {margin-left:20px;display:' .
                        (($NDUMPER_CONFIG['expanded'])? 'block' : 'none') . '}' .
                        '.dbgBlock P {font-size:8pt;color:#222222;}
                        .dbgBlock DIV {border:solid 2px #FFFFFF;}
                        .dbgBlock DIV:hover {border:solid 2px #F5F5F5;}
                      </style>',


    'main-functions' => '<script language="JavaScript">
                            function dshdbg(id, id2)
                            {
                                var el = document.getElementById(id);
                                var el2 = document.getElementById(id2);
                                if (el.style.display == "") {
                                    el.style.display = "' . (($NDUMPER_CONFIG['expanded'])? 'block' : 'none') . '";
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


    'first-level-block' => '<div class="dbgBlock">
                            <a class="m" href="#" onclick="JavaScript:dshdbg(\'{blockId}\', \'{linkId}\');">{name} ({type}) {size}
                            <span id="{linkId}">[' . (($NDUMPER_CONFIG['expanded'])? '-' : '+') . ']</span></a>
                            <div id="{blockId}">
                            <pre> {content}
                                <div class="t">{trace}</div></pre></div>
                            </div>'
);


/**
 * Dumper class
 */
class NDUMPER
{
    private static $_localCounter = 0;

    /**
     * Trying determine variable name
     * @param $traceArray
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
            if (empty($fileContent)) return $result;
            if (isset($fileContent[$lineNumber])) {
                $line = $fileContent[$lineNumber];
                preg_match("/vd[\s]{0,}\((.{0,})\)/i", $line, $matches);
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
     * Nice var_dump.
     * @global $NVDUMPER_TEMPLATES
     * @param mix $value variable
     *
     */
    public static function vd($value, $position)
    {
        global $NVDUMPER_TEMPLATES;

        $result = '';

        self::$_localCounter++;

        if (self::$_localCounter == 1) {
            $result .= $NVDUMPER_TEMPLATES['main-styles'];
            $result .= $NVDUMPER_TEMPLATES['main-functions'];
        }

        $traceArray = self::_getTraceAndName($position);
        $trace = $traceArray['trace'];
        $name  = $traceArray['name'];
        $ids   = self::_getIds(true);
        $type  = gettype($value);

        $result2 = $NVDUMPER_TEMPLATES['first-level-block'];
        $result2 = strtr($result2, array(
            '{blockId}' => $ids['block'],
            '{linkId}' => $ids['link'],
            '{name}'  => $name,
            '{type}' => $type,
            '{size}' => count($value),
            '{content}' => self::_getVarContent($value, 0),
            '{trace}' => $trace
        ));

        $result .= $result2;

        echo $result;
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
     * Get trace root
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


    /**
     * Build one var block.
     * @global $NDUMPER_CONFIG
     * @param array $arr
     * @param int   $level
     *
     * @return string
     */
    private static function _getVarContent($arr, $level = 0)
    {
        global $NDUMPER_CONFIG;

        $str = '';
        $level2 = $level + 1;

        // limit of recursion
        if ($level > 15) {
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
                $str .= "<span class=\"b\">{$value}</span>";
                break;
            case 'double':
                $str .= "<span class=\"d\">{$arr}</span>";
                break;
            case 'string':
                $strlen = strlen($arr);
                $str .= "<span>({$varType}) [{$strlen}] </span>";
                if ($strlen > 255) {
                    $title = '...';
                    $value = $arr;
                    $ids = self::_getIds(true);
                    $str .= "<a href=\"#\" onClick=\"return dshdbg('{$ids['block']}', '{$ids['link']}');\">{$title}<span id=\"{$ids['link']}\">".(($NDUMPER_CONFIG['expanded'])? '-' : '+') . "</span></a> => {\n";
                    $str .= "<div class=\"s\" id=\"{$ids['block']}\">" . $value . '</div>';
                    $str .= "}\n";
                } else {
                    $value = str_replace("\n",'\n', $arr);
                    $str .= "<span class=\"s\">{$value}</span>";
                }
                break;
            case 'integer':
                $str .= "<span>({$varType}) </span>";
                $str .= "<span class=\"i\">{$arr}</span>";
                break;
            case 'resource':
                $str .= "<span class=\"r\"}>{$arr}</span>";
                break;
            case 'NULL':
                $str .= "<span class=\"n\"}>{$arr}</span>";
                break;
            case 'array':
                $count = count($arr);
                $str .= "<span class=\"a\">{$varType} [{$count}]</span> ";
                $str .= "{ ";
                $ids = self::_getIds(true);
                $str .= "<a href=\"#\" onClick=\"return dshdbg('{$ids['block']}', '{$ids['link']}');\"><span id=\"{$ids['link']}\">[".(($NDUMPER_CONFIG['expanded'])? '-' : '+') . "]</span></a>\n";
                $str .= "<div id=\"{$ids['block']}\">";
                foreach ($arr as $key => $value) {
                    $str .= '<span class="k">'.$key.'</span>';
                    $str .= ' => ';
                    $str .= self::_getVarContent($value, $level2) . "\n";
                }
                $str .= "</div>";
                $str .= "}";
                break;
            case 'object':
                $count = count($arr);
                $str .= "<span class=\"o\">{$varType} [{$count}]</span> ";
                $str .= "{ ";
                $ids = self::_getIds(true);
                $str .= "<a href=\"#\" onClick=\"return dshdbg('{$ids['block']}', '{$ids['link']}');\"><span id=\"{$ids['link']}\">[".(($NDUMPER_CONFIG['expanded'])? '-' : '+') . "]</span></a>\n";
                $str .= "<div id=\"{$ids['block']}\">";
                foreach ($arr as $key => $value) {
                    $str .= '<span class="ok">'.$key.'</span>';
                    $str .= ' => ';
                    $str .= self::_getVarContent($value, $level2) . "\n";
                }
                $str .= "</div>";
                $str .= "}\n";
                break;
            default: // "unknown type"
                $str .= "<span>({$varType})</span> ";
                $str .= "<span class=\"u\">{$arr}</span>";
                break;
        }
        return $str;
    }
}

// This is the end, my beautiful friend
