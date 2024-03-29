<?php
return array(
	'QS_index'=>array(
		'alias'=>'QS_index',
		'pname'=>'首页',
		'module'=>'Mobile',
		'controller'=>'Index',
		'action'=>'index',
		'rewrite'=>'',
		'url' => '0',
		'title'=>C('qscms_site_name'),
		'header_title'=>'首页',
		'keywords'=>'',
		'description'=>''
	),
	'QS_companyshow'=>array(
		'alias'=>'QS_companyshow',
		'pname'=>'企业详细页',
		'module'=>'Mobile',
		'controller'=>'Jobs',
		'action'=>'comshow',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'企业详情 - '.C('qscms_site_name'),
		'header_title'=>'企业详情',
		'keywords'=>'',
		'description'=>''
	),
	'QS_jobslist'=>array(
		'alias'=>'QS_jobslist',
		'pname'=>'职位列表页',
		'module'=>'Mobile',
		'controller'=>'Jobs',
		'action'=>'index',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'职位列表 - '.C('qscms_site_name'),
		'header_title'=>'职位列表',
		'keywords'=>'',
		'description'=>''
	),
	'QS_jobsshow'=>array(
		'alias'=>'QS_jobsshow',
		'pname'=>'职位详细页',
		'module'=>'Mobile',
		'controller'=>'Jobs',
		'action'=>'show',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'职位详情 - '.C('qscms_site_name'),
		'header_title'=>'职位详情',
		'keywords'=>'',
		'description'=>''
	),
	'QS_resumelist'=>array(
		'alias'=>'QS_resumelist',
		'pname'=>'简历列表页',
		'module'=>'Mobile',
		'controller'=>'Resume',
		'action'=>'index',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'简历列表 - '.C('qscms_site_name'),
		'header_title'=>'简历列表',
		'keywords'=>'',
		'description'=>''
	),
	'QS_resumeshow'=>array(
		'alias'=>'QS_resumeshow',
		'pname'=>'简历详细页',
		'module'=>'Mobile',
		'controller'=>'Resume',
		'action'=>'show',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'简历详情 - '.C('qscms_site_name'),
		'header_title'=>'简历详情',
		'keywords'=>'',
		'description'=>''
	),
	'QS_newslist'=>array(
		'alias'=>'QS_newslist',
		'pname'=>'资讯列表',
		'module'=>'Mobile',
		'controller'=>'News',
		'action'=>'index',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'资讯列表 - '.C('qscms_site_name'),
		'header_title'=>'资讯列表',
		'keywords'=>'',
		'description'=>''
	),
	'QS_newsshow'=>array(
		'alias'=>'QS_newsshow',
		'pname'=>'资讯详情',
		'module'=>'Mobile',
		'controller'=>'News',
		'action'=>'show',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'资讯详情 - '.C('qscms_site_name'),
		'header_title'=>'资讯详情',
		'keywords'=>'',
		'description'=>''
	),
	'QS_jobfairlist'=>array(
		'alias'=>'QS_jobfairlist',
		'pname'=>'招聘会列表',
		'module'=>'Mobile',
		'controller'=>'Jobfair',
		'action'=>'index',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'招聘会列表 - '.C('qscms_site_name'),
		'header_title'=>'招聘会列表',
		'keywords'=>'',
		'description'=>''
	),
	'QS_jobfairshow'=>array(
		'alias'=>'QS_jobfairshow',
		'pname'=>'招聘会详情',
		'module'=>'Mobile',
		'controller'=>'Jobfair',
		'action'=>'show',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'招聘会详情 - '.C('qscms_site_name'),
		'header_title'=>'招聘会详情',
		'keywords'=>'',
		'description'=>''
	),
	'QS_jobfairexhibitors'=>array(
		'alias'=>'QS_jobfairexhibitors',
		'pname'=>'参会企业',
		'module'=>'Mobile',
		'controller'=>'Jobfair',
		'action'=>'comlist',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'参会企业 - '.C('qscms_site_name'),
		'header_title'=>'参会企业',
		'keywords'=>'',
		'description'=>''
	),
	'QS_mall_index'=>array(
		'alias'=>'QS_mall_index',
		'pname'=>C('qscms_points_byname').'商城',
		'module'=>'Mobile',
		'controller'=>'Mall',
		'action'=>'index',
		'rewrite'=>'',
		'url' => '0',
		'title'=>C('qscms_points_byname').'商城 - '.C('qscms_site_name'),
		'header_title'=>C('qscms_points_byname').'商城',
		'keywords'=>'',
		'description'=>''
	),
	'QS_goods_list'=>array(
		'alias'=>'QS_goods_list',
		'pname'=>'商品列表页',
		'module'=>'Mobile',
		'controller'=>'Mall',
		'action'=>'plist',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'商品列表 - '.C('qscms_site_name'),
		'header_title'=>'商品列表',
		'keywords'=>'',
		'description'=>''
	),
	'QS_goods_show'=>array(
		'alias'=>'QS_goods_show',
		'pname'=>'商品详情',
		'module'=>'Mobile',
		'controller'=>'Mall',
		'action'=>'show',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'商品详情 - '.C('qscms_site_name'),
		'header_title'=>'商品详情',
		'keywords'=>'',
		'description'=>''
	),
	'QS_goods_exchange'=>array(
		'alias'=>'QS_goods_exchange',
		'pname'=>'兑换商品',
		'module'=>'Mobile',
		'controller'=>'Mall',
		'action'=>'create_order',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'兑换商品 - '.C('qscms_site_name'),
		'header_title'=>'兑换商品',
		'keywords'=>'',
		'description'=>''
	),
	'QSall_order_list'=>array(
		'alias'=>'QSall_order_list',
		'pname'=>'兑换记录',
		'module'=>'Mobile',
		'controller'=>'Mall',
		'action'=>'order_list',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'兑换记录 - '.C('qscms_site_name'),
		'header_title'=>'兑换记录',
		'keywords'=>'',
		'description'=>''
	),
	'QSall_order_show'=>array(
		'alias'=>'QSall_order_show',
		'pname'=>'兑换详情',
		'module'=>'Mobile',
		'controller'=>'Mall',
		'action'=>'order_show',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'兑换详情 - '.C('qscms_site_name'),
		'header_title'=>'兑换详情',
		'keywords'=>'',
		'description'=>''
	),
	'QS_noticeshow'=>array(
		'alias'=>'QS_noticeshow',
		'pname'=>'公告详情',
		'module'=>'Mobile',
		'controller'=>'Notice',
		'action'=>'show',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'公告详情 - '.C('qscms_site_name'),
		'header_title'=>'公告详情',
		'keywords'=>'',
		'description'=>''
	),
	'QS_login'=>array(
		'alias'=>'QS_login',
		'pname'=>'会员登录',
		'module'=>'Mobile',
		'controller'=>'Members',
		'action'=>'login',
		'rewrite'=>'',
		'url' => '0',
		'title'=>'会员登录 - '.C('qscms_site_name'),
		'header_title'=>'会员登录',
		'keywords'=>'',
		'description'=>''
	),
);