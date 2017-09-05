<?php   

    /*************** 路由检测 ****************/
    defined('AUTHOR') or die('非法访问');
    //////////////////////////////////////////

    //////////////// 自动加载库 ///////////////
    spl_autoload_register(function($class){
        include 'class/'.$class.".php";
    });
    //////////////////////////////////////////

    /**
    *   时间信息服务模块，用于获取当前学期周数等
    *   模块可见级别：用户级模块
    */

    class TimeService
    {

        /***********单例模式*************/

            private static $_instance;

            private function __construct()
            {
            }

            public static function getInstance()
            {
                if(!isset(self::$_instance))
                {
                    self::$_instance=new self();
                }
                return self::$_instance;
            }

        /////////////////////////////////

        /*********** 返回值 *************/
            public function output($data,$status=0)
            {
                $arr=array('status'=>$status,'data'=>$data);
                echo json_encode($arr,JSON_UNESCAPED_UNICODE);
                //exit();
            }
        /////////////////////////////////

        /**
         * Time_getTermTime
         * 获取格式化的校历数据
         * @param null $data 无数据
         * @return 输出：学年，学期，第一周日期，第二周...第十二周日期（日期中用‘|’分隔）;
         */
        public function Time_getTermTime($data)
        {
            $ret=SchoolCalendar::getCurrentTermInfomation();
            $this->output(
                $ret,
                1);
            return;
        }

    }
?>