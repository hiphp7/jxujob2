<?php
$the_host = $_SERVER['HTTP_HOST'];
$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
if($the_host == 'hr.jxjob.net')
{
header('HTTP/1.1 301 Moved Permanently');
header('Location: http://www.jxujob.com'.$request_uri);//
}
?>