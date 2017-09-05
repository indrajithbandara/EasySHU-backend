<?php

    session_start();

    error_reporting(0);

    /******错误处理******/
    function ErrHandler($error_level,$error_message,$error_file,$error_line,$error_context)
    {
        $IntTime=(time()+8*3600);
        $Now=Gmdate("Y-m-d H:i:s",$IntTime);
        $Err='['.$Now."] Fatal error: $error_message in $error_file on line $error_line\r\n";
        error_log($Err,3,$_SERVER['DOCUMENT_ROOT']."/Server_Log.txt");
        if($error_level==E_USER_ERROR)
        {
            exit();
        }
    }
    set_error_handler("ErrHandler");
    /////////////////////

    define('AUTHOR',TRUE);

    include_once('class/FunctionQuery.php');

    //////////////// 自动加载库 ///////////////
    spl_autoload_register(function($class){
        include 'module/'.$class.".php";
    });
    //////////////////////////////////////////

    ////////////
    //echo "<pre>";
    //print_r($_POST);
    ////////////

    $module=FunctionQuery::QueryFunctionModule($_POST['function']);

    /* 如果不为账户相关操作，则进行用户鉴权 */
    if($module!='UserConfig' && $module!='TimeService')
    {
        $userhandler=UserConfig::getInstance();
        $result=$userhandler->
        ValidateUser($_POST['usr'],
                     $_POST['psw'],
                     $_POST['randstr'],
                     $_POST['timestamp'],
                     $_POST['sign']);
        if(!$result)exit();
    }

    $handler=$module::getInstance();
    $methodname=$_POST['function'];
    $data=$_POST['data'];
    $handler->$methodname($data);
    exit();

    session_commit();

?>