#api-doc

### 使用方法
####1、安装扩展
```
composer require weiwei/api-doc
```
>由于我没发布版本，所有暂时需带dev-master安装

####2、配置参数
安装好扩展后在 application\extra\ 文件夹下会生成 doc.php 配置文件
在controller参数中添加对应的类
```
    'controller' => [
        'app\\api\\controller\\Demo'
    ]
```
####3、在相关接口类中增加注释参数( group 参数将接口分组，可选)
方法如下：返回参数支持数组及多维数组
```
<?php
namespace app\index\controller;
use think\Controller;

/**
 * @title 测试demo
 * @description 接口说明
 * @group 接口分组
 * @header name:key require:1 default: desc:秘钥(区别设置)
 * @param name:public type:int require:1 default:1 other: desc:公共参数(区别设置)
 */
class Demo extends Controller
{
    /**
     * @title 测试demo接口
     * @description 接口说明
     * @author 开发者
     * @url /index/demo
     * @method GET
     *
     * @header name:device require:1 default: desc:设备号
     *
     * @param name:id type:int require:1 default:1 other: desc:唯一ID
     *
     * @return name:名称
     * @return mobile:手机号
     * @return list_messages:消息列表@
     * @list_messages message_id:消息ID content:消息内容
     * @return object:对象信息@!
     * @object attribute1:对象属性1 attribute2:对象属性2
     * @return array:数组值#
     * @return list_user:用户列表@
     * @list_user name:名称 mobile:手机号 list_follow:关注列表@
     * @list_follow user_id:用户id name:名称
     */
    public function index()
    {
        //接口代码
        $device = $this->request->header('device');
        echo json_encode(["code"=>200, "message"=>"success", "data"=>['device'=>$device]]);
    }

    /**
     * @title 登录接口
     * @description 接口说明
     * @author 开发者
     * @url /api/demo
     * @method GET
     * @module 用户模块

     * @param name:name type:int require:1 default:1 other: desc:用户名
     * @param name:pass type:int require:1 default:1 other: desc:密码
     *
     * @return name:名称
     * @return mobile:手机号
     *
     */
    public function login(Request $request)
    {
        //接口代码
        $device = $request->header('device');
        echo json_encode(["code"=>200, "message"=>"success", "data"=>['device'=>$device]]);
    }
}
```
####4、在浏览器访问http://你的域名/doc 查看接口文档

####5、预览
![](https://static.oschina.net/uploads/img/201704/17101409_tAgD.png)
![](https://static.oschina.net/uploads/img/201704/17101348_XuUz.png)
![](https://static.oschina.net/uploads/img/201704/17101306_KePe.png)

###更多支持
- QQ群663447446

###2017年8月16日更新
- 增加头部参数设置，根据自己需求去设置参数
- 增加全局参数设置及类参数设置，全局参数设置可以doc.php public_param、public_header配置，类局部公用参数可在class下面进行设置，详见demo.php
- 增加模拟请求方式get、post、put、delete，注释课设置method参数进行标识

###问题
不少小伙伴反应，没有正常安装doc.php 配置文件，原因是你改过应用目录官方默认是application
如果没有生成doc.php 配置文件 你可以手动安装，直接在application（你修改的目录）里面创建extra文件夹，然后把扩展包中的vendor\weiwei\api-doc\src\config.php文件复制进去，并重命名为doc.php
