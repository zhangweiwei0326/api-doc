<?php
namespace Weiwei\ApiDoc;

use think\Service as BaseService;

class Service extends BaseService
{
    public function register()
    {
        //注册路由
        $this->app->route->group(function() {
            require __DIR__.'/config/route.php';
        });
        //发布静态资源
        $targetDir = $this->app->getRootPath() . 'public/apidoc' .DIRECTORY_SEPARATOR;
        if (is_dir($targetDir) == false){
            $sourceDir = __DIR__.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR;
            $this->copy_dir($sourceDir, $targetDir);
        }
    }

    public function copy_dir($src, $des)
    {
        $dir = opendir($src);
        @mkdir($des);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->copy_dir($src . '/' . $file, $des . '/' . $file);
                } else {
                    copy($src . '/' . $file, $des . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}
