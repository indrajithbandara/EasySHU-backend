<?php

    /**
     * 本库用作对上海大学选课系统等的验证码作预处理
     * 从android自定义的处理库将代码移植至php
     * by zean Huang
     */

    class ShuValipicFormatter{

        private static $GrayLevel=140;
        private static $BlockLimit=15;

        private static $PixPosX=[];
        private static $PixPosY=[];

        public static function valicodeSimplify($image,$saveDir){
            $pic=imagecreatefromjpeg($image);
            $PicSizeX=imagesx($pic);
            $PicSizeY=imagesy($pic);
            $pic=imagescale($pic,$PicSizeX*2,$PicSizeY*2);
            $PicSizeX=imagesx($pic);
            $PicSizeY=imagesy($pic);
            self::valicodeGrey($pic,140);
            self::removeIsolatedPixel($pic);
            self::removePixelBlock($pic,10,40);
            $pic=self::erosion($pic,true);
            $pic=self::dilation($pic,true);
            $pic=self::dilation($pic,false);
            $pic=self::erosion($pic,true);
            imagepng($pic,$saveDir);
            imagedestroy($pic);
        }

        /* 灰度化图像 */
        private static function valicodeGrey(&$pic,$gate)
        {
            $PicSizeX=imagesx($pic);
            $PicSizeY=imagesy($pic);
            $white=imagecolorallocate($pic,255,255,255);
            $black=imagecolorallocate($pic,0,0,0);
            for($i=0;$i<$PicSizeX;$i++)
            {
                for($j=0;$j<$PicSizeY;$j++)
                {
                    $PixColor=imagecolorat($pic,$i,$j);
                    $Pixred=($PixColor>>16) & 0xFF;
                    $Pixgreen=($PixColor>>8) & 0xFF;
                    $Pixblue=$PixColor & 0xFF;
                    $greyScale=intdiv($Pixred+$Pixgreen+$Pixblue,3);
                    //echo $i.' '.$j.' '.$greyScale.'<br>';
                    if($greyScale>$gate)
                    {
                        imagesetpixel($pic,$i,$j,$white);
                    }
                    else
                    {
                        imagesetpixel($pic,$i,$j,$black);
                    }
                }
            }
            /*去除图片下方由于未知特殊原因形成的横杠TAT*/
            for($i=0;$i<$PicSizeX;$i++)
            {
                imagesetpixel($pic,$i,$PicSizeY-1,$white);
            }
        }

        /* 去除单个噪音像素 */
        private static function removeIsolatedPixel(&$pic)
        {
            $PicSizeX=imagesx($pic);
            $PicSizeY=imagesy($pic);
            $white=imagecolorallocate($pic,255,255,255);
            $black=imagecolorallocate($pic,0,0,0);
            for($i=0;$i<$PicSizeX;$i++)
            {
                for($j=0;$j<$PicSizeY;$j++)
                {
                    $flag=false;
                    if(imagecolorat($pic,$i,$j)!=$black)continue;
                    for($x=$i-1;$x<=$i+1;$x++)
                    {
                        for($y=$j-1;$y<=$j+1;$y++)
                        {
                            if($x<0 || $y<0 || $x>=$PicSizeX || $y>=$PicSizeY)continue;
                            if($x==$i && $y==$j)continue;
                            if(imagecolorat($pic,$x,$y)==$black)
                            {
                                $flag=true;
                                break;
                            }
                        }
                    }
                    if(!$flag)
                    {
                        imagesetpixel($pic,$i,$j,$white);
                    }
                    $flag=false;
                }
            }
        }

        /* 去除多像素噪音色块,将图像填充为红色 */
        private static function removePixelBlock(&$pic,$maxAllowPix,$maxDepth)
        {
            $white=imagecolorallocate($pic,255,255,255);
            $black=imagecolorallocate($pic,0,0,0);
            $PicSizeX=imagesx($pic);
            $PicSizeY=imagesy($pic);
            for($i=0;$i<$PicSizeX;$i++)
            {
                for($j=0;$j<$PicSizeY;$j++)
                {
                    if(imagecolorat($pic,$i,$j)!=$white){
                        self::floodfill4($pic,$i,$j,$PicSizeX,$PicSizeY,$maxDepth);
                        if(self::$PixelCnt<$maxAllowPix){
                            for($k=0;$k<self::$PixelCnt;$k++)
                            {
                                imagesetpixel($pic,self::$PixPosX[$k],self::$PixPosY[$k],$white);
                                self::$PixPosX[$k]=self::$PixPosY[$k]=0;      
                            }
                        }
                        else
                        {
                            for($k=0;$k<self::$PixelCnt;$k++)
                            {
                                self::$PixPosX[$k]=self::$PixPosY[$k]=0;
                            }
                        }
                        self::$PixelCnt=0;
                    }
                }
            }
        }

        /* 四向种子填充算法,计算色块大小,将图像填充为红色 */
        static $PixelCnt=0;
        private static function floodfill4($pic,$x,$y,$x_len,$y_len,$maxDepth)
        {
            if(self::$PixelCnt>$maxDepth)return;
            $white=imagecolorallocate($pic,255,255,255);
            $black=imagecolorallocate($pic,0,0,0);
            $red=imagecolorallocate($pic,255,0,0);
            if($x>=$x_len || $y>=$y_len || $x<0 || $y<0)return;
            $Pixel=imagecolorat($pic,$x,$y);
            if($Pixel!=$white && $Pixel!=$red)
            {
                self::$PixPosX[self::$PixelCnt]=$x;
                self::$PixPosY[self::$PixelCnt]=$y;
                self::$PixelCnt++;
                imagesetpixel($pic,$x,$y,$red);
                self::floodfill4($pic,$x-1,$y,$x_len,$y_len,$maxDepth);
                self::floodfill4($pic,$x+1,$y,$x_len,$y_len,$maxDepth);
                self::floodfill4($pic,$x,$y-1,$x_len,$y_len,$maxDepth);
                self::floodfill4($pic,$x,$y+1,$x_len,$y_len,$maxDepth);
            }
        }

        private static function erosion($pic,$dir)
        {
            //echo 'erosion start:'.microtime(true)."<br>";
            $PicSizeX=imagesx($pic);
            $PicSizeY=imagesy($pic);
            $out=imagecreatetruecolor($PicSizeX,$PicSizeY);
            $white=imagecolorallocate($out,255,255,255);
            $red=imagecolorallocate($pic,255,0,0);
            $red2=imagecolorallocate($out,255,0,0);
            if($dir)
            {
                for($i=0;$i<$PicSizeY;$i++)
                {
                    for($j=1;$j<$PicSizeX-1;$j++)
                    {
                        if(imagecolorat($pic,$j,$i)==$red)
                        {
                            imagesetpixel($out,$j,$i,$red2);
                            if(imagecolorat($pic,$j,$i)!=$red ||
                            imagecolorat($pic,$j-1,$i)!=$red || 
                            imagecolorat($pic,$j+1,$i)!=$red)
                            {
                                imagesetpixel($out,$j,$i,$white);
                            }
                        }
                        else
                        {
                            imagesetpixel($out,$j,$i,$white);
                        }
                    }
                }
            }
            else
            {
                for($i=0;$i<$PicSizeX;$i++)
                {
                    for($j=1;$j<$PicSizeY-1;$j++)
                    {
                        if(imagecolorat($pic,$i,$j)==$red)
                        {
                            imagesetpixel($out,$i,$j,$red2);
                            if(imagecolorat($pic,$i,$j)!=$red ||
                            imagecolorat($pic,$i,$j-1)!=$red || 
                            imagecolorat($pic,$i,$j+1)!=$red)
                            {
                                imagesetpixel($out,$i,$j,$white);
                            }
                        }
                        else
                        {
                            imagesetpixel($out,$i,$j,$white);
                        }
                    }
                }
            }
            for($i=0;$i<$PicSizeX;$i++){
                imagesetpixel($out,$i,0,$white);
                imagesetpixel($out,$i,$PicSizeY-1,$white);
            }
            for($i=0;$i<$PicSizeY;$i++){
                imagesetpixel($out,0,$i,$white);
                imagesetpixel($out,$PicSizeX-1,$i,$white);
            }
            //echo 'erosion end:'.microtime(true)."<br>";
            return $out;
        }

        private static function dilation($pic,$dir)
        {
            //echo 'dilation start:'.microtime(true)."<br>";
            $PicSizeX=imagesx($pic);
            $PicSizeY=imagesy($pic);
            $out=imagecreatetruecolor($PicSizeX,$PicSizeY);
            $white=imagecolorallocate($pic,255,255,255);
            $red=imagecolorallocate($pic,255,0,0);
            $white2=imagecolorallocate($out,255,255,255);
            $red2=imagecolorallocate($out,255,0,0);
            if($dir)
            {
                for($i=0;$i<$PicSizeY;$i++)
                {
                    for($j=1;$j<$PicSizeX-1;$j++)
                    {
                        if(imagecolorat($pic,$j,$i)==$white)
                        {
                            imagesetpixel($out,$j,$i,$white2);
                            if(imagecolorat($pic,$j,$i)==$red ||
                            imagecolorat($pic,$j-1,$i)==$red || 
                            imagecolorat($pic,$j+1,$i)==$red)
                            {
                                imagesetpixel($out,$j,$i,$red2);
                            }
                        }
                        else
                        {
                            imagesetpixel($out,$j,$i,$red2);
                        }
                    }
                }
            }
            else
            {
                for($i=0;$i<$PicSizeX;$i++)
                {
                    for($j=1;$j<$PicSizeY-1;$j++)
                    {
                        if(imagecolorat($pic,$i,$j)==$white)
                        {
                            imagesetpixel($out,$i,$j,$white2);
                            if(imagecolorat($pic,$i,$j)==$red ||
                            imagecolorat($pic,$i,$j-1)==$red || 
                            imagecolorat($pic,$i,$j+1)==$red)
                            {
                                imagesetpixel($out,$i,$j,$red2);
                            }
                        }
                        else
                        {
                            imagesetpixel($out,$i,$j,$red2);
                        }
                    }
                }
            }
            for($i=0;$i<$PicSizeX;$i++){
                imagesetpixel($out,$i,0,$white);
                imagesetpixel($out,$i,$PicSizeY-1,$white);
            }
            for($i=0;$i<$PicSizeY;$i++){
                imagesetpixel($out,0,$i,$white);
                imagesetpixel($out,$PicSizeX,$i,$white);
            }
            //echo 'dilation end:'.microtime(true)."<br>";
            return $out;
        }

    }

?>