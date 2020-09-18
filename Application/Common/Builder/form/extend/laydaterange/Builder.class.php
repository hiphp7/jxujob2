<?php

/**
 * @Author: 李志萌
 * @Date:   2020-04-05 17:58:58
 * @Last Modified by:   李志萌
 * @Last Modified time: 2020-04-05 18:35:06
 */

namespace Common\Builder\form\extend\laydaterange;

class Builder
{

    public function item($name = '', $title = '', $tips = '', $data = [])
    {
        $type = $data['laydatetype'] ? $data['laydatetype'] : 'date';

        return [
            'name'        => $name,
            'title'       => $title,
            'tips'        => $tips,
            'laydatetype' => $type,
        ];
    }

    /**
     * @var array 需要加载的js
     */
    public $js = [
    ];

    /**
     * @var array 需要加载的css
     */
    public $css = [
    ];
}
