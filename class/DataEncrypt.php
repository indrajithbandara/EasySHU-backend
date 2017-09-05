<?php
    //////////////// 自动加载库 ///////////////
    spl_autoload_register(function($class){
        include $class.".php";
        //echo "include $class.php!"."<br>";
    });
    //////////////////////////////////////////

    class DataEncrypt
    {

        /*生成新的公私钥*/
        public static function generateKey()
        {
            $config=array(
                "digest_alg"=>"sha512",
                "private_key_bits"=>2048,
                "private_key_type"=>OPENSSL_KEYTYPE_RSA,
            );
            //创建公私钥
            $res=openssl_pkey_new($config);
            //导出私钥
            openssl_pkey_export($res,$privKey);
            //导出公钥
            $pubKey=openssl_pkey_get_details($res);
            $pubKey=$pubKey["key"];
            return array("pubKey"=>$pubKey,"privKey"=>$privKey);
        }

        public static function encryptDataUsePrivKey($privKey,$data)
        {
            if(!$privateKey=openssl_pkey_get_private($privKey))return false;
            openssl_private_encrypt($data,$encypted,$privateKey);
            $encypted=base64_encode($encypted);
            return $encypted;
        }

        public static function decryptDataUsePubKey($pubKey,$encypted_data)
        {
            if(!$publicKey=openssl_pkey_get_public($pubKey))return false;
            $encypted_data=base64_decode($encypted_data);
            openssl_public_decrypt($encypted_data,$data,$publicKey);
            return $data;
        }

        public static function encryptDataUsePubKey($pubKey,$data)
        {
            if(!$publicKey=openssl_pkey_get_public($pubKey))return false;
            openssl_public_encrypt($data,$encypted,$publicKey);
            $encypted=base64_encode($encypted);
            return $encypted;
        }

        public static function decryptDataUsePrivKey($privKey,$encypted_data)
        {
            if(!$privateKey=openssl_pkey_get_private($privKey))return false;
            $encypted_data=base64_decode($encypted_data);
            openssl_private_decrypt($encypted_data,$data,$privateKey);
            return $data;
        }

    }

?>