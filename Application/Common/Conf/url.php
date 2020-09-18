<?php 
return array (
  'URL_MODEL' => 0,
  'URL_HTML_SUFFIX' => '.html',
  'URL_PATHINFO_DEPR' => '/',
  'URL_ROUTER_ON' => true,
  'URL_ROUTE_RULES' => 
  array (
    '/^jobfair\/(?!admin)(\w+)$/' => 'jobfair/index/:1',
    '/^mall\/(?!admin)(\w+)$/' => 'mall/index/:1',
  ),
  'QSCMS_VERSION' => '4.2.22',
  'QSCMS_RELEASE' => '2017-07-17 09:27:26',
  'SESSION_OPTIONS' => 
  array (
    'path' => './data/session',
    'domain' => '.jxujob.com',
  ),
  'COOKIE_PATH' => '/',
  'COOKIE_DOMAIN' => '.jxujob.com',
);
