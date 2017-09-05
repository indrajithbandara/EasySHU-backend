<?php
    //////////////// 自动加载库 ///////////////
    spl_autoload_register(function($class){
        include $class.".php";
        //echo "include $class.php!"."<br>";
    });
    //////////////////////////////////////////

    /**
     * Curl网络请求类
     * 通过导出$sessionId可以在多个Curl请求中使用同一个会话
     */
    class CurlNetwork
    {
        /**
         * 会话Id
         *
         * @var String
         */
        private $sessionId;

        /**
         * 服务器地址
         *
         * @var String
         */
        private $BaseURL;

        /**
         * Curl实例
         * 
         */
        private $handler;

        /**
         * 构造函数
         *
         * @param String $BaseURL 服务器地址
         */
        function __construct($BaseURL)
        {
            $this->BaseURL=$BaseURL;
            $this->handler=curl_init();
        }

        /**
         * 析构函数
         */
        function __destruct()
        {
            /*释放curl句柄*/
            curl_close($this->handler);
        }

        /**
         * get魔术方法
         *
         * @param String $name 变量名
         * @return 变量值
         */
        function __get($name)
        {
            return $this->$name;
        }

        /**
         * set魔术方法
         *
         * @param String $name 变量名
         * @param String $value 变量值
         */
        function __set($name,$value)
        {
            $this->$name=$value;
        }

        /**
         * 获取会话Id
         *
         * @param String $BaseURL 服务器地址
         * @return void
         */
        public function GetSessionID($SESSION_URL)
        {
            $this->SetCurlOption(CURLOPT_URL,$this->BaseURL.$SESSION_URL);
            $this->SetCurlOption(CURLOPT_HEADER,1);
            $this->SetCurlOption(CURLOPT_TIMEOUT,1);
            $this->SetCurlOption(CURLOPT_FOLLOWLOCATION,1);
            $this->SetCurlOption(CURLOPT_RETURNTRANSFER,1);
            $content=$this->CurlExecute();
            //i修饰符使大小写不敏感，U修饰符取消通配符的贪婪模式
            preg_match('/Set-Cookie:(.*);/iU',$content,$str);
            /*返回session*/
            return $str[1];
        }

        /**
         * 设置Curl参数
         *
         * @param String $opt 选项名
         * @param mixed $value 选项值
         * @return void
         */
        public function SetCurlOption($opt,$value)
        {
            curl_setopt($this->handler,$opt,$value);
        }

        /**
         * Curl操作执行
         *
         * @return void
         */
        private function CurlExecute()
        {
            return curl_exec($this->handler);
        }

        /**
         * Curl重置
         *
         * @return void
         */
        private function CurlReset()
        {
            curl_reset($this->handler);
        }

        /********************* 功能函数 *********************/

        /**
         * Curl发送POST请求
         * @method mixed $CurlPost($url,$dataArr)
         * @param String $url 目标相对地址
         * @param mixed $dataArr POST数据数组
         * @return void
         */
        public function CurlPost($url,$dataArr)
        {
            $this->CurlReset();
            $this->SetCurlOption(CURLOPT_URL,$this->BaseURL.$url);
            $this->SetCurlOption(CURLOPT_HEADER,0);
            $this->SetCurlOption(CURLOPT_TIMEOUT,10);
            $this->SetCurlOption(CURLOPT_FOLLOWLOCATION,1);
            $this->SetCurlOption(CURLOPT_RETURNTRANSFER,1);
            $this->SetCurlOption(CURLOPT_POST,1);
            $this->SetCurlOption(CURLOPT_POSTFIELDS,$dataArr);
            $this->SetCurlOption(CURLOPT_COOKIE,$this->sessionId);
            return $this->CurlExecute();
        }

        /**
         * Curl发送GET请求
         * @method mixed $CurlGet($url)
         * @param String $url 目标相对地址
         * @return void
         */
        public function CurlGet($url)
        {
            $this->CurlReset();
            $this->SetCurlOption(CURLOPT_URL,$this->BaseURL.$url);
            $this->SetCurlOption(CURLOPT_HEADER,0);
            $this->SetCurlOption(CURLOPT_TIMEOUT,1);
            $this->SetCurlOption(CURLOPT_FOLLOWLOCATION,1);
            $this->SetCurlOption(CURLOPT_RETURNTRANSFER,1);
            $this->SetCurlOption(CURLOPT_COOKIE,$this->sessionId);
            return $this->CurlExecute();
        }

        /**
         * Curl获取图片到本地
         * @method mixed $CurlGet($url,$dir)
         * @param String $url 目标相对地址
         * @param String $dir 目标将要保存的本地位置
         * @return bool 是否获取成功
         */
        public function CurlgetPic($url,$dir)
        {
            $fp = fopen($dir,'wb');
            $result=false;
            $retrycount=0;
            while(!$result && $retrycount<=10){
                $retrycount++;
                $this->CurlReset();
                $this->SetCurlOption(CURLOPT_URL,$this->BaseURL.$url);
                $this->SetCurlOption(CURLOPT_FILE,$fp);
                $this->SetCurlOption(CURLOPT_HEADER,0);
                $this->SetCurlOption(CURLOPT_TIMEOUT,5);
                $this->SetCurlOption(CURLOPT_CONNECTTIMEOUT,5);
                $this->SetCurlOption(CURLOPT_FOLLOWLOCATION,1);
                $this->SetCurlOption(CURLOPT_COOKIE,$this->sessionId);
                $result=$this->CurlExecute();
                //sleep(1);
            }
            fclose($fp);
            if($retrycount>10)
            {
                return false;
            }
            else
            {
                return true;
            }
        }

        /***********************************************/

    }

?>