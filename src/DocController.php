<?php
namespace Weiwei\ApiDoc;

use think\facade\Config;
use think\Request;
use app\BaseController as Controller;
use think\facade\View;

class DocController extends Controller
{
    /**
     * @var \think\Request Request实例
     */
    protected $request;

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

    public $static_path = '/apidoc/';

    public function __construct(Request $request){

        $this->doc = new Doc((array)Config::get('doc'));
        View::config(['view_path' => __DIR__.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR]);
        View::assign('title', Config::get("doc.title"));
        View::assign('version', Config::get("doc.version"));
        View::assign('copyright', Config::get("doc.copyright"));
        if(Config::get("doc.static_path", '')){
            $this->static_path = Config::get("doc.static_path");
        }
        View::assign('static', $this->static_path);
        $this->request = $request;
    }

    /**
     * 文档首页
     * @return Response
     */
    public function index()
    {
        View::assign('root', $this->request->root());
        if($this->checkLogin() == false){
            return redirect('pass');
        }
        return view('index', ['doc' => $this->request->get('name')]);
    }

    /**
     * 文档搜素
     * @return \think\Response|\think\response\View
     */
    public function search()
    {
        if($this->request->isAjax())
        {
            $data = $this->doc->searchList($this->request->get('query'));
            return response($data, 200);
        }
        else
        {
            if($this->checkLogin() == false){
                return redirect('pass');
            }
            $module = $this->doc->getModuleList();
            View::assign('root', $this->request->root());
            return view('search', ['module' => $module]);
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
                $actions[$key]['iconClose'] = $this->static_path."/js/zTree_v3/img/zt-folder.png";
                $actions[$key]['iconOpen'] = $this->static_path."/js/zTree_v3/img/zt-folder-o.png";
                $actions[$key]['open'] = true;
                $actions[$key]['isParent'] = true;
                $actions[$key]['actions'] = $this->setIcon($moudel['actions'], $num = 1);
            }else{
                $actions[$key]['icon'] = $this->static_path."/js/zTree_v3/img/zt-file.png";
                $actions[$key]['isParent'] = false;
                $actions[$key]['isText'] = true;
            }
        }
        return $actions;
    }

    /**
     * 接口列表
     */
    public function getList()
    {
        $list = $this->doc->getList();
        $list = $this->setIcon($list);
        return response(['firstId'=>'', 'list'=>$list], 200, [], 'json');
    }

    /**
     * 接口详情
     * @return mixed
     */
    public function getInfo()
    {
        if($this->checkLogin() == false){
            return redirect('pass');
        }
        list($class, $action) = explode("::", $this->request->get('name'));
        $action_doc = $this->doc->getInfo($class, $action);
        if($action_doc)
        {
            $return = $this->doc->formatReturn($action_doc);
            $action_doc['header'] = isset($action_doc['header']) ? array_merge($this->doc->__get('public_header'), $action_doc['header']) : [];
            $action_doc['param'] = isset($action_doc['param']) ? array_merge($this->doc->__get('public_param'), $action_doc['param']) : [];
            //curl code
            $curl_code = 'curl --location --request '.($action_doc['method'] ?? 'GET');
            $params = [];
            foreach ($action_doc['param'] as $param){
                $params[$param['name']] = $param['default'] ?? '';
            }
            $curl_code .= ' \''.$this->request->root().($action_doc["url"] ?? '').(count($params) > 0 ? '?'.http_build_query($params) : '').'\' ';
            foreach ($action_doc['header'] as $header){
                $curl_code .= '--header \''.$header['name'].':\'';
            }
            View::assign('root', $this->request->root());
            return view('info', ['doc'=>$action_doc, 'return'=>$return, 'curl_code' => $curl_code]);
        }
    }

    /**
     * 验证密码
     * @return bool
     */
    protected function checkLogin()
    {
        $pass = $this->doc->__get("password");
        if($pass){
            if(cache('apidoc-pass') === md5($pass)){
                return true;
            }else{
                return false;
            }
        }else{
            return true;
        }
    }

    /**
     * 输入密码
     * @return string
     */
    public function pass()
    {
        View::assign('root', $this->request->root());
        return view('pass');
    }

    /**
     * 登录
     * @return string
     */
    public function login()
    {
        $pass = $this->doc->__get("password");
        if($pass && $this->request->param('pass') === $pass){
            cache('apidoc-pass', md5($pass));
            $data = ['status' => '200', 'message' => '登录成功'];
        }else if(!$pass){
            $data = ['status' => '200', 'message' => '登录成功'];
        }else{
            $data = ['status' => '300', 'message' => '密码错误'];
        }
        return response($data, 200, [], 'json');
    }

    /**
     * 接口访问测试
     * @return \think\Response
     */
    public function debug()
    {
        $data = $this->request->all();
        $api_url = $this->request->input('url');
        $res['status'] = '404';
        $res['meaasge'] = '接口地址无法访问！';
        $res['result'] = '';
        $method =  $this->request->input('method_type', 'GET');
        $cookie = $this->request->input('cookie');
        $headers = $this->request->input('header', array());
        unset($data['method_type']);
        unset($data['url']);
        unset($data['cookie']);
        unset($data['header']);
        $res['result'] = $this->http_request($api_url, $cookie, $data, $method, $headers);
        if($res['result']){
            $res['status'] = '200';
            $res['meaasge'] = 'success';
        }
        return response($res, 200, [], 'json');
    }

    /**
     * curl模拟请求方法
     * @param $url
     * @param $cookie
     * @param array $data
     * @param $method
     * @param array $headers
     * @return mixed
     */
    private function http_request($url, $cookie, $data = array(), $method = array(), $headers = array()){
        $curl = curl_init();
        if(count($data) && $method == "GET"){
            $data = array_filter($data);
            $url .= "?".http_build_query($data);
            $url = str_replace(array('%5B0%5D'), array('[]'), $url);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (count($headers)){
            $head = array();
            foreach ($headers as $name=>$value){
                $head[] = $name.":".$value;
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, $head);
        }
        $method = strtoupper($method);
        switch($method) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        if (!empty($cookie)){
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

}