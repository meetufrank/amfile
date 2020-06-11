<?php
namespace app\generalichina\controller;

use think\Config;
use think\Controller;
use think\Response;



class Index extends Controller
{
    /**
     * 网站标题
     *
     * @var unknown
     */
    public function index() {
        $this->redirect('https://ts.advance-medical.com.cn/');
        die;
    }
}
 