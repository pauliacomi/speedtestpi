#!/usr/bin/php5
<?php
/*
 *  Speedtest.net linux terminal client.
 *  This is free and open source software by Alex based on a script from Janhouse
 *  Script uses curl, executes ifconfig commands in shell and writes temporary files in temp_down folder. Make sure you have everything set up before using it.
 */
header("content-type: text/plain");
/* * *              Configuration                   * * */
$iface="eth0";
$maxrounds=2;
$downloads="/home/pi/speedtest/temp_down/";
$uploads="/home/pi/speedtest/upload/";
$datadir="/home/pi/speedtest/data/";
/* * *              Speedtest servers               * * */
$do_server['London, Vodafone'] = "http://speedtest.vodafone.co.uk";
$do_server['Birmingham, Virgin'] = "http://perr-speedtest-1.server.virginmedia.net";
$do_server['Manchester, Virgin'] = "http://manc-speedtest-1.server.virginmedia.net";
$do_server['New York'] = "http://speedgauge2.optonline.net";

/* Variables */
$randoms=rand(100000000000, 9999999999999);
$time=time();
$day=date("H:i:s d-m-Y");
$do_size[1]=500;
$do_size[2]=1000;
$do_size[3]=1500;
$do_size[4]=2000;
$do_size[5]=2500;
$do_size[6]=3000;
$do_size[7]=3500;
$do_size[8]=4000;

/* * *              The rest                        * * */
function latency($round){
    global $server, $downloads, $do_server, $server, $iface, $randoms, $do_size,$globallatency,$maxrounds;

    $file=$downloads."latency.txt";
    $fp = fopen ($file, 'w+');
    $ch = curl_init($do_server[$server]."/speedtest/latency.txt?x=".$randoms);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $starttime=microtime(true);
    $response=curl_exec($ch);
    $endtme=microtime(true);

    $duration=$endtme-$starttime;

    curl_close($ch);
    fclose($fp);
    unlink($file);

    print round($duration, 2)."sec.";

    $globallatency+=$duration;
    if ($round > 1){
        latency(--$round);
    } else {
        print "\tAverage:".round($globallatency/$maxrounds, 2)."sec.\n";
        write_to_file(round($duration, 2), $server , "l");
    }
}

function download($size,$round){
    global $server, $downloads, $do_server, $server, $iface, $randoms, $do_size,$globaldownloadspeed,$maxrounds;

    $file=$downloads."fails_".$size.".jpg";

    print ".";
    $fp = fopen ($file, 'w+');
    $ln = $do_server[$server]."/speedtest/random".$do_size[$size]."x".$do_size[$size].".jpg?x=".$randoms."-".$size;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$ln);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $starttime=microtime(true);
    $sakuma_rx=shell_exec("sudo ifconfig ".$iface."|grep RX\ bytes|awk '{ print $2 }'|cut -d : -f 2");
    $response=curl_exec($ch);
    $beigu_rx=shell_exec("sudo ifconfig ".$iface."|grep RX\ bytes|awk '{ print $2 }'|cut -d : -f 2");
    $endtme=microtime(true);

    if ($response === false){
       print "Request failed:".curl_error($ch);
    }
    curl_close($ch);
    fclose($fp);

    #unlink($file);

    $duration=$endtme-$starttime;

    if($duration<4 && $size!=8) {
        download(++$size,$round);
    }else{
        $sakuma_rx=trim($sakuma_rx);
        $beigu_rx=trim($beigu_rx);
        $rx=$beigu_rx-$sakuma_rx;
        $rx_speed=((($rx*8)/1000)/1000)/$duration;
        if($duration<4){
          print "Duration is ".round($duration, 2)."sec - this may introduce errors.\n";
        }
        print round(filesize($file)/1000000,2)."Mb at ".round($rx_speed, 2)."Mb/s";
        $globaldownloadspeed+=$rx_speed;
        if ($round > 1){
           download($size,--$round);
        } else {
           print "\tAverage: ".round($globaldownloadspeed/$maxrounds, 2)." Mb/s.\n";
           write_to_file(round($rx_speed, 2), $server , "d");
  }
    }
}

function upload($size,$round){
    global $server, $uploads, $do_server, $server, $iface, $randoms,$globaluploadspeed,$maxrounds;

    $file=$uploads."upload_".$size;

    print ".";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
    curl_setopt($ch, CURLOPT_URL, $do_server[$server]."/speedtest/upload.php?x=0.".$randoms);
    curl_setopt($ch, CURLOPT_POST, true);
    $post = array(
        "file_box"=>"@".$file,
    );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

    $starttime=microtime(true);
    $sakuma_tx=shell_exec("sudo ifconfig ".$iface."|grep TX\ bytes|awk '{print $6}'|cut -d: -f2");
    $response = curl_exec($ch);
    $beigu_tx=shell_exec("sudo ifconfig ".$iface."|grep TX\ bytes|awk '{print $6}'|cut -d: -f2");
    $endtme=microtime(true);

    if ($response === false){
       print "Request failed:".curl_error($ch);
    }
    $aiznem=substr($response, 5);
    $kopa=filesize($file)+$aiznem;

    $duration=$endtme-$starttime; // sekundes

    if($duration<4 && $size!=8){
        #print round(filesize($file)/1000000,2)."Mb is to small: ".round($duration, 2)."sec\n";
        upload(++$size,$round);
    }else{
        $sakuma_tx=trim($sakuma_tx);
        $beigu_tx=trim($beigu_tx);
        $tx=$beigu_tx-$sakuma_tx;
        $tx_speed=((($tx*8)/1000)/1000)/$duration;
        if($duration<4){
          print "Duration is ".round($duration, 2)."sec - this may introduce errors.\n";
        }
        print round(filesize($file)/1000000,2)."Mb at ".round($tx_speed, 2)."Mb/s";
        $globaluploadspeed+=$tx_speed;
        if ($round > 1){
           upload($size,--$round);
        } else {
           print "\tAverage: ".round($globaluploadspeed/$maxrounds, 2)." Mb/s.\n";
           write_to_file(round($tx_speed, 2), $server , "u");
  }
    }
}

function write_to_file($data, $server, $updown){ // l - latency; u - upload; d - download
    global $day, $datadir;
    $fp = fopen($datadir."log.txt", "a");
    fwrite($fp, $day."\t".$updown."\t".$data."\t".$server."\n");
    fclose($fp);
}

foreach ($do_server as $server => $serverurl){
  $globallatency=0;
  print "* Testing latency for $server...";
  latency($maxrounds);
}
foreach ($do_server as $server => $serverurl){
  $globaldownloadspeed=0;
  print "* Testing download speed for $server...";
  download(1,$maxrounds);
}
foreach ($do_server as $server => $serverurl){
  $globaluploadspeed=0;
  print "* Testing upload speed $server...";
  upload(1,$maxrounds);
}

?>
