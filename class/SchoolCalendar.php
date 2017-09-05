<?php

    include_once('class/CurlNetwork.php');
    include_once('class/lib/simple_html_dom.php');

    class SchoolCalendar {

        /**
        * SchoolCalendar_getCurrentTermWeek获取当前的学周
        *
        * @return array('year'=>学年,'season'=>学期,'week'=>周数)
        */
        public static function getCurrentTermInfomation()
        {
            //切换至校历模式
            $content = self::doPostBack('HRCMS:ctr13929:Calendar:Linkbutton2',null);  
            //echo $content;  
            //首先寻找当前学年和学期
            $pattern="/(\d{4})-\d{4}学年(.*)季学期/";
            preg_match($pattern,$content,$matches);
            //print_r($matches);
            $year=$matches[1];
            $season=$matches[2];
            //取出课表部分内容（防止HTML解析器占用资源过大，进行简化）
            $start=stripos($content,"table width=250");
            $end=stripos($content,"</table>",$start);
            $term_detail=substr($content,$start-1,$end-$start+9);

            //删除课表样式
            /*$term_detail=preg_replace("/(style='(.)*?')|(cellSpacing='(.)*?')|(cellPadding='(.)*?')|(width=250)|(align='(.)*?')/","",$term_detail);*/
            /*$term_detail=preg_replace("/<a(.)*?>/","",$term_detail);*/
            //$term_detail=str_replace(" ","",$term_detail);
            //echo $term_detail;

            //HTML解析器解析
            $html=new simple_html_dom();
            $html->load($term_detail);

            $raw_calendar=[];
            foreach($html->find('tr') as $tr)
            {
                $raw_calendar_line=[];
                foreach ($tr->find('a') as $a) {
                    $tmp=$a->href;
                    $pattern="/date1=(\\d+)-(\\d+)-(\\d+)/";
                    preg_match($pattern,$tmp,$matches);
                    if(isset($matches[1]) && isset($matches[2]))
                    {
                        $raw_calendar_line []= $matches[1].'/'.$matches[2].'/'.$matches[3];    
                    }        
                }
                if($raw_calendar_line!=null)
                {
                    $raw_calendar[]=$raw_calendar_line;
                }
            }
            //print_r($raw_calendar);
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

        /**
        * doPostBack
        * 校历原始数据获取，私有函数
        */
        private static function doPostBack($target,$arg)
        {
            $NetHandler=new CurlNetwork('http://www.shu.edu.cn');
            $content=$NetHandler->CurlPost('/Default.aspx?tabid=8460',
            array(
                '__EVENTTARGET'   =>$target,
                '__EVENTARGUMENT' =>$arg,
                '__VIEWSTATE'=>'dDwtMTU1NzQzNDgyO3Q8O2w8aTw0Pjs+O2w8dDw7bDxpPDE+Oz47bDx0PDtsPGk8Mz47PjtsPHQ8O2w8aTwwPjs+O2w8dDw7bDxpPDM+O2k8ND47PjtsPHQ8O2w8aTwxPjs+O2w8dDw7bDxpPDE+O2k8Mz47aTw3Pjs+O2w8dDxwPHA8bDxWaXNpYmxlOz47bDxvPGY+Oz4+Oz47Oz47dDxwPHA8bDxWaXNpYmxlOz47bDxvPGY+Oz4+Oz47Oz47dDw7bDxpPDI+Oz47bDx0PDtsPGk8MD47PjtsPHQ8O2w8aTwwPjs+O2w8dDw7bDxpPDA+Oz47bDx0PDtsPGk8MD47aTwxPjtpPDI+O2k8Mz47PjtsPHQ8QDA8cDxwPGw8UmVwZWF0RGlyZWN0aW9uO0RhdGFLZXlzO18hSXRlbUNvdW50O1JlcGVhdENvbHVtbnM7PjtsPFN5c3RlbS5XZWIuVUkuV2ViQ29udHJvbHMuUmVwZWF0RGlyZWN0aW9uLCBTeXN0ZW0uV2ViLCBWZXJzaW9uPTEuMC41MDAwLjAsIEN1bHR1cmU9bmV1dHJhbCwgUHVibGljS2V5VG9rZW49YjAzZjVmN2YxMWQ1MGEzYTxWZXJ0aWNhbD47bDw+O2k8ND47aTwxPjs+Pjs+Ozs7Ozs7Ozs+O2w8aTwwPjtpPDE+O2k8Mj47aTwzPjs+O2w8dDw7bDxpPDA+O2k8MT47aTwyPjtpPDQ+O2k8Nj47aTw4PjtpPDEwPjtpPDEyPjtpPDE0Pjs+O2w8dDxAPFxlOz47Oz47dDxwPHA8bDxJbWFnZVVybDtOYXZpZ2F0ZVVybDtWaXNpYmxlOz47bDwvaW1hZ2VzL2VkaXQuZ2lmOy9EZWZhdWx0LmFzcHg/dGFiaWQ9ODQ2MCZjdGw9RWRpdCZtaWQ9MTM5MzMmSWQ9OTIzMzk7bzxmPjs+Pjs+Ozs+O3Q8cDxsPHNyYztWaXNpYmxlOz47bDwvRGVza3RvcE1vZHVsZXMvQXJ0L0ltYWdlcy9wb2ludC5naWY7bzxmPjs+Pjs7Pjt0PHA8cDxsPFRleHQ7VmlzaWJsZTs+O2w8XGU7bzxmPjs+Pjs+Ozs+O3Q8cDxwPGw8VGV4dDtOYXZpZ2F0ZVVybDs+O2w86Im65pyv5a2m56eR4oCc6auY5rC05bmz5aSn5a2m5YmN5rK/56CU56m25pa55ZCR6YG06YCJ4oCd5ZCv5Yqo5bel5L2c6YCaLi4uOy9EZWZhdWx0LmFzcHg/dGFiaWQ9ODQ2MCZjdGw9RGV0YWlsJm1pZD0xMzkzMyZJZD05MjMzOSZTa2luU3JjPVtHXVNraW5zL2NhbGVuZGFyL2NhbGVuZGFyOz4+O3A8bDx0aXRsZTs+O2w86Im65pyv5a2m56eR4oCc6auY5rC05bmz5aSn5a2m5YmN5rK/56CU56m25pa55ZCR6YG06YCJ4oCd5ZCv5Yqo5bel5L2c6YCa55+lOz4+Pjs7Pjt0PHA8bDxzcmM7VmlzaWJsZTs+O2w8L0Rlc2t0b3BNb2R1bGVzL0FydC9JbWFnZXMvaG90LmdpZjtvPGY+Oz4+Ozs+O3Q8cDxwPGw8VGV4dDtWaXNpYmxlOz47bDzmnKrlrqHmoLg7bzxmPjs+Pjs+Ozs+O3Q8cDxwPGw8VGV4dDs+O2w8MjAxNy0wNy0yNzs+Pjs+Ozs+O3Q8cDxwPGw8SW1hZ2VVcmw7PjtsPC9EZXNrdG9wTW9kdWxlcy9BcnQvSW1hZ2VzL3BvaW50LmdpZjs+Pjs+Ozs+Oz4+O3Q8O2w8aTwwPjtpPDE+O2k8Mj47aTw0PjtpPDY+O2k8OD47aTwxMD47aTwxMj47aTwxND47PjtsPHQ8QDxcZTs+Ozs+O3Q8cDxwPGw8SW1hZ2VVcmw7TmF2aWdhdGVVcmw7VmlzaWJsZTs+O2w8L2ltYWdlcy9lZGl0LmdpZjsvRGVmYXVsdC5hc3B4P3RhYmlkPTg0NjAmY3RsPUVkaXQmbWlkPTEzOTMzJklkPTkyMzMzO288Zj47Pj47Pjs7Pjt0PHA8bDxzcmM7VmlzaWJsZTs+O2w8L0Rlc2t0b3BNb2R1bGVzL0FydC9JbWFnZXMvcG9pbnQuZ2lmO288Zj47Pj47Oz47dDxwPHA8bDxUZXh0O1Zpc2libGU7PjtsPFxlO288Zj47Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7TmF2aWdhdGVVcmw7PjtsPOWVhuenkeKAnOmrmOawtOW5s+Wkp+WtpuWJjeayv+eglOeptuaWueWQkemBtOmAieKAneWQr+WKqOW3peS9nOmAmuefpTsvRGVmYXVsdC5hc3B4P3RhYmlkPTg0NjAmY3RsPURldGFpbCZtaWQ9MTM5MzMmSWQ9OTIzMzMmU2tpblNyYz1bR11Ta2lucy9jYWxlbmRhci9jYWxlbmRhcjs+PjtwPGw8dGl0bGU7PjtsPOWVhuenkeKAnOmrmOawtOW5s+Wkp+WtpuWJjeayv+eglOeptuaWueWQkemBtOmAieKAneWQr+WKqOW3peS9nOmAmuefpTs+Pj47Oz47dDxwPGw8c3JjO1Zpc2libGU7PjtsPC9EZXNrdG9wTW9kdWxlcy9BcnQvSW1hZ2VzL2hvdC5naWY7bzxmPjs+Pjs7Pjt0PHA8cDxsPFRleHQ7VmlzaWJsZTs+O2w85pyq5a6h5qC4O288Zj47Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPDIwMTctMDctMTk7Pj47Pjs7Pjt0PHA8cDxsPEltYWdlVXJsOz47bDwvRGVza3RvcE1vZHVsZXMvQXJ0L0ltYWdlcy9wb2ludC5naWY7Pj47Pjs7Pjs+Pjt0PDtsPGk8MD47aTwxPjtpPDI+O2k8ND47aTw2PjtpPDg+O2k8MTA+O2k8MTI+O2k8MTQ+Oz47bDx0PEA8XGU7Pjs7Pjt0PHA8cDxsPEltYWdlVXJsO05hdmlnYXRlVXJsO1Zpc2libGU7PjtsPC9pbWFnZXMvZWRpdC5naWY7L0RlZmF1bHQuYXNweD90YWJpZD04NDYwJmN0bD1FZGl0Jm1pZD0xMzkzMyZJZD05MjMzMDtvPGY+Oz4+Oz47Oz47dDxwPGw8c3JjO1Zpc2libGU7PjtsPC9EZXNrdG9wTW9kdWxlcy9BcnQvSW1hZ2VzL3BvaW50LmdpZjtvPGY+Oz4+Ozs+O3Q8cDxwPGw8VGV4dDtWaXNpYmxlOz47bDxcZTtvPGY+Oz4+Oz47Oz47dDxwPHA8bDxUZXh0O05hdmlnYXRlVXJsOz47bDzlhbPkuo5QSU3mtYHnqIvkuI7mmbrog73nrqHnkIbns7vnu5/ov4Hnp7vljYfnuqfnmoTpgJrnn6U7L0RlZmF1bHQuYXNweD90YWJpZD04NDYwJmN0bD1EZXRhaWwmbWlkPTEzOTMzJklkPTkyMzMwJlNraW5TcmM9W0ddU2tpbnMvY2FsZW5kYXIvY2FsZW5kYXI7Pj47cDxsPHRpdGxlOz47bDzlhbPkuo5QSU3mtYHnqIvkuI7mmbrog73nrqHnkIbns7vnu5/ov4Hnp7vljYfnuqfnmoTpgJrnn6U7Pj4+Ozs+O3Q8cDxsPHNyYztWaXNpYmxlOz47bDwvRGVza3RvcE1vZHVsZXMvQXJ0L0ltYWdlcy9ob3QuZ2lmO288Zj47Pj47Oz47dDxwPHA8bDxUZXh0O1Zpc2libGU7PjtsPOacquWuoeaguDtvPGY+Oz4+Oz47Oz47dDxwPHA8bDxUZXh0Oz47bDwyMDE3LTA3LTE0Oz4+Oz47Oz47dDxwPHA8bDxJbWFnZVVybDs+O2w8L0Rlc2t0b3BNb2R1bGVzL0FydC9JbWFnZXMvcG9pbnQuZ2lmOz4+Oz47Oz47Pj47dDw7bDxpPDA+O2k8MT47aTwyPjtpPDQ+O2k8Nj47aTw4PjtpPDEwPjtpPDEyPjtpPDE0Pjs+O2w8dDxAPFxlOz47Oz47dDxwPHA8bDxJbWFnZVVybDtOYXZpZ2F0ZVVybDtWaXNpYmxlOz47bDwvaW1hZ2VzL2VkaXQuZ2lmOy9EZWZhdWx0LmFzcHg/dGFiaWQ9ODQ2MCZjdGw9RWRpdCZtaWQ9MTM5MzMmSWQ9OTIzMjc7bzxmPjs+Pjs+Ozs+O3Q8cDxsPHNyYztWaXNpYmxlOz47bDwvRGVza3RvcE1vZHVsZXMvQXJ0L0ltYWdlcy9wb2ludC5naWY7bzxmPjs+Pjs7Pjt0PHA8cDxsPFRleHQ7VmlzaWJsZTs+O2w8XGU7bzxmPjs+Pjs+Ozs+O3Q8cDxwPGw8VGV4dDtOYXZpZ2F0ZVVybDs+O2w85YWz5LqO5ZCO5Yuk6ZuG5ZuiMjAxN34yMDE45a2m5bm05pqR5YGH5bel5L2c5a6J5o6S55qELi4uOy9EZWZhdWx0LmFzcHg/dGFiaWQ9ODQ2MCZjdGw9RGV0YWlsJm1pZD0xMzkzMyZJZD05MjMyNyZTa2luU3JjPVtHXVNraW5zL2NhbGVuZGFyL2NhbGVuZGFyOz4+O3A8bDx0aXRsZTs+O2w85YWz5LqO5ZCO5Yuk6ZuG5ZuiMjAxN34yMDE45a2m5bm05pqR5YGH5bel5L2c5a6J5o6S55qE6YCa55+lOz4+Pjs7Pjt0PHA8bDxzcmM7VmlzaWJsZTs+O2w8L0Rlc2t0b3BNb2R1bGVzL0FydC9JbWFnZXMvaG90LmdpZjtvPGY+Oz4+Ozs+O3Q8cDxwPGw8VGV4dDtWaXNpYmxlOz47bDzmnKrlrqHmoLg7bzxmPjs+Pjs+Ozs+O3Q8cDxwPGw8VGV4dDs+O2w8MjAxNy0wNy0xMzs+Pjs+Ozs+O3Q8cDxwPGw8SW1hZ2VVcmw7PjtsPC9EZXNrdG9wTW9kdWxlcy9BcnQvSW1hZ2VzL3BvaW50LmdpZjs+Pjs+Ozs+Oz4+Oz4+O3Q8cDxsPFZpc2libGU7PjtsPG88Zj47Pj47bDxpPDA+Oz47bDx0PDtsPGk8MD47PjtsPHQ8O2w8aTwyPjtpPDQ+O2k8Nj47PjtsPHQ8cDxwPGw8VGV4dDtFbmFibGVkOz47bDzkuIrkuIDpobU7bzxmPjs+Pjs+Ozs+O3Q8cDxwPGw8VGV4dDtFbmFibGVkOz47bDzkuIvkuIDpobU7bzxmPjs+Pjs+Ozs+O3Q8cDxwPGw8VGV4dDs+O2w856ysMemhtTs+Pjs+Ozs+Oz4+Oz4+Oz4+O3Q8cDxwPGw8VGV4dDtOYXZpZ2F0ZVVybDtWaXNpYmxlOz47bDzmm7TlpJouLjtodHRwOi8vd3d3LnNodS5lZHUuY246ODAvRGVmYXVsdC5hc3B4P3RhYmlkPTg0NjQ7bzx0Pjs+Pjs+Ozs+O3Q8cDxwPGw8VGV4dDs+O2w86L+U5ZueOz4+Oz47Oz47Pj47Pj47Pj47Pj47Pj47Pj47Pj47dDw7bDxpPDE+Oz47bDx0PDtsPGk8MD47PjtsPHQ8O2w8aTwzPjs+O2w8dDw7bDxpPDA+Oz47bDx0PDtsPGk8MT47aTwzPjtpPDU+Oz47bDx0PDtsPGk8MD47PjtsPHQ8O2w8aTwwPjtpPDE+Oz47bDx0PDtsPGk8MT47aTwzPjtpPDU+Oz47bDx0PHA8cDxsPEZvbnRfQm9sZDtUZXh0O18hU0I7PjtsPG88dD475pel5Y6G5qih5byPO2k8MjA0OD47Pj47Pjs7Pjt0PHA8cDxsPEZvbnRfQm9sZDtUZXh0O18hU0I7PjtsPG88Zj475qCh5Y6G5qih5byPO2k8MjA0OD47Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPOacrOWRqOWFqOagoea0u+WKqOWuieaOkjs+Pjs+Ozs+Oz4+O3Q8O2w8aTwwPjtpPDE+O2k8Mj47aTwzPjtpPDQ+Oz47bDx0PHA8cDxsPFRleHQ7VmlzaWJsZTs+O2w85pel56iL5a6J5o6SO288Zj47Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7VmlzaWJsZTs+O2w85a2m5pyv5oql5ZGK6aKE5ZGKO288Zj47Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7VmlzaWJsZTs+O2w856m655m9O288Zj47Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7VmlzaWJsZTs+O2w856m655m9O288Zj47Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7VmlzaWJsZTs+O2w856m655m9O288Zj47Pj47Pjs7Pjs+Pjs+Pjs+Pjt0PDtsPGk8MD47PjtsPHQ8O2w8aTwwPjtpPDI+Oz47bDx0PDtsPGk8MT47aTw0PjtpPDY+Oz47bDx0PEAwPHA8cDxsPFNEOz47bDxsPFN5c3RlbS5EYXRlVGltZSwgbXNjb3JsaWIsIFZlcnNpb249MS4wLjUwMDAuMCwgQ3VsdHVyZT1uZXV0cmFsLCBQdWJsaWNLZXlUb2tlbj1iNzdhNWM1NjE5MzRlMDg5PDIwMTctMDgtMTE+Oz47Pj47Pjs7Ozs7Ozs7Ozs+Ozs+O3Q8O2w8aTwwPjs+O2w8dDw7bDxpPDA+Oz47bDx0PDtsPGk8MD47PjtsPHQ8dDxwPHA8bDxEYXRhVGV4dEZpZWxkO0RhdGFWYWx1ZUZpZWxkOz47bDxOYW1lO0lEOz4+Oz47dDxpPDM3PjtAPDIwMDctMjAwOOWkjyAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA7MjAwOC0yMDA556eLICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDsyMDA4LTIwMDnlhqwgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgOzIwMDgtMjAwOeaYpSAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA7MjAwOC0yMDA55aSPICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDsyMDA5LTIwMTDnp4sgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgOzIwMDktMjAxMOWGrCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA7MjAwOS0yMDEw5pilICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDsyMDA5LTIwMTDlpI8gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgOzIwMTAtMjAxMeWkjyAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA7MjAxMC0yMDEx5YasOzIwMTAtMjAxMeaYpSAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA7MjAxMC0yMDEx5aSPICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDsyMDExLTIwMTLnp4sgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgOzIwMTEtMjAxMuWGrCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA7MjAxMS0yMDEy5pilICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA7MjAxMS0yMDEy5aSPOzIwMTItMjAxM+eniyAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA7MjAxMi0yMDEz5YasICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDsyMDEyLTIwMTPmmKUgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgOzIwMTItMjAxM+WkjyAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA7MjAxMy0yMDE056eLICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDsyMDEzLTIwMTTlhqwgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgOzIwMTMtMjAxNOaYpSAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA7MjAxMy0yMDE05aSPICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDsyMDE0LTIwMTXnp4sgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgOzIwMTQtMjAxNeWGrCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA7MjAxNC0yMDE15pilICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDsyMDE0LTIwMTXlpI8gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgOzIwMTUtMjAxNueniyA7MjAxNS0yMDE25YasOzIwMTUtMjAxNuaYpTsyMDE1LTIwMTblpI87MjAxNi0yMDE356eLOzIwMTYtMjAxN+WGrDsyMDE2LTIwMTfmmKU7MjAxNi0yMDE35aSPOz47QDwxOzI7Mzs0OzU7Njs3Ozg7OTsxMDsxMzsxNDsxNTsxNjsxNzsyMDsyMTsyMjsyMzsyNDsyNTsyNjsyNzsyODsyOTszMDszMTszMjszMzszNTszNzszODszOTs0MDs0Mjs0Mzs0NTs+PjtsPGk8MzY+Oz4+Ozs+Oz4+Oz4+Oz4+O3Q8cDxwPGw8VmlzaWJsZTs+O2w8bzxmPjs+Pjs+Ozs+Oz4+O3Q8O2w8aTwxPjtpPDI+O2k8Nj47aTw3PjtpPDk+Oz47bDx0PHA8cDxsPFRleHQ7PjtsPDIwMTflubQ45pyIMTHml6U7Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPOaYn+acn+S6lDs+Pjs+Ozs+O3Q8QDA8cDxwPGw8RGF0YUtleXM7XyFJdGVtQ291bnQ7PjtsPGw8PjtpPDE+Oz4+Oz47Ozs7Ozs7Oz47bDxpPDA+Oz47bDx0PDtsPGk8MT47aTwyPjtpPDM+O2k8NT47aTw2PjtpPDc+O2k8OT47PjtsPHQ8cDxwPGw8TmF2aWdhdGVVcmw7PjtsPC9EZWZhdWx0LmFzcHg/dGFiaWQ9ODQ2MCZjdGw9RGV0YWlsJm1pZD0xMzkyOSZUSUQ9OTIzMzYmU2tpblNyYz1bR11Ta2lucy9jYWxlbmRhci9jYWxlbmRhciZwdGFiSUQ9JWU2JWEwJWExJWU1JThlJTg2JWU5JWE2JTk2JWU5JWExJWI1Oz4+Oz47bDxpPDA+Oz47bDx0PHA8cDxsPFRleHQ7PjtsPOWOjOawp+awqOawp+WMluW3peiJuuaWsOi/m+WxlTs+Pjs+Ozs+Oz4+O3Q8QDxUcnVlOz47Oz47dDxwPHA8bDxUZXh0Oz47bDzlnLDngrnvvJo7Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPOagoeacrOmDqOS4nOWMuueOr+WMlualvDQwMeS8muiuruWupDs+Pjs+Ozs+O3Q8QDxUcnVlOz47Oz47dDxwPHA8bDxUZXh0Oz47bDzml7bpl7TvvJo7Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPDIwMTflubQ45pyIMTHml6UgOTowMDs+Pjs+Ozs+Oz4+Oz4+O3Q8QDA8cDxwPGw8RGF0YUtleXM7XyFJdGVtQ291bnQ7PjtsPGw8PjtpPDE+Oz4+Oz47Ozs7Ozs7Oz47bDxpPDA+Oz47bDx0PDtsPGk8MT47PjtsPHQ8cDxwPGw8TmF2aWdhdGVVcmw7PjtsPC9EZWZhdWx0LmFzcHg/dGFiaWQ9ODQ2MCZjdGw9RGV0YWlsJm1pZD0xMzkyOSZUSUQ9OTIzMzYmU2tpblNyYz1bR11Ta2lucy9jYWxlbmRhci9jYWxlbmRhciZwdGFiSUQ9JWU2JWEwJWExJWU1JThlJTg2JWU5JWE2JTk2JWU5JWExJWI1Oz4+Oz47bDxpPDA+O2k8Mj47PjtsPHQ8cDxwPGw8VGV4dDs+O2w85Y6M5rCn5rCo5rCn5YyW5bel6Im65paw6L+b5bGVOz4+Oz47Oz47dDxwPHA8bDxUZXh0Oz47bDwyMDE35bm0N+aciDI05pelOz4+Oz47Oz47Pj47Pj47Pj47dDxwPHA8bDxWaXNpYmxlOz47bDxvPGY+Oz4+Oz47Oz47Pj47Pj47Pj47dDxwPGw8VmlzaWJsZTs+O2w8bzxmPjs+PjtsPGk8MD47PjtsPHQ8O2w8aTwwPjs+O2w8dDw7bDxpPDE+O2k8Mz47aTw1PjtpPDc+O2k8OT47aTwxMT47aTwxMz47aTwxNT47aTwxNz47aTwyMz47aTwyNT47aTwzMT47aTwzMz47aTwzOT47aTw0MT47aTw0Nz47aTw0OT47aTw1NT47aTw1Nz47aTw2Mz47aTw2NT47PjtsPHQ8cDxwPGw8VGV4dDs+O2w85LiK5LiA5ZGoOz4+Oz47Oz47dDxwPHA8bDxUZXh0Oz47bDzkuIvkuIDlkag7Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPDIwMTYtMjAxN+WtpuW5tOWkj+Wto+WtpuacnyDlhajmoKHmtLvliqjlronmjpI7Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPOaXpSZuYnNwXDvmnJ87Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPOaYnyZuYnNwXDvmnJ87Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPOS4iiZuYnNwXDvljYg7Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPOS4iyZuYnNwXDvljYg7Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPDA4LTA3Oz4+Oz47Oz47dDxwPHA8bDxUZXh0Oz47bDzmmJ/mnJ/kuIA7Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPDA4LTA4Oz4+Oz47Oz47dDxwPHA8bDxUZXh0Oz47bDzmmJ/mnJ/kuow7Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPDA4LTA5Oz4+Oz47Oz47dDxwPHA8bDxUZXh0Oz47bDzmmJ/mnJ/kuIk7Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPDA4LTEwOz4+Oz47Oz47dDxwPHA8bDxUZXh0Oz47bDzmmJ/mnJ/lm5s7Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPDA4LTExOz4+Oz47Oz47dDxwPHA8bDxUZXh0Oz47bDzmmJ/mnJ/kupQ7Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPDA4LTEyOz4+Oz47Oz47dDxwPHA8bDxUZXh0Oz47bDzmmJ/mnJ/lha07Pj47Pjs7Pjt0PHA8cDxsPFRleHQ7PjtsPDA4LTEzOz4+Oz47Oz47dDxwPHA8bDxUZXh0Oz47bDzmmJ/mnJ/ml6U7Pj47Pjs7Pjs+Pjs+Pjs+Pjs+Pjs+Pjs+Pjs+Pjs+Pjs+Pjs+Pjs+Pjs+Pjs+Pjs+6rBupuuFO+1/Np44AL/hkTTsTpg=',
                ));
            return $content;
        }        
    }

?>