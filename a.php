<?

include_once('include/common.inc');
include_once('include/benc.php');
include_once('lib/common.lib');

$announce_interval = 15*60;
$timeout = 1*60*60;

define('TABLE_PEERS','peers');
define('FIELD_PEERS','');

function peers_get($ref=false, $add='') {
	global $db;
	return common_get($db,TABLE_PEERS,$ref,$add);
}

function peers_list($ref=false, $add='', $field_list='*') {
	global $db;
	return common_list($db,TABLE_PEERS,$ref,$add,$field_list);
}

function peers_count($ref=false,$add='') {
	global $db;
	return common_count($db,TABLE_PEERS,$ref,$add);
}

function peers_save($param) {
	global $db;
	return common_save($db,TABLE_PEERS,$param,FIELD_PEERS);
}

function peers_delete($ref) {
	global $db;
	return common_delete($db,TABLE_PEERS,$ref);
}


@error_reporting(E_ALL & ~E_NOTICE);
@ini_set('error_reporting', E_ALL & ~E_NOTICE);
@ini_set('display_errors', '1');
@ini_set('display_startup_errors', '0');
@ini_set('ignore_repeated_errors', '1');
@ignore_user_abort(0);
@set_time_limit(30);
@set_magic_quotes_runtime(0);

function gzip() {
	if (@extension_loaded('zlib') && @ini_get('zlib.output_compression') != '1' && @ini_get('output_handler') != 'ob_gzhandler') {
		@ob_start('ob_gzhandler');
	}
}

function err($msg) {
	benc_resp(array("failure reason" => array(type => "string", value => $msg)));
	exit();
}

function benc_resp($d) {
	benc_resp_raw(benc(array(type => "dictionary", value => $d)));
}

function benc_resp_raw($x) {
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header("Content-Type: text/plain");
	print($x);
	//error_log('RESP ['.$x.']');
}

function getip() {
	return (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
}

gzip();

foreach (array('info_hash','peer_id','event','ip','localip') as $x) {
	if(isset($_GET[$x]))
		$GLOBALS[$x] = '' . $_GET[$x];
}

foreach (array('port','downloaded','uploaded','left') as $x)
	$GLOBALS[$x] = 0 + $_GET[$x];

if (get_magic_quotes_gpc()) {
    $info_hash = stripslashes($info_hash);
    $peer_id = stripslashes($peer_id);
}

foreach (array('info_hash','peer_id','port','downloaded','uploaded','left') as $x) {
	if (!isset($x)) err('Missing key: '.$x);
	foreach (array('info_hash','peer_id') as $x) {
		if (strlen($GLOBALS[$x]) != 20) err('Invalid '.$x.' (' . strlen($GLOBALS[$x]) . ' - ' . urlencode($GLOBALS[$x]) . ')');
	}
}

$ip = getip();
$rsize = 50;

foreach(array('num want', 'numwant', 'num_want') as $k) {
	if (isset($_GET[$k])) {
		$rsize = (int) $_GET[$k];
		break;
	}
}

$agent = $_SERVER['HTTP_USER_AGENT'];

if (!$port || $port > 0xffff)
	err("Invalid port");
if (!isset($event)) $event = '';
$seeder = (intval($left) == 0) ? 1 : 0;

if (function_exists('getallheaders')) $headers = getallheaders();
else $headers = emu_getallheaders();


if(substr($peer_id, 0, 6) == "exbc\08") err("BitComet 0.56 is Banned, Upgrade.");
if(substr($peer_id, 0, 4) == "FUTB") err("FUTB? Fuck You Too."); //patched version of BitComet 0.57 (FUTB- Fuck U TorrentBits)
if(substr($peer_id, 1, 2) == 'BC' && substr($peer_id, 5, 2) != 70 && substr($peer_id, 5, 2) != 63 && substr($peer_id, 5, 2) != 77 && substr($peer_id, 5, 2) >= 59/* && substr($peer_id, 5, 2) <= 88*/) err("BitComet ".substr($peer_id, 5, 2)." is banned. Use only 0.70 or switch to uTorrent 1.6.1.");
if(substr($peer_id, 1, 2) == 'UT' && substr($peer_id, 3, 3) >= 170 && substr($peer_id, 3, 3) <= 174) err("uTorrent ".substr($peer_id, 3, 3)." is banned. Downgrade to 1.6.1 or use 1.7.5 or higher.");
if(substr($peer_id, 0, 4) == "FUTB") err("FUTB? Fuck You Too.");
if(substr($peer_id, 0, 3) == "-TS") err("TorrentStorm is Banned.");
if(substr($peer_id, 0, 5) == "Mbrst") err("Burst! is Banned.");
if(substr($peer_id, 0, 3) == "-BB") err("BitBuddy is Banned.");
if(substr($peer_id, 0, 3) == "-SZ") err("Shareaza is Banned.");
if(substr($peer_id, 0, 5) == "turbo") err("TurboBT is banned.");
if(substr($peer_id, 0, 4) == "T03A") err("Please Update your BitTornado.");
if(substr($peer_id, 0, 4) == "T03B") err("Please Update your BitTornado.");
if(substr($peer_id, 0, 3 ) == "FRS") err("Rufus is Banned.");
if(substr($peer_id, 0, 2 ) == "eX") err("eXeem is Banned.");
if(substr($peer_id, 0, 8 ) == "-TR0005-") err("Transmission/0.5 is Banned.");
if(substr($peer_id, 0, 8 ) == "-TR0006-") err("Transmission/0.6 is Banned.");
if(substr($peer_id, 0, 8 ) == "-XX0025-") err("Transmission/0.6 is Banned.");
if(substr($peer_id, 0, 1 ) == ",") err ("RAZA is banned.");
if(substr($peer_id, 0, 3 ) == "-AG") err("This is a banned client. We recommend uTorrent or Azureus.");
if(substr($peer_id, 0, 3 ) == "R34") err("BTuga/Revolution-3.4 is not an acceptalbe client. Please read the FAQ on recommended clients.");
if(substr($peer_id, 0, 4) == "exbc") err("This version of BitComet is banned! You can thank DHT for this ban!");
if(substr($peer_id, 0, 3) == '-FG') err("FlashGet is banned!");

$hash = bin2hex($info_hash);

$peer = false;

$peer_id = base64_encode($peer_id);
$peer = peers_get(array('hash' => $hash,'peer_id' => $peer_id));
if (!$peer) {
	$peer = array(
		'hash' => $hash,
		'peer_id' => $peer_id,
		'ip' => $ip,
		'port' => $port,
		'seeder' => $seeder,
		'agent' => $agent,
		'started' => time(),
		'last_active' => time(),
	);
	$peer['id'] = peers_save($peer);
} else {
	$peer['ip'] = $ip;
	$peer['port'] = $port;
	$peer['seeder'] = $seeder;
	$peer['agent'] = $agent;
	$peer['last_active'] = time();
	$params = array(
		'id' => $peer['id'],
		'port' => $peer['port'],
		'seeder' => $peer['seeder'],
		'agent' => $peer['agent'],
		'last_active' => $peer['last_active'],
		'_mode' => CSMODE_UPDATE,
	);
	
	peers_save($params);
}
if (!$peer['id']) err("Error with DB!!!");
$resp = '';
$resp .= 'd' . benc_str('interval') . 'i' . $announce_interval;
$resp .= 'e' . benc_str('peers') . (($compact = ($_GET['compact'] == 1)) ? '' : 'l');
$no_peer_id = ($_GET['no_peer_id'] == 1);

$data = peers_list(array('hash' => $hash),sql_pholder(' AND peer_id != ? AND last_active >= ? ORDER BY RAND() LIMIT '.$rsize,$peer_id,time()-$timeout));
foreach ($data as $item) {
	if($compact) {
		$peer_ip = explode('.', $item["ip"]);
		$plist .= pack("C*", $peer_ip[0], $peer_ip[1], $peer_ip[2], $peer_ip[3]). pack("n*", (int) $item["port"]);
	} else {
		$resp .= 'd' .
			benc_str('ip') . benc_str($item['ip']) .
			(!$no_peer_id ? benc_str("peer id") . benc_str(base64_decode($item["peer_id"])) : '') .
			benc_str('port') . 'i' . $item['port'] . 'e' . 'e';
	}
}
$resp .= ($compact ? benc_str($plist) : '') . (substr($peer_id, 0, 4) == '-BC0' ? "e7:privatei1ee" : "ee");


benc_resp_raw($resp);

?>