<?php
if(define(WEB_ROOT_PATH)) C('SESSION_OPTIONS.path',realpath(WEB_ROOT_PATH.C('SESSION_OPTIONS.path')));
return array(
	'LOAD_EXT_CONFIG'       => 'uc', //扩展配置
	'DEFAULT_THEME'		 	=> 'default',
);