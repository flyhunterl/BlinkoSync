<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 将Typecho博客内容同步到Blinko
 * 
 * @package BlinkoSync
 * @author flyhunterl
 * @version 1.0.0
 * @link https://llingfei.com
 */
class BlinkoSync_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     */
    public static function activate()
    {
        // 修改钩子位置为 write-post.php:writeBottom
        Typecho_Plugin::factory('admin/write-post.php:writeBottom')->render = array('BlinkoSync_Plugin', 'render');
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('BlinkoSync_Plugin', 'sync');
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
        // 检查是否选择了同步
        if (empty($post->request->syncToBlinko) || !in_array('sync', $post->request->syncToBlinko)) {
            return $contents;
        }

        // 获取插件配置
        $config = Typecho_Widget::widget('Widget_Options')->plugin('BlinkoSync');
        
        // 准备POST数据
        $postData = array(
            'content' => $contents['text'],  // 文章内容
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
     * 添加 render 方法来显示同步选项
     */
    public static function render()
    {
        ?>
        <section class="typecho-post-option">
            <label for="syncToBlinko-0" class="typecho-label">同步到Blinko</label>
            <p>
                <span>
                    <input type="checkbox" id="syncToBlinko-0" name="syncToBlinko[]" value="sync" checked="true" />
                    <label for="syncToBlinko-0">将文章同步到Blinko平台</label>
                </span>
            </p>
        </section>
        <?php
    }
} 