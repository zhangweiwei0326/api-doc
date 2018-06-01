<?php
namespace Api\Doc;

class Doc
{
    protected  $config = [
        'title'=>'APi接口文档',
        'version'=>'1.0.0',
        'copyright'=>'Powered By Zhangweiwei',
        'password' => '',
        'static_path' => '',
        'controller' => [],
        'filter_method'=>['_empty'],
        'return_format' => [
            'status' => "200/300/301/302",
            'message' => "提示信息",
        ]
    ];

    /**
     * 架构方法 设置参数
     * @access public
     * @param  array $config 配置参数
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 使用 $this->name 获取配置
     * @access public
     * @param  string $name 配置名称
     * @return mixed    配置值
     */
    public function __get($name)
    {
        return $this->config[$name];
    }

    /**
     * 设置验证码配置
     * @access public
     * @param  string $name  配置名称
     * @param  string $value 配置值
     * @return void
     */
    public function __set($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * 检查配置
     * @access public
     * @param  string $name 配置名称
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->config[$name]);
    }

    /**
     * 获取接口列表
     * @return array
     */
    public function getList()
    {
        $controller = $this->config['controller'];
        $list = [];
        foreach ($controller as $class)
        {
            if(class_exists($class))
            {
                $module = [];
                $reflection = new \ReflectionClass($class);
                $doc_str = $reflection->getDocComment();
                $doc = new DocParser();
                $class_doc = $doc->parse($doc_str);
                $module =  $class_doc;
                $module['class'] = $class;
                $method = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
                $filter_method = array_merge(['__construct'], $this->config['filter_method']);
                $module['actions'] = [];
                foreach ($method as $action){
                    if(!in_array($action->name, $filter_method))
                    {
                        $doc = new DocParser();
                        $doc_str = $action->getDocComment();
                        if($doc_str)
                        {
                            $action_doc = $doc->parse($doc_str);
                            $action_doc['name'] = $class."::".$action->name;
                            if(array_key_exists('title', $action_doc)){
                                if(array_key_exists('module', $action_doc)){
                                    $key = array_search($action_doc['module'], array_column($module['actions'], 'title'));
                                    if($key === false){
                                        $action = $module;
                                        $action['title'] = $action_doc['module'];
                                        $action['module'] = $action_doc['module'];
                                        $action['actions'] = [];
                                        array_push($action['actions'], $action_doc);
                                        array_push($module['actions'], $action);
                                    }else{
                                        array_push($module['actions'][$key]['actions'], $action_doc);
                                    }
                                }else{
                                    array_push($module['actions'], $action_doc);
                                }
                            }
                        }
                    }
                }
                if(array_key_exists('group', $module)){
                    $key = array_search($module['group'], array_column($list, 'title'));
                    if($key === false){ //创建分组
                        $floder = [
                            'title' => $module['group'],
                            'description' => '',
                            'package' => '',
                            'class' => '',
                            'actions' => []
                        ];
                        array_push($floder['actions'], $module);
                        array_push($list, $floder);
                    }else{
                        array_push($list[$key]['actions'], $module);
                    }
                }else{
                    array_push($list, $module);
                }
            }
        }
        return $list;
    }

    /**
     * 文档目录列表
     * @return array
     */
    public function getModuleList()
    {
        $controller = $this->config['controller'];
        $list = [];
        foreach ($controller as $class) {
            if (class_exists($class)) {
                $reflection = new \ReflectionClass($class);
                $doc_str = $reflection->getDocComment();
                $doc = new DocParser();
                $class_doc = $doc->parse($doc_str);
                if(array_key_exists('group', $class_doc)){
                    $key = array_search($class_doc['group'], array_column($list, 'title'));
                    if($key === false){ //创建分组
                        $floder = [
                            'title' => $class_doc['group'],
                            'children' => []
                        ];
                        array_push($floder['children'], $class_doc);
                        array_push($list, $floder);
                    }
                    else
                    {
                        array_push($list[$key]['children'], $class_doc);
                    }
                }else{
                    array_push($list, $class_doc);
                }
            }
        }
        return $list;
    }

    /**
     * 获取类中指导方法注释详情
     * @param $class
     * @param $action
     * @return array
     */
    public function getInfo($class, $action)
    {
        $action_doc = [];
        if($class && class_exists($class)){
            $reflection = new \ReflectionClass($class);
            $doc_str = $reflection->getDocComment();
            $doc = new DocParser();
            $class_doc = $doc->parse($doc_str);
            $class_doc['header'] = isset($class_doc['header'])? $class_doc['header'] : [];
            $class_doc['param'] = isset($class_doc['param']) ? $class_doc['param'] : [];
            if($reflection->hasMethod($action)) {
                $method = $reflection->getMethod($action);
                $doc = new DocParser();
                $action_doc = $doc->parse($method->getDocComment());
                $action_doc['name'] = $class."::".$method->name;
                $action_doc['header'] = isset($action_doc['header']) ? array_merge($class_doc['header'], $action_doc['header']) : $class_doc['header'];
                $action_doc['param'] = isset($action_doc['param']) ? array_merge($class_doc['param'], $action_doc['param']) : $class_doc['param'];
            }
        }
        return $action_doc;
    }

    /**
     * 文档列表搜素
     * @param string $keyword
     * @return array
     */
    public function searchList($keyword = "")
    {
        $controller = $this->config['controller'];
        $list = [];
        foreach ($controller as $class)
        {
            if(class_exists($class))
            {
                $reflection = new \ReflectionClass($class);
                $method = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
                $filter_method = array_merge(['__construct'], $this->config['filter_method']);
                foreach ($method as $action){
                    if(!in_array($action->name, $filter_method))
                    {
                        $doc = new DocParser();
                        $doc_str = $action->getDocComment();
                        if($doc_str)
                        {
                            $action_doc = $doc->parse($doc_str);
                            $action_doc['name'] = $class."::".$action->name;
                            if((isset($action_doc['title']) && strpos($action_doc['title'], $keyword) !== false)
                                    || (isset($action_doc['description']) && strpos($action_doc['description'], $keyword) !== false)
                                    || (isset($action_doc['author']) && strpos($action_doc['author'], $keyword) !== false)
                                    || (isset($action_doc['url'])  && strpos($action_doc['url'], $keyword) !== false))
                            {
                                array_push($list, $action_doc);
                            }
                        }
                    }
                }
            }
        }
        return $list;
    }

    /**
     * 格式化数组为json字符串-用于格式显示
     * @param array $doc
     * @return string
     */
    public function formatReturn($doc = [])
    {
        $json = '{<br>';
        $data = $this->config['return_format'];
        foreach ($data as $name=>$value) {
            $json .= '&nbsp;&nbsp;"'.$name.'":'.$value.',<br>';
        }
        $json .= '&nbsp;&nbsp;"data":{<br/>';
        $returns = isset($doc['return']) ? $doc['return'] : [];
        foreach ($returns as $val)
        {
            list($name, $value) =  explode(":", trim($val));
            if(strpos($value, '@') != false){
                $json .= $this->string2jsonArray($doc, $val, '&nbsp;&nbsp;&nbsp;&nbsp;');
            }else{
                $json .= '&nbsp;&nbsp;&nbsp;&nbsp;' . $this->string2json(trim($name), $value);
            }
        }
        $json .= '&nbsp;&nbsp;}<br/>';
        $json .= '}';
        return $json;
    }

    /**
     * 格式化json字符串-用于展示
     * @param $name
     * @param $val
     * @return string
     */
    private function string2json($name, $val){
        if(strpos($val,'#') != false){
            return '"'.$name.'": ["'.str_replace('#','',$val).'"],<br/>';
        }else {
            return '"'.$name.'":"'.$val.'",<br/>';
        }
    }

    /**
     * 递归转换数组为json字符格式-用于展示
     * @param $doc
     * @param $val
     * @param $space
     * @return string
     */
    private function string2jsonArray($doc, $val, $space){
        list($name, $value) =  explode(":", trim($val));
        $json = "";
        if(strpos($value, "@!") != false){
            $json .= $space.'"'.$name.'":{//'.str_replace('@!','',$value).'<br/>';
        }else{
            $json .= $space.'"'.$name.'":[{//'.str_replace('@','',$value).'<br/>';
        }
        $return = isset($doc[$name]) ? $doc[$name] : [];
        if(preg_match_all('/(\w+):(.*?)[\s\n]/s', $return." ", $meatchs)){
            foreach ($meatchs[0] as $key=>$v){
                if(strpos($meatchs[2][$key],'@') != false){
                    $json .= $this->string2jsonArray($doc,$v,$space.'&nbsp;&nbsp;');
                } else{
                    $json .= $space.'&nbsp;&nbsp;'. $this->string2json(trim($meatchs[1][$key]), $meatchs[2][$key]);
                }
            }
        }
        if(strpos($value, "@!") != false){
            $json .= $space."}<br/>";
        }else{
            $json .= $space."}]<br/>";
        }
        return $json;
    }
}