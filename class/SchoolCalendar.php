<?php

    include_once('class/CurlNetwork.php');
    include_once('class/lib/simple_html_dom.php');    

    class SchoolCalendar {

        public static function getCurrentTermInfomation()
        {
            $NetHandler=new CurlNetwork('http://www.shu.edu.cn');
            $content=$NetHandler->CurlGet('/bzap.jsp?urltype=tree.TreeTempUrl&wbtreeid=1233');
            preg_match("/var(\s)*allTeachWeek(\s)*=/",$content,$matchStart);
            $start=stripos($content,$matchStart[0]);
            $end=stripos($content,";",$start);
            $term_detail=json_decode(trim(substr($content,$start+strlen($matchStart[0]),$end-$start-strlen($matchStart[0]))));
            //print_r($term_detail);
            
            $pattern="/(\d{4})-\d{4}学年(.*?)季学期/";
            preg_match($pattern,$content,$matches);
            $year=$matches[1];
            $season=$matches[2];
            //print_r($matches);

            //解析校历
            $raw_calendar=[];
            foreach($term_detail as $term_week)
            {
                $raw_calendar_line=[];
                $list=$term_week->list;
                foreach($list as $day)
                {
                    if($day->weekname=="星期六" || $day->weekname=="星期日")continue;
                    $date_raw=$day->date;
                    $date=self::getFullDate($date_raw,$year);
                    $raw_calendar_line []= $date;
                }
                $raw_calendar[]=$raw_calendar_line;
            }
            $calendar='';
            foreach($raw_calendar as $line)
            {
                foreach($line as $day)
                {
                    $calendar.=$day.',';
                }
                $calendar=substr($calendar,0,strlen($calendar)-1);
                $calendar.='|';
            }
            $calendar=substr($calendar,0,strlen($calendar)-1);
            //echo $calendar;
            return array('year'=>$year,'season'=>$season,'calendar'=>$calendar);
        }

        private static function getFullDate($date,$year)
        {
            preg_match('/(\d+)-(\d+)/',$date,$tmp);
            $mon=intval($tmp[1]);
            $day=intval($tmp[2]);
            $time_small=mktime(12,0,0,$mon,$day,$year);
            $time_big=mktime(12,0,0,$mon,$day,$year+1);
            $small_interval=abs(time()-$time_small);
            $big_interval=abs(time()-$time_big);
            if($small_interval<$big_interval)return $year.'/'.$mon.'/'.$day;
            else return ($year+1).'/'.$mon.'/'.$day;
        }

    }
?>