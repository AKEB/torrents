<?
require_once('include/common.inc');
require_once('lib/common.lib');

common_init();
session_init();

setcookie('TEST',123,time()+3600);

vardump($_COOKIE['TEST']);


exit;


for($i=1;$i<=143;$i++) {
	printf('<a href="http://www.xakep.ru/magazine/xa/%1$03d/xa_%1$03d.pdf">http://www.xakep.ru/magazine/xa/%1$03d/xa_%1$03d.pdf</a><br>'."\n",$i);
}















exit;


$sum = 0;

$total_apps = 0;
$total_iad = 0;
$total_admob = 0;

$data = common_list($db, 'admob_report',false,'',' SUM(revenue) AS total ');
$sum += round($data[0]['total'],4);
$total_admob = round($data[0]['total'],4);

$data = common_list($db, 'apple_apps_report',false,'',' SUM(total_revenue) AS total ');
$sum += round($data[0]['total'],4);
$total_apps = round($data[0]['total'],4);

$data = common_list($db, 'apple_iads_report',false,'',' SUM(revenue) AS total ');
$sum += round($data[0]['total'],4);
$total_iad = round($data[0]['total'],4);

$y = date('Y');
$m = date('m');
$d = date('d');

$DIFF = 14;

$start_time = mktime(0,0,0,$m,$d-$DIFF,$y);
$end_time = time();

$date_money_sum = array();
$date_money = array();
$data = common_list($db, 'apple_apps_report',false,sql_pholder(' AND time>=? AND time<=? GROUP BY time ORDER BY time DESC ',$start_time,$end_time),' SUM(total_revenue) AS money, date');
foreach ($data as $item) {
	$date_money_sum[$item['date']] += round($item[money],2);
	$date_money['app'][$item['date']] += round($item[money],2);
}

$data = common_list($db, 'apple_iads_report',false,sql_pholder(' AND time>=? AND time<=? GROUP BY time ORDER BY time DESC ',$start_time,$end_time),' SUM(revenue) AS money, date');
foreach ($data as $item) {
	$date_money_sum[$item['date']] += round($item[money],2);
	$date_money['iAds'][$item['date']] += round($item[money],2);
}

$data = common_list($db, 'admob_report',false,sql_pholder(' AND time>=? AND time<=? GROUP BY time ORDER BY time DESC ',$start_time,$end_time),' SUM(revenue) AS money, date');
foreach ($data as $item) {
	$date_money_sum[$item['date']] += round($item[money],4);
	$date_money['admob'][$item['date']] += round($item[money],4);
}

krsort($date_money_sum);

?>

<table cellpadding="1" cellspacing="1" border="1" width="500" style="width:500px;">
	<tr>
		<th>Date</th>
		<th>Apps</th>
		<th>iAds</th>
		<th>Admob</th>
		<th>Sum</th>
	</tr>
	<tr>
		<th>ALL</th>
		<th align="right"><?=round($total_apps,2);?></th>
		<th align="right"><?=round($total_iad,2);?></th>
		<th align="right"><?=round($total_admob,2);?></th>
		<th align="right"><?=round($sum,2);?></th>
	</tr>
	<?
	$local_apps = 0;
	$local_iad = 0;
	$local_admob = 0;
	$local_sum = 0;
	
	for($i = 0; $i<=$DIFF; $i++) {
		$key = date("Y-m-d",mktime(0,0,0,$m,$d-$i,$y));
		$local_apps += round($date_money['app'][$key],4);
		$local_iad += round($date_money['iAds'][$key],4);
		$local_admob += round($date_money['admob'][$key],4);
		$local_sum += round($date_money_sum[$key],4);
		?>
		<tr>
			<td><?=$key;?></td>
			<td align="right"><?=round($date_money['app'][$key],2);?></td>
			<td align="right"><?=round($date_money['iAds'][$key],2);?></td>
			<td align="right"><?=round($date_money['admob'][$key],2);?></td>
			<td align="right"><?=round($date_money_sum[$key],2);?></td>
		</tr>
		<?
	}
	?>
	<tr>
		<th>Sum</th>
		<td align="right"><?=round($local_apps,2);?></th>
		<td align="right"><?=round($local_iad,2);?></th>
		<td align="right"><?=round($local_admob,2);?></th>
		<td align="right"><?=round($local_sum,2);?></th>
	</tr>
</table>






<?
exit;


$start = 101000;

$cnt_max = 1300;

for ($i=$start;$i<=$start+$cnt_max;$i++) {
	$data = @file_get_contents("http://www.iphones.ru/iNotes/".$i);
	
	if (!$data) continue;
	$data = explode('<body>', $data);
	$data = explode('<div id="content">', $data[1]);
	$data = explode('<div class="commentsblock">', $data[1]);
	$data = explode('<div class="entrymeta"',$data[0]);
	
	$data = str_replace("\r\n", "[br]", $data[0]);
	$data = str_replace("\n", "[br]", $data);
	
	// Определение Загаловка
	preg_match_all('/<h2>(.*?)<\/h2>/', $data, $matches);
	preg_match_all('/<a href="(.*?)">(.*?)<\/a>/',$data, $matches);
	$title = $matches[2][0];
	
	preg_match_all('/<div class="entrybody">(.*)<\/div>/', $data, $matches);
	$body = $matches[1][0];
	$body = explode('<div class="app-rating">', $body);
	$body = trim(str_replace("[br]","\r\n", $body[0]));
	
	if (strpos($body,'attachment') !== false) continue; 
	
	if (strlen($body) < 100) continue;
	
	if (!$title || !$body) continue;
	
	$body .= '<p style="text-align: right;">Материал взят с сайта <a href="http://iphones.ru/">iphones.ru</a></p><p style="text-align: justify;">';
	
	// Найти все картинки
	//preg_match_all('/<img src="http:\/\/www.iphones.ru(.*?)"/', $body, $matches);
	//vardump($matches);
	
	$sql = sql_pholder("
	INSERT INTO `akeb`.`ios5_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`, `post_icon`) VALUES (NULL, '1', ?, '0000-00-00 00:00:00', ?, ?, '', 'draft', 'open', 'open', '', '', '', '', ?, '0000-00-00 00:00:00', '', '0', '', '0', 'post', '', '0', '');\r\n",
	date("Y-m-d H:i:s"),$body,$title,date("Y-m-d H:i:s"));
	echo $sql;
}




exit;
?>

 



//html_parse($data[1]);
//print_r($array);



exit;





function is_email_exists($email){
	$server_prefix = Array("", "mail.", "smtp.");
	$ret = false;
	list($prefix, $domain) = split("@", $email);
	if(function_exists("getmxrr") && getmxrr($domain, $mxhosts)){
		$ret = true;
	} else {
		foreach($server_prefix as $val){
			if(@fsockopen($val.$domain, 25, $errno, $errstr, 3)){
				$ret = true;
				break;
			} elseif(@fsockopen($val.$domain, 2525, $errno, $errstr, 3)){
				$ret = true;
				break;
			}
		}
	}
	return $ret;
}


function validate_email($email){
	$mailparts=explode("@",$email);
	$hostname = $mailparts[1];
	
	// validate email address syntax
	$exp = "^[a-z\'0-9]+([._-][a-z\'0-9]+)*@([a-z0-9]+([._-][a-z0-9]+))+$";
	$b_valid_syntax=eregi($exp, $email);
	
	// get mx addresses by getmxrr
	$b_mx_avail=getmxrr( $hostname, $mx_records, $mx_weight );
	$b_server_found=0;
	
	ECHO " | $email > ".var_export($mx_records,true)." ";
	
	
	
	
	if($b_valid_syntax && $b_mx_avail){
		echo "TEST";
		// copy mx records and weight into array $mxs
		$mxs=array();
		
		for($i=0;$i<count($mx_records);$i++){
			$mxs[$mx_weight[$i]]=$mx_records[$i];
		}
		
		// sort array mxs to get servers with highest prio
		ksort ($mxs, SORT_NUMERIC );
		reset ($mxs);
		
		while (list ($mx_weight, $mx_host) = each ($mxs) ) {
			echo " test1";
			if($b_server_found == 0){
				echo " test2";
				//try connection on port 25
				
				ECHO " |$mx_host| ";
				
				
				$fp = @fsockopen($mx_host,25, $errstr, $errstr, 2);
				if (!$fp) $fp = @fsockopen($mx_host,2525, $errno, $errstr, 2);
				
				
				var_export($errstr);
				var_export($errstr);
				
				if($fp){
					echo " test3";
					$ms_resp="";
					// say HELO to mailserver
					$ms_resp.=send_command($fp, "HELO microsoft.com");
					echo " $ms_resp ";
					// initialize sending mail
					$ms_resp.=send_command($fp, "MAIL FROM:<support@microsoft.com>");
					echo " $ms_resp ";
					// try receipent address, will return 250 when ok..
					$rcpt_text=send_command($fp, "RCPT TO:<".$email.">");
					$ms_resp.=$rcpt_text;
					echo " $rcpt_text ";
					
					if(substr( $rcpt_text, 0, 3) == "250")
						$b_server_found=1;
					
					// quit mail server connection
					$ms_resp.=send_command($fp, "QUIT");
					
					echo " $ms_resp ";
					fclose($fp);
					
				}
				
			}
		}
	}
	return $b_server_found;
}

function send_command($fp, $out){
	
	fwrite($fp, $out . "\r\n");
	return get_data($fp);
}

function get_data($fp){
	$s="";
	stream_set_timeout($fp, 2);
	
	for($i=0;$i<2;$i++)
		$s.=fgets($fp, 1024);
	
	return $s;
}

// support windows platforms
if (!function_exists ('getmxrr') ) {
	function getmxrr($hostname, &$mxhosts, &$mxweight) {
		if (!is_array ($mxhosts) ) {
			$mxhosts = array ();
		}
		
		if (!empty ($hostname) ) {
			$output = "";
			@exec ("nslookup.exe -type=MX $hostname.", $output);
			$imx=-1;
			
			foreach ($output as $line) {
				$imx++;
				$parts = "";
				if (preg_match ("/^$hostname\tMX preference = ([0-9]+), mail exchanger = (.*)$/", $line, $parts) ) {
					$mxweight[$imx] = $parts[1];
					$mxhosts[$imx] = $parts[2];
				}
			}
			return ($imx!=-1);
		}
		return false;
	}
}


$post = file_get_contents('php://input');
error_log("------INPUT-----------");
error_log(var_export($post,true));
$in = array();
$amf->unpack($post,$in);
error_log(var_export($in,true));

$secret = "Fdr!dfdvhhf_34dfbbfhdb4547325gefrg34t_";

$out = array();
$out['status'] = 0;
do {
	if (md5($in['uid'].$secret) != $in['key']) {
		$out['error'] = 'Не правильный ХЕШ';
		break;
	}
	
	$out['id'] = $in['uid'];
	$out['status'] = 100;
} while (0);

//header('Content-Type: text/html; charset=UTF-8');
header("Content-Transfer-Encoding: binary");
$data = $amf->pack($out);
//$data = pack("N",strlen($data)).$data;
print $data;
exit;
?>