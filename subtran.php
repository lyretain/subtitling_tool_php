#!/usr/bin/php
<?php
/**
 * Subtitles zh-cn zh-tw mutual conversion
 * Usage: subtran.php dirname filename
 * Example: subtran.php "/home/download/video/Tsuki ga Michibiku Isekai Douchuu"
 * Example input file: subtitlename.zh-cn.ass
 * Example output file: subtitlename.zh-tw.ass
 */
function request($url,$data,$json=true){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    if (!empty($data)) {
        $data = json_encode($data,JSON_UNESCAPED_UNICODE);
        
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        if($json){ //发送JSON数据
            curl_setopt($curl, CURLOPT_HEADER, 0);
            $header[]="Content-Type: application/json; charset=utf-8";
            $header[]="Content-Length: ".strlen($data);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    
    
    // curl_setopt($curl, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
    // curl_setopt($curl, CURLOPT_PROXY, "192.168.3.25"); //代理服务器地址
    // curl_setopt($curl, CURLOPT_PROXYPORT, 10809); //代理服务器端口
    
    $res = curl_exec($curl);
    $errorno = curl_errno($curl);
    
    curl_close($curl);
    return $res;
}
function chkCode($string) {
    $code = array(
        'ASCII',
        'GBK',
        'UTF-8',
        'UTF-16LE'
    );
    foreach ($code as $c) {
        if ($string === iconv('UTF-8', $c, iconv($c, 'UTF-8', $string))) {
            return $c;
        }
    }
    return null;
}
$dir=pathinfo($argv[1])['dirname'];
$filename=pathinfo($argv[1])['filename'];
$filelist=scandir($dir);
$sublist=[];
for($i=0;$i<count($filelist);$i++){
    $fullname="$dir/{$filelist[$i]}";
    if(is_file($fullname) && strpos($fullname,$filename) && in_array(pathinfo($fullname)['extension'],["ass","srt","ssa"])){
        $sublist[]=$fullname;
    }
}

$api="http://api.zhconvert.org/convert";
$data['converter']="China";
$newfile="";
for($i=0;$i<count($sublist);$i++){
    echo "Start {$sublist[$i]}\n";
    if(strpos($sublist[$i],"zh-cn")){
        $data['converter']="Taiwan"; 
        $newfile=str_replace("zh-cn","zh-tw",$sublist[$i]);
    }elseif(strpos($sublist[$i],"zh-tw")){
        $data['converter']="China"; 
        $newfile=str_replace("zh-tw","zh-cn",$sublist[$i]);
    }
    echo "Convert to $newfile\n";
    if(!is_file($newfile)){
        $test=file_get_contents($sublist[$i]);
        if(chkCode($test)=="UTF-8"){
            
        }else{
            $test=mb_convert_encoding ($test, "UTF-8", chkCode($test));
        }
        $data['text']=$test;
        echo "Start convert\n";
        $subreq=request($api,$data);
        $strlen = strlen($subreq);
        $subreq=json_decode($subreq,true);
        if($subreq['code']==0 && $strlen>0){
            file_put_contents($newfile,$subreq['data']['text']);
            echo "Complete $newfile\n";
        }else{
            echo json_encode($subreq)."\n";
            echo "error: strlen: $strlen\n";
        }
    }else{
        echo "Skip\n";
    }
}
