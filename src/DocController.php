<?php
namespace Api\Doc;

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
        //5.1 去除常量调整导致的问题
        if(!defined('THINK_VERSION')){
            if(!defined('DS'))  define('DS', DIRECTORY_SEPARATOR);
        }
        //有些程序配置了默认json问题
        config('default_return_type', 'html');
        if (is_null($request)) {
            $request = Request::instance();
        }
        $this->request = $request;
        $this->assets_path = __DIR__.DS.'assets'.DS;
        $this->view_path = __DIR__.DS.'view'.DS;
        if(!defined('THINK_VERSION')){
            $this->doc = new Doc((array)\think\facade\Config::pull('doc'));
        }else{
            $this->doc = new Doc((array)\think\Config::get('doc'));
        }
        $config = [
            'view_path' => $this->view_path,
            'default_filter' => '',
        ];
        $this->view =  new View($config);
        if(!$this->view->engine){
            $this->view->init($config);
        }
        $this->view->assign('title',$this->doc->__get("title"));
        $this->view->assign('version',$this->doc->__get("version"));
        $this->view->assign('copyright',$this->doc->__get("copyright"));
        $this->assets_path = $this->doc->__get("static_path");
        $this->assets_path = $this->assets_path ? $this->assets_path : '/doc/assets';
        $this->view->assign('static', $this->assets_path);
        $this->root = $this->request->root() ? $this->request->root() : $this->request->domain();
    }

    /**
     * 验证密码
     * @return bool
     */
    protected function checkLogin()
    {
        $pass = $this->doc->__get("password");
        if($pass){
            if(session('pass') === md5($pass)){
                return true;
            }else{
                return false;
            }
        }else{
            return true;
        }
    }

    /**
     * 显示模板
     * @param $name
     * @param array $vars
     * @param array $replace
     * @param array $config
     * @return string
     */
    protected function show($name, $vars = [], $config = [])
    {
        $vars = array_merge(['root'=>$this->root], $vars);
        return $this->view->fetch($name, $vars, $config);
    }


    /**
     * 解析资源
     * @return $this
     */
    public function assets()
    {
        $assets_path = __DIR__.DS.'assets'.DS;
        $path = str_replace("doc/assets", "", $this->request->pathinfo());
        $ext = $this->request->ext();
        if($ext)
        {
            $type= "text/html";
            $content = file_get_contents($assets_path.$path);
            if(array_key_exists($ext, $this->mimeType))
            {
                $type = $this->mimeType[$ext];
            }
            return response($content, 200, ['Content-Length' => strlen($content)])->contentType($type);
        }
    }

    /**
     * 输入密码
     * @return string
     */
    public function pass()
    {
        return $this->show('pass');
    }

    /**
     * 登录
     * @return string
     */
    public function login()
    {
        $pass = $this->doc->__get("password");
        if($pass && $this->request->param('pass') === $pass){
            session('pass', md5($pass));
            $data = ['status' => '200', 'message' => '登录成功'];
        }else if(!$pass){
            $data = ['status' => '200', 'message' => '登录成功'];
        }else{
            $data = ['status' => '300', 'message' => '密码错误'];
        }
        return response($data, 200, [], 'json');
    }

    /**
     * 文档首页
     * @return mixed
     */
    public function index()
    {
        if($this->checkLogin()){
            return $this->show('index', ['doc' => $this->request->param('doc')]);
        }else{
            return redirect('doc/pass');
        }
    }

    /**
     * 文档搜素
     * @return mixed|\think\Response
     */
    public function search()
    {
        if($this->request->isAjax())
        {
            $data = $this->doc->searchList($this->request->param('query'));
            return response($data, 200, [], 'json');
        }
        else
        {
            $module = $this->doc->getModuleList();
            return $this->show('search', ['module' => $module]);
        }
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
                $actions[$key]['iconClose'] = $this->assets_path."/js/zTree_v3/img/zt-folder.png";
                $actions[$key]['iconOpen'] = $this->assets_path."/js/zTree_v3/img/zt-folder-o.png";
                $actions[$key]['open'] = true;
                $actions[$key]['isParent'] = true;
                $actions[$key]['actions'] = $this->setIcon($moudel['actions'], $num = 1);
            }else{
                $actions[$key]['icon'] = $this->assets_path."/js/zTree_v3/img/zt-file.png";
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