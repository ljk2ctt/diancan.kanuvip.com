<?php
namespace Home\Controller;
class IndexController extends CommonController {
    public function index(){
        redirect(U('Manage/table'));
//        $this->display();        
    }
}