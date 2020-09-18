<?php
namespace Ucenter\qscmslib\Api;
use Ucenter\Api\PublicTool;

require_cache(APP_PATH . 'Ucenter/Conf/uc.php');

abstract class Api{

    public static $logIndex = 1;

    public static function log($text) {
        if(!APP_DEBUG){
            return ;
        }
        
        if (self::$logIndex == 1) {
            $log = '*************************测试分割线*************************' . PHP_EOL;
            $log .= date("Y-m-d H:i:s", time()) . PHP_EOL;
        }

        $logPath = RUNTIME_PATH . 'Debug/';

        if (!is_dir($logPath)) {
            PublicTool::mkdirs($logPath);
        }

        $logFile = $logPath . 'log'.date("Y-m-d", time()).'.log';

        if (is_array($text)) {
            $log = self::$logIndex . ', array==>';
            $text = var_export($text, true);
        } else {
            $log .= self::$logIndex . ', str==>';
        }

        $log .= $text . PHP_EOL;
        file_put_contents($logFile, $log, FILE_APPEND);
        self::$logIndex++;
    }
}

