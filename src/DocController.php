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
    }

    /**
     * 显示模板
     * @param $name
     * @return mixed
     */
    protected function show($name, $vars = [], $replace = [], $config = [])
    {
        $re = [
            "__ASSETS__" => "/doc/assets"
        ];
        $replace = array_merge($replace, $re);
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
     * 接口列表
     * @return \think\Response
     */
    public function getList()
    {
        $list = $this->doc->getList();
        foreach ($list as $key=>$moudel){
            $list[$key]['iconClose'] = "/doc/assets/js/zTree_v3/img/zt-folder.png";
            $list[$key]['iconOpen'] = "/doc/assets/js/zTree_v3/img/zt-folder-o.png";
            $list[$key]['open'] = true;
            $list[$key]['isParent'] = true;
            foreach ($moudel['actions'] as $k=>$v) {
                $moudel['actions'][$k]['icon'] = "/doc/assets/js/zTree_v3/img/zt-file.png";
                $moudel['actions'][$k]['isParent'] = false;
                $moudel['actions'][$k]['isText'] = true;
            }
            $list[$key]['actions'] = $moudel['actions'];
        }
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
        $type =  $this->request->param('method_type','get');
        $cookie = $this->request->param('cookie','GET');
        unset($data['method_type']);
        unset($data['url']);
        unset($data['cookie']);
        if($type == 'get'){
            $data = array_filter($data);
            $api_url .= "?".http_build_query($data);
            //还原数组格式
            $api_url = str_replace(array('%5B0%5D'), array('[]'), $api_url);
            $res['result'] = http_request($api_url, $cookie);
        }else{
            $res['result'] = http_request($api_url, $cookie, $data);
        }
        if($res['result']){
            $res['status'] = '200';
            $res['meaasge'] = 'success';
        }
        url();
        return response($res, 200, [], 'json');
    }
}