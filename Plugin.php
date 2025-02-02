<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 将Typecho博客内容同步到Blinko
 * 
 * @package BlinkoSync
 * @author flyhunterl
 * @version 1.0.1
 * @link https://llingfei.com
 */
class BlinkoSync_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('BlinkoSync_Plugin', 'sync');
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('BlinkoSync_Plugin', 'writePostOptions');
        return _t('插件已经激活，请配置Blinko API设置');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     */
    public static function deactivate()
    {
        return _t('插件已被禁用');
    }

    /**
     * 获取插件配置面板
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $blinkoUrl = new Typecho_Widget_Helper_Form_Element_Text(
            'blinkoUrl',
            null,
            'https://blinko.apidocumentation.com/v1/note/upsert',
            _t('Blinko API URL'),
            _t('请输入Blinko API的URL地址')
        );
        $form->addInput($blinkoUrl);

        $blinkoToken = new Typecho_Widget_Helper_Form_Element_Text(
            'blinkoToken',
            null,
            '',
            _t('Blinko API Token'),
            _t('请输入Blinko的API Token')
        );
        $form->addInput($blinkoToken);
    }

    /**
     * 个人用户的配置面板
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 同步文章到Blinko
     * 
     * @param array $contents 文章内容
     * @param Widget_Contents_Post_Edit $post 文章对象
     */
    public static function sync($contents, $post)
    {
        // 判断是否为新文章
        if (!empty($contents['cid'])) {
            // 如果文章已有ID，说明是更新文章，直接返回
            return $contents;
        }

        // 判断是否勾选同步
        if (empty($_POST['sync_to_blinko'])) {
            return $contents;
        }

        // 获取插件配置
        $config = Typecho_Widget::widget('Widget_Options')->plugin('BlinkoSync');
        
        // 准备POST数据
        $postData = array(
            'content' => $contents['text'],
            'type' => 0,
            'attachments' => array(),
            'isArchived' => null,
            'isTop' => null,
            'isShare' => null,
            'isRecycle' => null,
            'references' => array(null)
        );

        // 设置请求头
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config->blinkoToken
        );

        // 初始化cURL
        $ch = curl_init($config->blinkoUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // 执行请求
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // 检查是否有错误发生
        if (curl_errno($ch)) {
            error_log('Blinko同步失败: ' . curl_error($ch));
        } else if ($httpCode != 200) {
            error_log('Blinko同步失败: HTTP状态码 ' . $httpCode . ', 响应: ' . $response);
        }

        curl_close($ch);

        return $contents;
    }

    /**
     * 在发布文章页面添加同步选项
     */
    public static function writePostOptions()
    {
        echo '<script>';
        echo '$(document).ready(function(){';
        echo "$('#tab-advance').append('<section class=\'typecho-post-option\'><label><input type=\'checkbox\' name=\'sync_to_blinko\' value=\'1\' /> 同步到blinko</label></section>');";
        echo '});';
        echo '</script>';
    }
} 
