<?php

    /** 
    *   tesseract 命令行执行工具 
    *   @author ThunderBird1997
    *   这是参考 @link https://github.com/thiagoalessio/tesseract-ocr-for-php 编写的PHP识别库。
    **/

    class TesseractAPI{

        /*tesseract程序帮助内容

        tesseract imagename|stdin outputbase|stdout [options...] [configfile...]

        OCR options:
        --tessdata-dir PATH   Specify the location of tessdata path.
        --user-words PATH     Specify the location of user words file.
        --user-patterns PATH  Specify the location of user patterns file.
        -l LANG[+LANG]        Specify language(s) used for OCR.
        -c VAR=VALUE          Set value for config variables.
                                Multiple -c arguments are allowed.
        --psm NUM             Specify page segmentation mode.
        --oem NUM             Specify OCR Engine mode.
        NOTE: These options must occur before any configfile.

        Page segmentation modes:
        0    Orientation and script detection (OSD) only.
        1    Automatic page segmentation with OSD.
        2    Automatic page segmentation, but no OSD, or OCR.
        3    Fully automatic page segmentation, but no OSD. (Default)
        4    Assume a single column of text of variable sizes.
        5    Assume a single uniform block of vertically aligned text.
        6    Assume a single uniform block of text.
        7    Treat the image as a single text line.
        8    Treat the image as a single word.
        9    Treat the image as a single word in a circle.
        10   Treat the image as a single character.
        11   Sparse text. Find as much text as possible in no particular order.
        12   Sparse text with OSD.
        13   Raw line. Treat the image as a single text line,bypassing hacks that are Tesseract-specific.

        OCR Engine modes:
        0    Original Tesseract only.
        1    Cube only.
        2    Tesseract + cube.
        3    Default, based on what is available.

        Single options:
        -h, --help            Show this help message.
        --help-psm            Show page segmentation modes.
        --help-oem            Show OCR Engine modes.
        -v, --version         Show version information.
        --list-langs          List available languages for tesseract engine.
        --print-parameters    Print tesseract parameters to stdout.

        */

        ////////////////////////////////////////////////////////////

        /*
            图像地址
        */
        private $imageName;
        
        /*
            命令行程序调用位置
        */
        private $executable = "C:\Tesseract";

        /*
            输出形式
            注：当其为'stdout'时输出到命令行
            　  否则输出到一个名为其值的txt文件
        */
        private $outputMode;

        //////////////////为引擎选项（options...)/////////////////////

        /*
            tessdata训练数据存放位置
        */
        private $tessdataDir;

        /*
            userword存放位置
        */
        private $userwordDir;

        /*
            userpattern存放位置
        */
        private $userpatternDir;

        /*
            识别语言
        */
        private $lang=[];

        /**
         *   分页模式
         *   @var int
        **/
        private $PageSegMode=3;

        /**
        *    OCR引擎模式
        *    @var int
        **/
        private $OCRengineMode=3;

        /**
        *    配置参数
        **/
        private $config=[];

        ///////////////////////// 函数 ////////////////////////////

        /**
        *   命令行指令生成
        **/
        public function cmdGenerate(){
            return $this->executable.'\tesseract.exe '.$this->ioCmdGenerate().$this->optionCmdGenerate().$this->configCmdGenerate();
        }

        /**
        *   生成并运行指令
        **/
        public function run(){
            $cmd=$this->cmdGenerate();
            //echo $cmd; //为这行取消注释可以输出指令内容
            exec($cmd,$result);
            return $result;
        }

        /////////////////////////////////////////////////////////

        /**
        *  
        *   参数设置(保留接口，测试用)
        **/
        public function setParam($name,$value){
            $this->$name=$value;
            echo "set $name to $value <br>";
        }

        /**
        *   参数获取(保留接口，测试用)
        **/
        public function getParam($name){
            $value=$this->$name;
            return $value;
        }


        /**
        *   显示可用语言列表
        **/
        public function listLang(){
            $cmd=$this->executable.'\tesseract.exe --list-langs'.$this->tessdataDir;
            //echo $cmd;
            echo "<pre>";
            exec($cmd,$ListofLang);
            //print_r($ListofLang);
            $ListofLang=array_slice($ListofLang,1,count($ListofLang));
            //print_r($ListofLang);
            return $ListofLang;
        }

        ///////////////////////////////////////////////////////////////

        /**
        *   设置待识别图像
        **/
        public function setTargetImage($img){
            $this->imageName=$img;
        }

        /**
        *   设置tesseract指令调用位置
        **/
        public function setExecutableDir($dir){
            $this->executable=$dir;
        }

        /**
        *   设置输出方式
        *   如果是cmd输出方式，则将直接输出在程序中，为false则输出到文件
        **/
        public function setOutputMode(bool $cmdOutput,$des=''){
            if($cmdOutput){
                $this->outputMode='stdout';
            }else{
                $this->outputMode=$des;
            }
        }

        ///////////////////////////////////////////////////////////////

        /**
        *   设置训练数据存放位置
        **/
        public function setTessdataDir($dir){
            $this->tessdataDir=$dir;
        }

        /**
        *   设置userword存放位置
        **/
        public function setUserwordDir($dir){
            $this->userwordDir=$dir;
        }

        /**
        *   设置userpattern存放位置
        **/
        public function setUserpatternDir($dir){
            $this->userpatternDir=$dir;
        }

        /**
        *   设置识别语言
        **/
        public function setLang($langlist){
            $this->lang=$langlist;
        }

        /**
        *   设置分页模式
        **/
        public function setPageSeg($psm){
            $this->PageSegMode=$psm;
        }

        /**
        *   设置引擎模式
        **/
        public function setOCRengineMode($oem){
            $this->OCRengineMode=$oem;
        } 

        /**
        *   设置白名单（允许匹配的字符）
        **/    
        public function setWhiteList(...$args){
            $whitelist="";
            foreach($args as $arg){
                foreach($arg as $tmp){
                    $whitelist.=$tmp;
                }
            }
            $this->config('tessedit_char_whitelist',$whitelist);
        }

        public function config($key,$value){
            $this->config[$key]=$value;
        }

        ///////////////////////////////////////////////////////////////
        
        /**
        *  生成输入输出配置指令
        **/
        public function ioCmdGenerate(){
            return $this->imageName.' '.$this->outputMode;
        }     

        /**
        *  生成选项配置指令
        **/
        public function optionCmdGenerate(){
            $cmdtmp=' ';

            if($this->tessdataDir!==null){
                $cmdtmp.="--tessdata-dir ";
                $cmdtmp.=$this->tessdataDir.' ';
            }

            if($this->userwordDir!==null){
                $cmdtmp.="--user-words ";
                $cmdtmp.=$this->userwordDir.' ';
            }  

            if($this->userpatternDir!==null){
                $cmdtmp.="--user-patterns ";
                $cmdtmp.=$this->userpatternDir.' ';
            }  

            if($this->lang!=null){
                $cmdtmp.="-l ";
                for($i=0;$i<count($this->lang);$i++){
                    $cmdtmp.=$this->lang[$i].'+';
                    //echo "add:".$this->lang[$i];
                }
                $cmdtmp=substr($cmdtmp,0,strlen($cmdtmp)-1).' ';
            }

            $cmdtmp.='--psm '.$this->PageSegMode.' --oem '.$this->OCRengineMode;

            return $cmdtmp;
        }

        /**
        *  生成配置指令
        **/
        public function configCmdGenerate(){
            $buildParam = function ($config, $value) {
                return ' -c '.escapeshellarg("$config=$value");
            };
            return implode(' ', array_map(
                $buildParam,
                array_keys($this->config),
                array_values($this->config)
            ));
        }
    }
?>