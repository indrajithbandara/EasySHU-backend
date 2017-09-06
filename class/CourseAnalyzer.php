<?php

    //////////////// 自动加载库 ///////////////
    spl_autoload_register(function($class){
        include $class.".php";
        //echo "include $class.php!"."<br>";
    });
    include_once('lib/simple_html_dom.php');
    //////////////////////////////////////////

    class CourseAnalyzer
    {

        /*
        public static function output($data,$status=0)
            {
                $arr=array('status'=>$status,'data'=>$data);
                echo json_encode($arr,JSON_UNESCAPED_UNICODE);
                //exit();
            }
        */

        /**
         * CJ_ParseCourseList
         * 公有函数，解析并格式化课表
         * @param String $htmObj 待解析文档
         * @param boolean $TestInput 是否为测试
         * @return void
         */
        public static function CJ_ParseCourseList($htmObj,$TestInput=false)
        {
            //create html_dom object
            $html=new simple_html_dom();
            if($TestInput)
            {
                $html->load_file("res/test_course_list.html");
            }
            else
            {
                $html->load($htmObj);
            }
            
            $course_list=[];

            //find course schedule elements
            foreach($html->find('tr') as $tr)
            {
                $arr=[];
                //only those cells with rowspan=1 are what we want
                foreach($tr->find('td[rowspan=1]') as $td) 
                {
                    $arr []= str_replace(' ','',$td->plaintext);
                }
                $course_list []= $arr;
            }

            //remove null course
            $cnt=0;
            $pos=[];
            foreach ($course_list as $course) {
                if($course==null)
                {
                    $pos []= $cnt;
                }
                $cnt++;
            }

            foreach ($pos as $p) {
                unset($course_list[$p]);
            }

            $ret=[];
            
            foreach ($course_list as $course) {
                /*返回原始数据，以备他用*/
                $course_processed["course_num"]=$course[0];
                $course_processed["course_name"]=$course[1];
                $course_processed["course_teacher_num"]=$course[2];
                $course_processed["course_teacher_name"]=$course[3];
                $course_processed["course_time"]=$course[4];
                $course_processed["course_room"]=$course[5];
                $course_processed["course_ques_time"]=$course[6];
                $course_processed["course_ques_place"]=$course[7];
                /*处理并返回课程开始结束时间，上课的周数，备注等信息*/
                $course_processed["course_time_detail"]=self::GenerateCourseTimeDetail($course[4]);
                $ret []= $course_processed;
            }        

            //echo self::GenerateCourseTimeDetail('一9-10 三1-2 三6-9(1-6周) 五6-9单');

            return $ret;
                        
        }

        /**
         * XK_ParseCourseList
         * 公有函数，解析并格式化课程列表
         * @param String $htmObj 待解析文档
         * @param boolean $TestInput 是否为测试
         * @return 格式化课程列表
         */
        public static function XK_ParseQueryCourseList($htmObj,$TestInput=false)
        {
            //create html_dom object
            $html=new simple_html_dom();
            if($TestInput)
            {
                $html->load_file("res/test_query_course_list.html");
            }
            else
            {
                $html->load($htmObj);
            }
            
            $course_list=[];

            //find course schedule elements
            foreach($html->find('tr') as $tr)
            {
                $arr=[];
                //与课程表不同，td里的都是我们需要的
                foreach($tr->find('td') as $td) 
                {
                    $arr []= str_replace(' ','',$td->plaintext);
                }
                $course_list []= $arr;
            }

            //remove null course
            $cnt=0;
            $pos=[];
            foreach ($course_list as $course) {
                if($course==null)
                {
                    $pos []= $cnt;
                }
                $cnt++;
            }

            foreach ($pos as $p) {
                unset($course_list[$p]);
            }

            $course_list=array_values($course_list);

            //mend the broken course
            $course_tmp=$course_list[0];
            foreach ($course_list as &$course_modify) {
                //栏目数量少于正常数量，说明课程名和课程号与前一门课程相同
                if(count($course_modify)==10){
                    array_unshift($course_modify,$course_tmp[0],$course_tmp[1],$course_tmp[2]);
                }else{
                    $course_tmp=$course_modify; 
                }
               
            }

            $ret=[];
        
            $cnt=0;

            for($i=0;$i<count($course_list);$i++) { 
                $course=$course_list[$i];            
                /*处理原始数据，将数据转换为对应的值*/
                $course[2]=intval($course[2]);
                $course[7]=intval($course[7]);
                $course[8]=intval($course[8]);                
                $course[9]=array_search($course[9],consts::$campus);
                $tmp=0;
                if(preg_match('/限制人数/',$course[10]))$tmp+=1;
                if(preg_match('/禁止选课/',$course[10]))$tmp+=2;
                if(preg_match('/禁止退课/',$course[10]))$tmp+=4;
                $course[10]=$tmp;

                /*返回原始数据*/
                $course_processed["course_num"]=$course[0];
                $course_processed["course_name"]=$course[1];
                $course_processed["course_teacher_num"]=$course[3];
                $course_processed["course_teacher_name"]=$course[4];
                $course_processed["course_credit"]=$course[2];
                $course_processed["course_time"]=$course[5];
                $course_processed["course_campus"]=$course[9];
                $course_processed["course_room"]=$course[6];
                $course_processed["course_chosen"]=$course[8];
                $course_processed["course_capacity"]=$course[7];
                $course_processed["course_restrict"]=$course[10];
                $course_processed["course_ques_time"]=$course[11];
                $course_processed["course_ques_place"]=$course[12];
                /*处理并返回课程开始结束时间，上课的周数，备注等信息*/
                $res=self::GenerateCourseTimeDetailWithComment($course[5]);
                $course_processed["course_time_detail"]=$res[0];
                $course_processed["course_comment"]=$res[1];
                $ret []= $course_processed;
                $course_processed=[];
            }        
            return $ret;
        }

        /**
         * GenerateCourseTimeDetail
         * 私有函数，生成时间详细信息
         */
        public static function GenerateCourseTimeDetail($course_time,$outputRemain=false)
        {
            if($course_time==null)return;
            $week_day=array("一","二","三","四","五");
            
            $processed_time="";
            $course_time_remain=$course_time;

            preg_match("/((?:一\d+-\d+)*)((?:二\d+-\d+)*)((?:三\d+-\d+)*)((?:四\d+-\d+)*)((?:五\d+-\d+)*)\s*(?:\(|\（)第(\d+)周(?:\(|\）)/",$course_time,$matches);
            if($matches!=null)
            {
                if($outputRemain)$course_time_remain=str_replace($matches[0],"",$course_time_remain);
                for($i=1;$i<6;$i++)
                {
                    preg_match_all("/".$week_day[$i-1]."(\d+)-(\d+)/",$matches[$i],$Innermatches,PREG_SET_ORDER);
                    foreach($Innermatches as $tmp)
                    {
                        for($j=$tmp[1];$j<=$tmp[2];$j++)
                            $processed_time.=$j.',';
                        $processed_time=substr($processed_time,0,strlen($processed_time)-1);
                        $processed_time.='@'.$matches[6];
                        $processed_time.='&';
                    }
                    $processed_time=substr($processed_time,0,strlen($processed_time)-1);
                    $processed_time.='|';
                }
            }
            else
            {
                for($i=0;$i<5;$i++)
                {    
                    $pattern='/'.$week_day[$i].'(\d+)-(\d+)\s*(单|双|(?:\(|\（)第(\d+)周(?:\)|\）)|(?:\(|\（)(\d+)(-|,)(\d+)周(?:\)|\）))*(?: )*(男)*(女)*/';
                    preg_match_all($pattern,$course_time,$matches,PREG_SET_ORDER);

                    foreach($matches as $time)
                    {
                        if($time!=null){
                            for($j=intval($time[1]);$j<=intval($time[2]);$j++)
                            {
                                $processed_time.=$j.',';
                            }
                            $processed_time=substr($processed_time,0,strlen($processed_time)-1);
                            if(array_key_exists(3,$time))
                            {
                                $week_raw=$time[3];
                                if($week_raw=='单')
                                {
                                    $processed_time.='@1,3,5,7,9';
                                }
                                else if($week_raw=='双')
                                {
                                    $processed_time.='@2,4,6,8,10';
                                }
                                else if(preg_match("/(\d+)(-|,)(\d+)周/",$week_raw)==1)
                                {
                                    $processed_time.='@';
                                    if($time[6]=='-')
                                    {
                                        for($j=intval($time[5]);$j<=intval($time[7]);$j++)
                                        {
                                            $processed_time.=$j.',';
                                        }
                                        $processed_time=substr($processed_time,0,strlen($processed_time)-1);
                                    }
                                    else if($time[6]==',')
                                    {
                                        $processed_time.=intval($time[5]).','.intval($time[7]);
                                    }
                                }
                                else if(preg_match("/第\d+周/",$week_raw)==1)
                                {
                                    $processed_time.='@';
                                    $processed_time.=$time[4];
                                }
                            }
                            if($outputRemain)$course_time_remain=str_replace($time[0],"",$course_time_remain);
                        }
                        $processed_time.='&';
                    }
                    if(substr($processed_time,strlen($processed_time)-1,1)=="&")
                    {
                    $processed_time=substr($processed_time,0,strlen($processed_time)-1);  
                    }
                    /*如果最后有多余的","则删除*/
                    if(substr($processed_time,strlen($processed_time)-1,1)==",")
                    {
                    $processed_time=substr($processed_time,0,strlen($processed_time)-1);  
                    }
                    $processed_time.='|';
                    //////////////////////////

                }
            }
            $processed_time=substr($processed_time,0,strlen($processed_time)-1);  
            
            if($outputRemain)return array($processed_time,$course_time_remain);
            else return $processed_time;

        }

        /**
         * GenerateCourseTimeDetailWithComment
         * 私有函数，生成时间详细信息和课程标签
         */
        public static function GenerateCourseTimeDetailWithComment($course_time)
        {
            //生成课程时间信息
            $res=self::GenerateCourseTimeDetail($course_time,true);
            if($res==null)return;
            $time=$res[0];
            $remain=$res[1];
            //剩下的都是课程标签,用逗号或者空格分割,将他们都替换为空格，分割为数组
            $remain=str_replace("，"," ",$remain);
            $remain=str_replace(","," ",$remain);
            return array($time,$remain);
        }
    }

?>