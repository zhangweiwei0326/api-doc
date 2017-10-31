<?php
namespace Api\Doc;

use think\Config;
use think\Paginator;
use think\View;
use think\Request;


class DocController
{
    protected $assets_path = "";
    protected $view_path = "";
    protected $root = "";
    /**
     * @var \think\Request Request实例
     */
    protected $request;
    /**
     * @var \think\View 视图类实例
     */
    protected $view;
    /**
     * @var Doc 
     */
    protected $doc;
    /**
     * @var array 资源类型
     */
    protected $mimeType = [
        'xml'  => 'application/xml,text/xml,application/x-xml',
        'json' => 'application/json,text/x-json,application/jsonrequest,text/json',
        'js'   => 'text/javascript,application/javascript,application/x-javascript',
        'css'  => 'text/css',
        'rss'  => 'application/rss+xml',
        'yaml' => 'application/x-yaml,text/yaml',
        'atom' => 'application/atom+xml',
        'pdf'  => 'application/pdf',
        'text' => 'text/plain',
        'png'  => 'image/png',
        'jpg'  => 'image/jpg,image/jpeg,image/pjpeg',
        'gif'  => 'image/gif',
        'csv'  => 'text/csv',
        'html' => 'text/html,application/xhtml+xml,*/*',
    ];

    public function __construct(Request $request = null)
    {
        //有些程序配置了默认json问题
        config('default_return_type', 'html');
        if (is_null($request)) {
            $request = Request::instance();
        }
        $this->request = $request;
        $this->assets_path = __DIR__.DS.'assets'.DS;
        $this->view_path = __DIR__.DS.'view'.DS;
        $config = [
            'view_path' => $this->view_path
        ];
        $this->view =  new View($config);
        $this->doc = new Doc((array)Config::get('doc'));

        $this->view->assign('title',$this->doc->__get("title"));
        $this->view->assign('version',$this->doc->__get("version"));
        $this->view->assign('copyright',$this->doc->__get("copyright"));
        $this->root = $this->request->root();
    }

    /**
     * 显示模板
     * @param $name
     * @return mixed
     */
    protected function show($name, $vars = [], $replace = [], $config = [])
    {
        $re = [
            "__ASSETS__" => $this->root."/doc/assets"
        ];
        $replace = array_merge($re, $replace);
        $vars = array_merge(['root'=>$this->root], $vars);
        return $this->view->fetch($name, $vars, $replace, $config);
    }


    /**
     * 解析资源
     * @return $this
     */
    public function assets()
    {
        $path = str_replace("doc/assets", "", $this->request->pathinfo());
        $ext = $this->request->ext();
        if($ext)
        {
            $type= "text/html";
            $content = file_get_contents($this->assets_path.$path);
            if(array_key_exists($ext, $this->mimeType))
            {
                $type = $this->mimeType[$ext];
            }
            return response($content, 200, ['Content-Length' => strlen($content)])->contentType($type);
        }
    }

    /**
     * 文档首页
     * @return mixed
     */
    public function index()
    {
        return $this->show('index');
    }

    /**
     * 设置目录树及图标
     * @param $actions
     * @return mixed
     */
    protected function setIcon($actions, $num = 1)
    {
        foreach ($actions as $key=>$moudel){
            if(isset($moudel['actions'])){
                $actions[$key]['iconClose'] = $this->root."/doc/assets/js/zTree_v3/img/zt-folder.png";
                $actions[$key]['iconOpen'] = $this->root."/doc/assets/js/zTree_v3/img/zt-folder-o.png";
                $actions[$key]['open'] = true;
                $actions[$key]['isParent'] = true;
                $actions[$key]['actions'] = $this->setIcon($moudel['actions'], $num = 1);
            }else{
                $actions[$key]['icon'] = $this->root."/doc/assets/js/zTree_v3/img/zt-file.png";
                $actions[$key]['isParent'] = false;
                $actions[$key]['isText'] = true;
            }
        }
        return $actions;
    }

    /**
     * 接口列表
     * @return \think\Response
     */
    public function getList()
    {
        $list = $this->doc->getList();
        $list = $this->setIcon($list);
        return response(['firstId'=>'', 'list'=>$list], 200, [], 'json');
    }

    /**
     * 接口详情
     * @param string $name
     * @return mixed
     */
    public function getInfo($name = "")
    {
        list($class, $action) = explode("::", $name);
        $action_doc = $this->doc->getInfo($class, $action);
        if($action_doc)
        {
            $return = $this->doc->formatReturn($action_doc);
            $action_doc['header'] = isset($action_doc['header']) ? array_merge($this->doc->__get('public_header'), $action_doc['header']) : [];
            $action_doc['param'] = isset($action_doc['param']) ? array_merge($this->doc->__get('public_param'), $action_doc['param']) : [];
            return $this->show('info', ['doc'=>$action_doc, 'return'=>$return]);
        }
    }


    /**
     * 接口访问测试
     * @return \think\Response
     */
    public function debug()
    {
        $data = $this->request->param();
        $api_url = $this->request->param('url');
        $res['status'] = '404';
        $res['meaasge'] = '接口地址无法访问！';
        $res['result'] = '';
        $method =  $this->request->param('method_type', 'GET');
        $cookie = $this->request->param('cookie');
        $headers = $this->request->param('header/a', array());
        unset($data['method_type']);
        unset($data['url']);
        unset($data['cookie']);
        unset($data['header']);
        $res['result'] = http_request($api_url, $cookie, $data, $method, $headers);
        if($res['result']){
            $res['status'] = '200';
            $res['meaasge'] = 'success';
        }
        return response($res, 200, [], 'json');
    }
}