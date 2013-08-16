<?
require_once('include/common.inc');
require_once('lib/common.lib');
require_once('lib/message.lib');

common_init();
session_init();

$mode = strval($_REQUEST['mode']);
$action = strval($_REQUEST['action']);
$ref = intval($_REQUEST['ref']);

?>
<html>
	<head>
		<META HTTP-EQUIV=Refresh CONTENT="10; URL=reload.php?r=<?=time();?>">
		<script language="javaScript" src="js/common.js"></script>
	</head>
	<body bgcolor="#000000">
		<?
		switch ($mode) {
			case 'message':
				switch ($action) {
					case 'new':
						common_show_message( );
						break;
					case 'relpay':
						$to_id = intval($_REQUEST['to_id']);
						common_show_message($to_id);
					//break; // TODO: Нужно будет убрать
					case 'read':
						error_log('message read '.$ref);
						$mess = message_get($ref);
						if ($mess) {
							message_save(array(
								'id' => $mess['id'],
								'read' => 1,
							));
							?>
							<script>
							try{
								var main = top.frames['main'];
								var obj;
								if (main) {
									obj = main.gebi('MESSAGE');
									if (obj) {
										obj.innerHTML='';
										top.message=false;
									}
								}
							} catch(e){};
							</script>
							<?
						}
						break;
					
				}
				break;
		}
		
		
		?>
	</body>
</html>