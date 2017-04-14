<?php
namespace Api\Doc;

class Doc
{
    protected  $config = [
        'controller' => [],
    ];

    /**
     * 架构方法 设置参数
     * @access public
     * @param  array $config 配置参数
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
        //获取模块列表
        $this->get_list();
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

    public function get_list()
    {
        //app\\api\\controller\\Upload
        $controller = $this->config['controller'];
        foreach ($controller as $class)
        {
            $reflection = new \ReflectionClass($controller);
            $doc_str = $reflection->getDocComment();
            $doc = new DocParser();
            $doc = $doc->parse($doc_str);
        }
    }
    
    /**
     * 
     * @return \think\Response
     */
    public function show()
    {
        $content = "123";
        return response($content, 200);
    }
}