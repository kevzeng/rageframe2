<?php
namespace wechat\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use common\helpers\ResultDataHelper;
use common\helpers\StringHelper;
use common\controllers\FileBaseController;
use common\helpers\FileHelper;

/**
 * Class FileController
 * @package wechat\controllers
 * @author jianyan74 <751393839@qq.com>
 */
class FileController extends FileBaseController
{
    protected $extend = [
        'images' => '.jpg',
        'videos' => '.mp4',
        'voices' => '.mp3',
    ];

    /**
     * 下载微信临时资源
     *
     * string media_id 资源id
     * string type images/videos/voices 资源类型
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionDownload()
    {
        $mediaId = Yii::$app->request->post('media_id');
        $type = Yii::$app->request->post('type', 'images');

        $config = Yii::$app->params['uploadConfig'][$type];
        $stream = Yii::$app->wechat->app->media->get($mediaId);

        // 验证接口是否报错
        if ($error = Yii::$app->debris->getWechatError($stream, false))
        {
            return ResultDataHelper::json(422, $error);
        }

        // 文件后缀
        $fileExc = $this->extend[$type];
        $filePath = $config['path'] . date($config['subName'], time()) . "/";
        $fileName = $config['prefix'] . StringHelper::randomNum(time());
        $relativePath = Yii::getAlias("@attachurl/") . $filePath; // 相对路径
        $absolutePath = Yii::getAlias("@attachment/") . $filePath; // 绝对路径
        $fileFullName  = $fileName . $fileExc; // 完整文件名

        if (!FileHelper::mkdirs($absolutePath))
        {
            return ResultDataHelper::json(422, '文件夹创建失败，请确认是否开启attachment文件夹写入权限');
        }

        // 移动文件
        if (!$stream->save($absolutePath, $fileFullName))
        {
            return ResultDataHelper::json(422, '文件移动失败');
        }

        return ResultDataHelper::json(200, '上传成功', [
            'url' => $relativePath . $fileFullName
        ]);
    }
}