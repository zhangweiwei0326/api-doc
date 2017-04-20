#api-doc

### 使用方法
####1、安装扩展
```
composer require weiwei/api-doc dev-master
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
####3、在相关接口类中增加注释参数
方法如下：返回参数支持数组及多维数组
```
<?php
/**
 * @title 测试demo
 * @description 接口说明
 */
class demo
{
    /**
     * @title 测试demo接口
     * @description 接口说明
     * @author 开发者
     * @url /api/demo
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
    }

}
```
####4、在浏览器访问http://你的域名/doc 查看接口文档

####5、预览
![](https://static.oschina.net/uploads/img/201704/17101409_tAgD.png)
![](https://static.oschina.net/uploads/img/201704/17101348_XuUz.png)
![](https://static.oschina.net/uploads/img/201704/17101306_KePe.png)

###更多支持
- QQ1763692101

###问题
不少小伙伴反应，没有正常安装doc.php 配置文件，原因是你改过应用目录官方默认是application
如果没有生成doc.php 配置文件 你可以手动安装，直接在application（你修改的目录）里面创建extra文件夹，然后把扩展包中的vendor\weiwei\api-doc\src\config.php文件复制进去，并重命名为doc.php
