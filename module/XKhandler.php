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
    *   选课系统处理模块，用于查询课程，选课等
    *   模块可见级别：用户级模块
    */

    class XKhandler
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

        //private static $XK_BASEURL='http://xk.autoisp.shu.edu.cn:8080';
        //for test
        private static $XK_BASEURL='http://xk.autoisp.shu.edu.cn';

        /**
         * 选课系统登陆
         *
         * @param String $usr 用户名
         * @return 在没有该用户记录时返回USER NG结束脚本执行
         *         否则成功：array(true,$sessionID)
         *             失败：array(false,null)
         */
        private function XK_login($usr)
        {
            $NetHandler=new CurlNetwork(self::$XK_BASEURL);
            $NetHandler->sessionId=$NetHandler->GetSessionID('/');
        
            $userinfo=UserinfoManager::getInstance();
            $result=$userinfo->queryUser($usr);
            if($result==null)
            {
                $this->output('USER NG',0);
                exit();
            }
            $pass=$result['psw'];
            $result=$userinfo->queryUserPrivKey($usr);
            $privKey=$result['key'];
            $pass=DataEncrypt::decryptDataUsePrivKey($privKey,$pass);

            do{
                $vali=ValicodeTool::getValicode($NetHandler,'/Login/GetValidateCode?GetTimestamp()');
                $content=$NetHandler->CurlPost('/',
                                               array('txtUserName'=>$usr,
                                                     'txtPassword'=>$pass,
                                                     'txtValiCode'=>$vali)
                                              );
                //echo $content;
                //echo $vali.'<br>';
            }while(preg_match('/验证码错误！/',$content)==1);
        
            if(preg_match('/帐号或密码错误！/',$content)==1)
            {
                $ret=array(false,0);
                return $ret;
            }else if(preg_match('/尚未完成教学评估！/',$content)==1)
            {
                $ret=array(false,1);
                return $ret;
            }
            else
            {
                $ret=array(true,$NetHandler->sessionId);
                return $ret;
            }
        }

        /**
         * 选课系统查询课程
         *
         * @param $data=array(usr=>用户名,course_num=>课程号,course_name=>课程名,
         * teacher_num=>教师号,teacher_name=>教师名,course_time=>上课时间,
         * not_full=>人数未满,credit=>学分数,campus=>校区,enroll=>已选人数,
         * min_capacity=>最小容量空余,max_capacity=>最大容量空余,page_index=>翻页页码,
         * page_size=>每页记录数量)
         * @return 在没有该用户记录时返回USER NG结束脚本执行
         *         在登陆失败（密码错误）时返回USRPSW NG结束脚本执行
         *         成功：json返回课程列表
         */
        public function XK_QueryCourse($data)
        {
            $username=$data['usr'];
            if(!isset($_SESSION['XKsessionID']))
            {
                $result=$this->XK_login($username);
                if(!$result[0])
                {
                    if($result[1]==0)$this->output('USRPSW NG',0);
                    else $this->output('GRADE NG',0);
                    return;
                }
                $_SESSION['XKsessionID']=$result[1];
            }
            //首先检验参数是否正确
            $Nethandler=new CurlNetwork(self::$XK_BASEURL);
            $Nethandler->sessionId=$_SESSION['XKsessionID'];
            $content=$Nethandler->CurlPost('/Login/ValiWhereValue',
                array('CourseNo'=>$data['course_num'],'CourseName'=>$data['course_name'],
                'TeachNo'=>$data['teacher_num'],'TeachName'=>$data['teacher_name'],
                'CourseTime'=>$data['course_time'],'NotFull'=>$data['not_full'],
                'Credit'=>$data['credit'],'Campus'=>$data['campus'],'Enrolls'=>$data['enroll'],
                'MinCapacity'=>$data['min_capacity'],'MaxCapacity'=>$data['max_capacity']));
               
            if(!preg_match("/成功/",$content))$this->ouput("DATA NG",0);//数据格式不正确

            $content=$Nethandler->CurlPost('/StudentQuery/CtrlViewQueryCourse',
                array('CourseNo'=>$data['course_num'],'CourseName'=>$data['course_name'],
                'TeachNo'=>$data['teacher_num'],'TeachName'=>$data['teacher_name'],
                'CourseTime'=>$data['course_time'],'NotFull'=>$data['not_full'],
                'Credit'=>$data['credit'],'Campus'=>$data['campus'],'Enrolls'=>$data['enroll'],
                'MinCapacity'=>$data['min_capacity'],'MaxCapacity'=>$data['max_capacity'],
                'PageIndex'=>$data['page_index'],'PageSize'=>$data['page_size'],'FunctionString'=>'InitPage'));

            $res=CourseAnalyzer::XK_ParseQueryCourseList($content);
            $this->output($res,1);
            return;
        }
    }
?>