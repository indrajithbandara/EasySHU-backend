<?php

    class FunctionQuery{

        public static function QueryFunctionModule($Name)
        {
            //echo "即将开始查询函数$Name<br>";//
            $db=new mysqli('localhost','root','xiaoyubibi111','easyshu');
            $sql="SELECT * FROM `easyshu`.`functions` WHERE func = '$Name' LIMIT 1" ;
            //echo "SQL语句为：$sql<br>";
            $res=$db->query($sql);
            $arr=$res->fetch_assoc();
            $result=$arr['module'];
            //echo "函数所在模块为$result<br>";//
            $db->close();
            return $result;
        }

    }