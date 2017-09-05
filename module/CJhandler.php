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
    *   教务系统处理模块，用于获取课表，教学评估等
    *   模块可见级别：用户级模块
    */

    class CJhandler
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

        private static $CJ_BASEURL='http://cj.shu.edu.cn';

        /**
         * 教务系统登陆
         *
         * @param String $usr 用户名
         * @return 在没有该用户记录时返回USER NG结束脚本执行
         *         否则成功：array(true,$sessionID)
         *             失败：array(false,null)
         */
        private function CJ_login($usr)
        {
            $NetHandler=new CurlNetwork(self::$CJ_BASEURL);
            $NetHandler->sessionId=$NetHandler->GetSessionID('/User/GetValidateCode?+GetTimestamp()').
                                   ';'.
                                   $NetHandler->GetSessionID('');
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
                $vali=ValicodeTool::getValicode($NetHandler,'/User/GetValidateCode?+GetTimestamp()');
                $content=$NetHandler->CurlPost('/',
                                               array('txtUserNo'=>$usr,
                                                     'txtPassword'=>$pass,
                                                     'txtValidateCode'=>$vali)
                                              );
                //echo $content.'<br>';
            }while(preg_match('/提供的验证码不正确/',$content)==1);
        
            if(preg_match('/学号或密码错误/',$content)==1)
            {
                $ret=array(false,null);
                return $ret;
            }
            else
            {
                $ret=array(true,$NetHandler->sessionId);
                return $ret;
            }

        }

        /**
         * 教务系统获取课表
         *
         * @param $data=array('usr'=>用户名,'year'=>学年,'season'=>季度)
         * @return 在没有该用户记录时返回USER NG结束脚本执行
         *         在登陆失败（密码错误）时返回USRPSW NG结束脚本执行
         *         成功：json返回课表
         */
        public function CJ_getCourseTable($data)
        {
            $username=$data['usr'];
            if(!isset($_SESSION['CJsessionID']))
            {
                $result=$this->CJ_login($username);
                if(!$result[0])
                {
                    $this->output('USRPSW NG',0);
                    return;
                }
                $_SESSION['CJsessionID']=$result[1];
            }

            $term=$data['year'];
            if($data['season']==4)
            {
                $season=5;
            }
            else
            {
                $season=$data['season'];
            }
            $term .= $season;

            $Nethandler=new CurlNetwork(self::$CJ_BASEURL);
            $Nethandler->sessionId=$_SESSION['CJsessionID'];
            $content='';
            while($content==null)
            {
                $content=$Nethandler->CurlPost('/StudentPortal/CtrlStudentSchedule',array('academicTermID'=>$term));   
            }
            $CourseListArr=CourseAnalyzer::CJ_ParseCourseList($content);
            //$CourseListArr=CourseAnalyzer::CJ_ParseCourseList(null,TRUE);
            $this->output($CourseListArr,1);
            return;
        } 

        //todo
        public function CJ_gradeTeacher($data)
        {
            $username=$data['usr'];
            if(!isset($_SESSION['CJsessionID']))
            {
                $result=$this->CJ_login($username);
                if(!$result[0])
                {
                    $this->output('USRPSW NG',0);
                    return;
                }
                $_SESSION['CJsessionID']=$result[1];
            }

            $Nethandler=new CurlNetwork(self::$CJ_BASEURL);
            $Nethandler->sessionId=$_SESSION['CJsessionID'];
            $content=$Nethandler->CurlGet('/StudentPortal/Evaluate');   
             
        }

    }

?>