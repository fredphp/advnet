<?php

namespace app\admin\controller\singlepage;

use app\common\controller\Backend;
use app\common\model\Singlepage;
use app\common\model\SinglepageCategory;
use app\common\exception\UploadException;
use app\common\library\Upload;
use think\Config;
use think\Exception;
use think\Db;

/**
 * 单页管理
 *
 * @icon fa fa-file
 * @remark 管理网站单页内容，支持富文本编辑
 */
class Page extends Backend
{
    protected $model = null;
    protected $noNeedRight = ['ueditor'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new Singlepage();

        // 获取分类列表用于下拉选择
        $categoryList = SinglepageCategory::getCategoryList(1);
        $categoryData = [0 => '请选择分类'];
        foreach ($categoryList as $k => $v) {
            $categoryData[$v['id']] = $v['name'];
        }
        $this->view->assign('categoryList', $categoryData);

        // 传递分类列表到前端JS (Config.categoryList)
        $categoryJsList = [];
        foreach ($categoryList as $v) {
            $categoryJsList[$v['id']] = $v['name'];
        }
        $this->assignconfig('categoryList', $categoryJsList);
    }

    /**
     * 单页列表（带分页）
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->where($where)->count();
            $list = $this->model->with(['category'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加单页
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('参数不能为空'));
            }

            if (empty($params['title'])) {
                $this->error('页面标题不能为空');
            }
            if (empty($params['category_id'])) {
                $this->error('请选择分类');
            }

            $params['createtime'] = time();
            $params['updatetime'] = time();

            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
        return $this->view->fetch();
    }

    /**
     * 编辑单页
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('参数不能为空'));
            }

            if (empty($params['title'])) {
                $this->error('页面标题不能为空');
            }

            $params['updatetime'] = time();

            try {
                $result = $row->allowField(true)->save($params);
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error($row->getError());
                }
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 删除单页
     */
    public function del($ids = '')
    {
        if (!$this->request->isPost()) {
            $this->error(__('参数错误'));
        }
        $ids = $ids ? $ids : $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }

        $pk = $this->model->getPk();
        $list = $this->model->where($pk, 'in', $ids)->select();

        $count = 0;
        Db::startTrans();
        try {
            foreach ($list as $item) {
                $count += $item->delete();
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        } else {
            $this->error(__('删除失败'));
        }
    }

    /**
     * UEditor服务端接口
     * 处理UEditor的各种请求：配置获取、图片上传、文件上传、视频上传等
     */
    public function ueditor()
    {
        $this->layout = '';
        Config::set('default_return_type', 'json');

        $action = $this->request->get('action', 'config');

        switch ($action) {
            case 'config':
                // 返回UEditor配置
                $result = $this->getUeditorConfig();
                break;

            case 'uploadimage':
                // 上传图片
                $result = $this->ueUpload('image');
                break;

            case 'uploadscrawl':
                // 上传涂鸦（base64图片）
                $result = $this->ueUploadScrawl();
                break;

            case 'uploadvideo':
                // 上传视频
                $result = $this->ueUpload('video');
                break;

            case 'uploadfile':
                // 上传附件
                $result = $this->ueUpload('file');
                break;

            case 'listimage':
                // 图片列表
                $result = $this->ueList('image');
                break;

            case 'listfile':
                // 文件列表
                $result = $this->ueList('file');
                break;

            case 'catchimage':
                // 抓取远程图片
                $result = $this->ueCatchImage();
                break;

            default:
                $result = ['state' => '请求地址出错'];
                break;
        }

        // UEditor要求输出为 "callback函数名(json)" 或 直接json
        $callback = $this->request->get('callback', '');
        if ($callback) {
            header('Content-Type: text/javascript');
            echo $callback . '(' . json_encode($result) . ')';
        } else {
            header('Content-Type: application/json');
            echo json_encode($result);
        }
        exit;
    }

    /**
     * 获取UEditor后端配置
     */
    private function getUeditorConfig()
    {
        // 还原upload配置
        Config::load(APP_PATH . 'extra/upload.php', 'upload');
        $uploadConfig = Config::get('upload');

        $cdnUrl = cdnurl('', true);
        $saveKey = $uploadConfig['savekey'] ?: '/uploads/{year}{mon}{day}/{filemd5}{.suffix}';
        // UEditor需要的格式
        $imagePathFormat = str_replace(['{year}{mon}{day}', '{filemd5}', '{.suffix}'], ['{yyyy}{mm}{dd}', '{time}', '{ext}'], $saveKey);

        return [
            /* 上传图片配置项 */
            "imageActionName"   => "uploadimage",            /* 执行上传图片的action名称 */
            "imageFieldName"    => "upfile",                  /* 提交的图片表单名称 */
            "imageMaxSize"      => $uploadConfig['maxsize'] ? str_to_byte($uploadConfig['maxsize']) : 2048000, /* 上传大小限制，单位B */
            "imageAllowFiles"   => [".png", ".jpg", ".jpeg", ".gif", ".bmp", ".webp"], /* 上传图片格式显示 */
            "imageCompressEnable" => true,                    /* 是否压缩图片, 默认是true */
            "imageCompressBorder" => 1600,                    /* 图片压缩最长边限制 */
            "imageInsertAlign"  => "none",                   /* 插入的图片浮动方式 */
            "imageUrlPrefix"    => $cdnUrl,                   /* 图片访问路径前缀 */
            "imagePathFormat"   => $imagePathFormat,          /* 上传保存路径,可以自定义保存路径和文件名格式 */

            /* 涂鸦图片上传配置项 */
            "scrawlActionName"      => "uploadscrawl",       /* 执行上传涂鸦的action名称 */
            "scrawlFieldName"       => "upfile",             /* 提交的图片表单名称 */
            "scrawlPathFormat"      => $imagePathFormat,     /* 上传保存路径,可以自定义保存路径和文件名格式 */
            "scrawlMaxSize"         => 2048000,              /* 上传大小限制，单位B */
            "scrawlUrlPrefix"       => $cdnUrl,              /* 图片访问路径前缀 */
            "scrawlInsertAlign"     => "none",

            /* 截图工具上传 */
            "snapscreenActionName"  => "uploadimage",        /* 执行上传截图的action名称 */
            "snapscreenPathFormat"  => $imagePathFormat,     /* 上传保存路径,可以自定义保存路径和文件名格式 */
            "snapscreenUrlPrefix"   => $cdnUrl,              /* 图片访问路径前缀 */
            "snapscreenInsertAlign" => "none",              /* 插入的图片浮动方式 */

            /* 抓取远程图片配置 */
            "catcherLocalDomain"    => ["127.0.0.1", "localhost", "img.baidu.com"],
            "catcherActionName"     => "catchimage",        /* 执行抓取远程图片的action名称 */
            "catcherFieldName"      => "source",            /* 提交的图片列表表单名称 */
            "catcherPathFormat"     => $imagePathFormat,     /* 上传保存路径,可以自定义保存路径和文件名格式 */
            "catcherUrlPrefix"      => $cdnUrl,              /* 图片访问路径前缀 */
            "catcherMaxSize"        => 2048000,              /* 上传大小限制，单位B */
            "catcherAllowFiles"     => [".png", ".jpg", ".jpeg", ".gif", ".bmp"],

            /* 上传视频配置 */
            "videoActionName"       => "uploadvideo",       /* 执行上传视频的action名称 */
            "videoFieldName"        => "upfile",            /* 提交的视频表单名称 */
            "videoPathFormat"       => $imagePathFormat,     /* 上传保存路径,可以自定义保存路径和文件名格式 */
            "videoUrlPrefix"        => $cdnUrl,              /* 视频访问路径前缀 */
            "videoMaxSize"          => 102400000,           /* 上传大小限制，单位B，默认100MB */
            "videoAllowFiles"       => [".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg", ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid"],

            /* 上传文件配置 */
            "fileActionName"        => "uploadfile",        /* controller里,执行上传视频的action名称 */
            "fileFieldName"         => "upfile",            /* 提交的文件表单名称 */
            "filePathFormat"        => $imagePathFormat,     /* 上传保存路径,可以自定义保存路径和文件名格式 */
            "fileUrlPrefix"         => $cdnUrl,              /* 文件访问路径前缀 */
            "fileMaxSize"           => 51200000,            /* 上传大小限制，单位B，默认50MB */
            "fileAllowFiles"        => [".png", ".jpg", ".jpeg", ".gif", ".bmp", ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg", ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid", ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso", ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"],

            /* 列出指定目录下的图片 */
            "imageManagerActionName" => "listimage",         /* 执行图片管理的action名称 */
            "imageManagerListPath"   => "/uploads/",        /* 指定要列出图片的目录 */
            "imageManagerListSize"   => 20,                  /* 每次列出文件数量 */
            "imageManagerUrlPrefix"  => $cdnUrl,             /* 图片访问路径前缀 */
            "imageManagerInsertAlign" => "none",             /* 插入的图片浮动方式 */
            "imageManagerAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp", ".flv", ".swf"],

            /* 列出指定目录下的文件 */
            "fileManagerActionName"  => "listfile",          /* 执行文件管理的action名称 */
            "fileManagerListPath"    => "/uploads/",        /* 指定要列出文件的目录 */
            "fileManagerUrlPrefix"   => $cdnUrl,             /* 文件访问路径前缀 */
            "fileManagerListSize"   => 20,                   /* 每次列出文件数量 */
            "fileManagerAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp", ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg", ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid", ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso", ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"],
        ];
    }

    /**
     * UEditor上传处理
     * @param string $type 上传类型: image|video|file
     */
    private function ueUpload($type = 'image')
    {
        Config::load(APP_PATH . 'extra/upload.php', 'upload');

        $file = $this->request->file('upfile');
        if (!$file) {
            return ['state' => '未找到上传文件'];
        }

        // 根据类型设置允许的文件格式
        $mimeTypeMap = [
            'image' => 'image/gif,image/jpeg,image/png,image/jpg,image/bmp,image/webp',
            'video' => 'video/mp4,video/webm,video/x-flv,video/quicktime,video/x-msvideo',
            'file'  => '',
        ];

        try {
            $upload = new Upload($file);
            $attachment = $upload->upload();

            return [
                'state'    => 'SUCCESS',
                'url'      => $attachment->url,
                'title'    => $attachment->filename,
                'original' => $file->getInfo('name'),
                'type'     => $file->getInfo('type'),
                'size'     => $file->getInfo('size'),
            ];
        } catch (UploadException $e) {
            return ['state' => $e->getMessage()];
        } catch (Exception $e) {
            return ['state' => $e->getMessage()];
        }
    }

    /**
     * UEditor涂鸦上传处理(base64)
     */
    private function ueUploadScrawl()
    {
        Config::load(APP_PATH . 'extra/upload.php', 'upload');

        $base64Data = $this->request->post('upfile', '');
        if (empty($base64Data)) {
            return ['state' => '未接收到涂鸦数据'];
        }

        // 将base64转换为临时文件再上传
        $base64Data = preg_replace('/^data:image\\/\\w+;base64,/', '', $base64Data);
        $imageData = base64_decode($base64Data);
        if ($imageData === false) {
            return ['state' => '涂鸦数据解码失败'];
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'ue_scrawl_');
        file_put_contents($tempFile, $imageData);

        try {
            $file = new \think\File($tempFile, 'image/png');
            $file->setSaveName('scrawl_' . md5(microtime(true)) . '.png');

            $upload = new Upload($file);
            $attachment = $upload->upload();

            return [
                'state' => 'SUCCESS',
                'url'   => $attachment->url,
                'title' => $attachment->filename,
            ];
        } catch (Exception $e) {
            return ['state' => $e->getMessage()];
        } finally {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    /**
     * UEditor抓取远程图片
     */
    private function ueCatchImage()
    {
        $source = $this->request->post('source', '');
        if (empty($source)) {
            return ['state' => '未接收到图片地址'];
        }

        $source = explode('ue_separate_ue', $source);
        $list = [];

        foreach ($source as $url) {
            $url = trim($url);
            if (empty($url)) continue;

            $list[] = [
                'state' => 'SUCCESS',
                'url'   => $url,
                'source' => $url,
            ];
        }

        return [
            'state' => count($list) > 0 ? 'SUCCESS' : '抓取失败',
            'list'  => $list,
        ];
    }

    /**
     * UEditor在线文件/图片管理列表
     */
    private function ueList($type = 'image')
    {
        $allowFiles = $type === 'image'
            ? ['.png', '.jpg', '.jpeg', '.gif', '.bmp']
            : ['.png', '.jpg', '.jpeg', '.gif', '.bmp', '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb', '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid', '.rar', '.zip', '.tar', '.gz', '.7z', '.bz2', '.cab', '.iso', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.pdf', '.txt', '.md', '.xml'];

        $size = $this->request->get('size', 20);
        $start = $this->request->get('start', 0);

        $files = Db::name('attachment')
            ->where('status', 'normal')
            ->where('mimetype', 'like', 'image%')
            ->order('id', 'desc')
            ->limit($start, $size)
            ->select();

        $list = [];
        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file['url'], PATHINFO_EXTENSION));
            if (in_array('.' . $ext, $allowFiles)) {
                $list[] = [
                    'url' => cdnurl($file['url'], true),
                ];
            }
        }

        return [
            'state' => 'SUCCESS',
            'list'  => $list,
            'start' => $start,
            'total' => count($list),
        ];
    }
}
