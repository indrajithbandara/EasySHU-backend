<?php
    echo "欢迎访问EasySHU后台服务器！";
    include_once('class/CourseAnalyzer.php');
    echo "<pre>";
    //print_r(CourseAnalyzer::GenerateCourseTimeDetailWithComment("三3-4(第1周)五1-3(第1周)四1-3(1-2周)四6-7(1-2周)二1-3(第2周)二6-7(第2周)"));
    print_r(CourseAnalyzer::XK_ParseQueryCourseList(null,true));
?>