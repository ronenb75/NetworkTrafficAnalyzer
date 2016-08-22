<?

if (isset($_GET['dnslookup'])) {
	echo (gethostbyaddr($_GET['dnslookup']));
}

if (isset($_GET['expandSourceIP'])) {
	$db = new mysqli('localhost', 'root', '', 'network');

	if ($db->connect_errno > 0)
		die("Unable to connect to database [" . $db->connect_error . "]");

	$sql = 'select sip.name as name, INET_NTOA(t.srcIP) as srcIP, traffic/1048576 as traffic, timestampdiff(day, firstSeen, now()) as firstSeen, timestampdiff(day, lastUpdate, now()) as lastUpdate from traffic t left join ips sip on t.srcIP = sip.ip left join ips dip on t.dstIP = dip.ip where traffic != 0 and dstIP = INET_ATON("'.$_GET['expandSourceIP'].'") and traffic/1048576 > 5 order by traffic desc';

	if (!$srcList = $db->query($sql))
		die ('Error: '.$sql.'<br>'.$db->error);

	$list = array();
	$i = 0;
	
	while ($row = $srcList->fetch_assoc()) {
		$list[$i] = array("ip" => $row['srcIP'], "traffic" => $row['traffic'], "firstSeen" => $row['firstSeen'], "lastUpdate" => $row['lastUpdate']);
		if ($row['name'] == NULL)
			$list[$i] += array("name"=>$row['srcIP']);
		  else
		  	$list[$i] += array("name"=>$row['name']);
		$i++;
	}

	$db->close();

	echo (json_encode($list, TRUE));
}

if (isset($_GET['updateName'])) {
	$db = new mysqli('localhost', 'root', '', 'network');

	if ($db->connect_errno > 0)
		die("Unable to connect to database [" . $db->connect_error . "]");

	if ($_GET['updateName'] == NULL)
		$sql = 'delete from ips where ip = INET_ATON("'.$_GET['ip'].'")';
	  else
		$sql = 'insert into ips (ip, name) values (INET_ATON("'.$_GET['ip'].'"), "'.$_GET['updateName'].'") on duplicate key update name = "'.$_GET['updateName'].'"';
	
	if (!$db->query($sql))
		die ('Error: '.$sql.'<br>'.$db->error);

	$db->close();

	if ($_GET['updateName'] == NULL)
		echo ($_GET['ip']);
	  else
	  	echo ($_GET['updateName']);
}

if (isset($_GET['deleteIP'])) {
	$db = new mysqli('localhost', 'root', '', 'network');

	if ($db->connect_errno > 0)
		die("Unable to connect to database [" . $db->connect_error . "]");

	$sql = 'delete from traffic where dstIP = INET_ATON("'.$_GET['deleteIP'].'") or srcIP = INET_ATON("'.$_GET['deleteIP'].'")';
	if (!$db->query($sql))
		die ('Error: '.$sql.'<br>'.$db->error);

	$sql = 'delete from ips where ip = INET_ATON("'.$_GET['deleteIP'].'")';
	if (!$db->query($sql))
		die ('Error: '.$sql.'<br>'.$db->error);

	$db->close();
}

if (isset($_GET['commonlyUsed'])) {
	$db = new mysqli('localhost', 'root', '', 'network');

	if ($db->connect_errno > 0)
		die("Unable to connect to database [" . $db->connect_error . "]");

	$sql = 'select name, count(name) as times from ips group by name having times > 1 order by times desc';

	if (!$srcList = $db->query($sql))
		die ('Error: '.$sql.'<br>'.$db->error);

	$list = array();
	$i = 0;
	
	while ($row = $srcList->fetch_assoc()) {
		$list[$i] = array($row['name']);
		$i++;
	}

	$db->close();

	echo (json_encode($list, TRUE));
}
?>