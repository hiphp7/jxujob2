<?php

/**
 * @Author: 李志萌
 * @Date:   2020-04-05 17:58:58
 * @Last Modified by:   李志萌
 * @Last Modified time: 2020-04-24 09:57:23
 */

namespace Common\builder\form\extend\codemirrorhtml;

class Builder
{

    public function item($name = '', $title = '', $tips = '', $data = [])
    {

        return [
            'name'  => $name,
            'title' => $title,
            'tips'  => $tips,
        ];
    }

    /**
     * @var array 需要加载的js
     */
    public $js = [
        '/Public/static/codemirror-5.38.0/lib/codemirror.js',
        '/Public/static/codemirror-5.38.0/mode/javascript/javascript.js',
        '/Public/static/codemirror-5.38.0/mode/xml/xml.js',
        '/Public/static/codemirror-5.38.0/mode/css/css.js',
        '/Public/static/codemirror-5.38.0/mode/htmlmixed/htmlmixed.js',
        '/Public/static/codemirror-5.38.0/addon/selection/active-line.js',
        // 'codemirrorhtml.js',
    ];

    /**
     * @var array 需要加载的css
     */
    public $css = [
        '/Public/static/codemirror-5.38.0/lib/codemirror.css',
        // 'codemirrorhtml.css',
    ];
}
