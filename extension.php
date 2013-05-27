<?php

/*
 * (c) Aleksey Orlov <i.trancer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace More;

use Bolt\BaseExtension;

class Extension extends BaseExtension
{
    function info()
    {
        return array(
            'name' => "Markdown Finetune",
            'description' => "An extension provides controls for Markdown field type",
            'keywords' => "bolt, extension, markdown",
            'author' => "Aleksey Orlov",
            'link' => "https://github.com/axsy/bolt-extension-markdown-finetune",
            'version' => "0.1",
            'required_bolt_version' => "1.0.2",
            'highest_bolt_version' => "1.1",
            'type' => "General",
            'first_releasedate' => "2013-05-27",
            'latest_releasedate' => "2013-05-27",
            'dependencies' => "",
            'priority' => 10
        );
    }

    function initialize()
    {
    }

}