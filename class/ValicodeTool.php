<?php
    //////////////// 自动加载库 ///////////////
    spl_autoload_register(function($class){
        include $class.".php";
        //echo "include $class.php!"."<br>";
    });
    //////////////////////////////////////////

    class ValicodeTool{

        public static function getValicode($CurlNetworkInstance,$ValiURL)
        {
            //echo 'operation start at:'.microtime(true).'<br>';

            $pic_index=mt_rand();
            $result='';
            $validate=false;

            while(!$validate)
            {
                //echo 'repeat recog.<br>';
                //$ValiURL='Login/GetValidateCode?+GetTimestamp()';
                if(!$CurlNetworkInstance->CurlgetPic($ValiURL,'tmp/vali'.$pic_index.'.jpeg'))continue;

                //echo 'picture saved at:'.microtime(true).'<br>';

                $a=new ShuValipicFormatter();
                $a->valicodeSimplify('tmp/vali'.$pic_index.'.jpeg','tmp/result'.$pic_index.'.png');

                //echo 'valicode simplified at:'.microtime(true).'<br>';

                $api=new TesseractAPI();
                $api->setLang(array('num'));
                $api->setTargetImage($_SERVER['DOCUMENT_ROOT'].'/tmp/result'.$pic_index.'.png');
                $api->setOutputMode(true);
                $api->setPageSeg(7);
                $api->setWhiteList(range(0,9),range('a','z'),range('A','Z'));
                $result=$api->run();

                $tmp=str_replace(' ','',$result);
                $result=$tmp[0];
                $result=strtolower($result);
                //echo 'recog:'.$result.'<br>';
                if(strlen($result)==4)$validate=true;
            }

            /* 删除临时文件 */
            //if(file_exists('tmp/vali'.$pic_index.'.jpeg'))unlink('tmp/vali'.$pic_index.'.jpeg');
            //if(file_exists('tmp/result'.$pic_index.'.png'))unlink('tmp/result'.$pic_index.'.png');

            return $result;
            //echo 'operation end at:'.microtime(true).'<br>';            
        }
    }

?>