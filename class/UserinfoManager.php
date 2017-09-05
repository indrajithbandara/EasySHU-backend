<?php
    //////////////// 自动加载库 ///////////////
    spl_autoload_register(function($class){
        include $class.".php";
        //echo "include $class.php!"."<br>";
    });
    //////////////////////////////////////////

    /* 对easyshu用户信息数据表userinfo,用户加密数据表encrypt的操作库 */

    class UserinfoManager
    {

        /******************************/
        private static $_instance;

        private function __construct()
        {
            $this->db=new mysqli('localhost','root','xiaoyubibi111','easyshu');
        }

        public function __destruct()
        {
            $this->db->close();
        }

        public static function getInstance()
        {
            if(!isset(self::$_instance))
            {
                self::$_instance=new self();
            }
            return self::$_instance;
        }

        /*************userinfo用户信息数据表***************/

        //保存的密码已经是通过公钥加密过的密码

        private $db;//数据库实例

        /* 新用户注册 */
        public function newUser($usr,$psw)
        {
            $sql='INSERT INTO `easyshu`.`userinfo`(`usr`,`psw`) VALUES (?,?)';
            $statement=$this->db->prepare($sql);
            $statement->bind_param('ss',$usr,$psw);
            return $statement->execute();
        }

        /* 用户修改密码 */
        public function changePassword($usr,$psw)
        {
            $sql="UPDATE `easyshu`.`userinfo`
                    SET
                    `psw` = ?
                    WHERE `usr` = $usr
                    ";
            $statement=$this->db->prepare($sql);
            $statement->bind_param('s',$psw);
            if($statement->execute())
            {
                return;
            }
            else
            {
                die('sql update execution failed');
            }
        }

        /* 删除用户 */
        public function deleteUser($usr)
        {
            $sql="DELETE FROM `easyshu`.`userinfo`
                    WHERE `usr` = ?
                    ";
            $statement=$this->db->prepare($sql);
            $statement->bind_param('s',$usr);
            if($statement->execute())
            {
                return;
            }
            else
            {
                die('sql delete execution failed');
            }
        }

        /* 查询用户 */
        public function queryUser($usr)
        {
            $result=[];
            $sql="SELECT * FROM `easyshu`.`userinfo` WHERE usr = $usr";
            $res=$this->db->query($sql);
            while($arr=$res->fetch_assoc())
            {
                $result=array_merge($result,$arr);
            } 
            return $result;
        }

        //////////////////////////////////////////////////

        /*************encrypt用户加密数据表***************/

        /* 加入用户私钥 */
        public function SaveUserPrivKey($usr,$privKey)
        {
            $sql='INSERT INTO `easyshu`.`encrypt`(`usr`,`key`) VALUES (?,?)';
            $statement=$this->db->prepare($sql);
            $statement->bind_param('ss',$usr,$privKey);
            return $statement->execute();
        }

        /* 删除用户私钥 */
        public function deleteUserPrivKey($usr)
        {
            $sql="DELETE FROM `easyshu`.`encrypt`
                    WHERE `usr` = ?
                    ";
            $statement=$this->db->prepare($sql);
            $statement->bind_param('s',$usr);
            if($statement->execute())
            {
                return;
            }
            else
            {
                die('sql delete execution failed');
            }
        }

        /* 获取用户私钥 */
        public function queryUserPrivKey($usr)
        {
            $result=[];
            $sql="SELECT * FROM `easyshu`.`encrypt` WHERE usr = $usr";
            $res=$this->db->query($sql);
            while($arr=$res->fetch_assoc())
            {
                $result=array_merge($result,$arr);
            } 
            return $result;
        }

        //////////////////////////////////////////////////

    }

?>