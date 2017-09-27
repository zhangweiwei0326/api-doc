<?php

namespace Api\Doc;

class Doc
{
    protected $config = [
        'title' => 'APi接口文档',
        'version' => '1.0.0',
        'copyright' => 'Powered By Zhangweiwei',
        'controller' => [],
        'filter_method' => ['_empty'],
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
     * @param  string $name 配置名称
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
        foreach ($controller as $class) {
            if (class_exists($class)) {
                $moudel = [];
                $reflection = new \ReflectionClass($class);
                $doc_str = $reflection->getDocComment();
                $doc = new DocParser();
                $class_doc = $doc->parse($doc_str);
                $moudel = $class_doc;
                $moudel['class'] = $class;
                $method = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
                $filter_method = array_merge(['__construct'], $this->config['filter_method']);
                $moudel['actions'] = [];
                foreach ($method as $action) {
                    if (!in_array($action->name, $filter_method)) {
                        $doc = new DocParser();
                        $doc_str = $action->getDocComment();
                        if ($doc_str) {
                            $action_doc = $doc->parse($doc_str);
                            $action_doc['name'] = $class . "::" . $action->name;
                            array_push($moudel['actions'], $action_doc);
                        }
                    }
                }
                array_push($list, $moudel);
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
        if ($class && class_exists($class)) {
            $reflection = new \ReflectionClass($class);
            $doc_str = $reflection->getDocComment();
            $doc = new DocParser();
            $class_doc = $doc->parse($doc_str);
            $class_doc['header'] = isset($class_doc['header']) ? $class_doc['header'] : [];
            $class_doc['param'] = isset($class_doc['param']) ? $class_doc['param'] : [];
            if ($reflection->hasMethod($action)) {
                $method = $reflection->getMethod($action);
                $doc = new DocParser();
                $action_doc = $doc->parse($method->getDocComment());
                $action_doc['header'] = isset($action_doc['header']) ? array_merge($class_doc['header'], $action_doc['header']) : [];
                $action_doc['param'] = isset($action_doc['param']) ? array_merge($class_doc['param'], $action_doc['param']) : [];
            }
        }
        return $action_doc;
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
        foreach ($data as $name => $value) {
            $json .= '&nbsp;&nbsp;"' . $name . '":' . $value . ',<br>';
        }

        $returns = isset($doc['return']) ? $doc['return'] : [];
        if (count($returns) == 1 && strpos($returns[0], ':') == false) {
            $json .= '&nbsp;&nbsp;"data":' . $returns[0];
            $json .= '<br/>';
            $json .= '}';
            return $json;

        }
        $json .= '&nbsp;&nbsp;"data":{<br/>';


        foreach ($returns as $val) {
            list($name, $value) = explode(":", trim($val));
            if (strpos($value, '@') != false) {
                $json .= $this->string2jsonArray($doc, $val, '&nbsp;&nbsp;&nbsp;&nbsp;');
            } else {
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
    private function string2json($name, $val)
    {
        if (strpos($val, '#') != false) {
            return '"' . $name . '": ["' . str_replace('#', '', $val) . '"],<br/>';
        } else {
            return '"' . $name . '":"' . $val . '",<br/>';
        }
    }

    /**
     * 递归转换数组为json字符格式-用于展示
     * @param $doc
     * @param $val
     * @param $space
     * @return string
     */
    private function string2jsonArray($doc, $val, $space)
    {
        list($name, $value) = explode(":", trim($val));
        $json = "";
        if (strpos($value, "@!") != false) {
            $json .= $space . '"' . $name . '":{//' . str_replace('@!', '', $value) . '<br/>';
        } else {
            $json .= $space . '"' . $name . '":[{//' . str_replace('@', '', $value) . '<br/>';
        }
        $return = isset($doc[$name]) ? $doc[$name] : [];
        if (preg_match_all('/(\w+):(.*?)[\s\n]/s', $return . " ", $meatchs)) {
            foreach ($meatchs[0] as $key => $v) {
                if (strpos($meatchs[2][$key], '@') != false) {
                    $json .= $this->string2jsonArray($doc, $v, $space . '&nbsp;&nbsp;');
                } else {
                    $json .= $space . '&nbsp;&nbsp;' . $this->string2json(trim($meatchs[1][$key]), $meatchs[2][$key]);
                }
            }
        }
        if (strpos($value, "@!") != false) {
            $json .= $space . "}<br/>";
        } else {
            $json .= $space . "}]<br/>";
        }
        return $json;
    }
}