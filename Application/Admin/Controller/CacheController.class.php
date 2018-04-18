<?php
namespace Admin\Controller;
use Admin\Model\AdminModel;
use Think\Controller;
class CacheController extends AdminController{
    //初始化
    protected function _initialize(){
        parent::_initialize();
    }
    //model
    private function model(){
        return new AdminModel();
    }
    //清除缓存
    public function clear_cache(){
        $dirs = array(RUNTIME_PATH);
        foreach ($dirs as $value) {$this->rmdirr($value);
        }
        @mkdir('Runtime', 0777, true);
        $Admin=$this->model();
        $Admin->write_log('清除了系统缓存');
        $this->assign('title',"清除系统缓存");
        $this->echo_page('cache_success',U('Index/index'));
    }
    private function rmdirr($dirname){
        if (!file_exists($dirname)) return false;
        if (is_file($dirname) || is_link($dirname)) return unlink($dirname);
        $dir = dir($dirname);
        if ($dir) {
            while (false !== $entry = $dir->read()) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                $this->rmdirr($dirname . DIRECTORY_SEPARATOR . $entry);
            }
        }
        $dir->close();
        return rmdir($dirname);
    }
    //end
}
