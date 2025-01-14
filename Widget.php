<?php
namespace TypechoPlugin\BlinkoSync;

use Typecho\Widget\Helper\Form\Element;
use Typecho\Widget\Helper\Form\Element\Checkbox;

class Widget extends \Typecho\Widget
{
    /**
     * 为文章编辑页面添加同步选项
     * 
     * @param \Typecho\Widget\Helper\Form $form
     * @return void
     */
    public static function render($form)
    {
        $syncToBlinko = new Checkbox(
            'syncToBlinko',
            ['sync' => _t('同步到Blinko')],
            ['sync'],
            _t('是否将文章同步到Blinko平台'),
            _t('选中此项后，文章将会被同步到Blinko平台')
        );
        $form->addInput($syncToBlinko);
    }
} 