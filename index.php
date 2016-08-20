<?
$db = new mysqli('localhost', 'root', '', 'network');

if ($db->connect_errno > 0) {
	die("Unable to connect to database [" . $db->connect_error . "]");
}


?>

<html>
<head>
	<title> Network Traffic Analyser </title>
	<link href="style.css" rel="stylesheet" type="text/css">
	<link rel="shortcut icon" HREF="./icon.png">

	<script>
		var xmlhttp = new Array();
		var elementID = new Array();

		function dnsLookup (elementID, ipToLookup) {
			index = xmlhttp.length;
			xmlhttp[index] = (new XMLHttpRequest());
			xmlhttp[index].cellID = elementID;
			xmlhttp[index].onreadystatechange=function() {
				if (this.readyState==4 && this.status==200) {
					document.getElementById(this.cellID).innerHTML = ' (' + this.responseText + ')';
				}
			}
			elementID[index] = ipToLookup;
			xmlhttp[index].open("GET","ajax.php?dnslookup=" + ipToLookup, true);
			xmlhttp[index].send();
		}

		function expandDetails(dstIP) {
			var td = document.getElementById(dstIP + '_dst'), rowLocation = td.parentNode.rowIndex + 1;

			originalInnerHTML = td.innerHTML;
			td.innerHTML = '<img src=./loading.gif width="30" height="30"/>';

			xmlhttp = (new XMLHttpRequest());
			xmlhttp.onreadystatechange=function() {
				if (this.readyState==4 && this.status==200) {
					var tableToExpand = document.getElementById("networkDetails");

					var list = JSON.parse(this.response);

    				for(i = 0; i < list.length; i++) {
						var row = tableToExpand.insertRow(rowLocation + i);
						var name = row.insertCell(0);
						var traffic = row.insertCell(1);
						var firstSeen = row.insertCell(2);
						var lastUpdate = row.insertCell(3);

						var uniqid = Date.now();
						var dnsLookupResult = dnsLookup(uniqid + '_resolve', list[i].ip);

						name.innerHTML = '<span id="' + uniqid + '" OnClick="PopupEditName(\'' + list[i].ip + '\', \'' + list[i].name +'\')">' + list[i].name + '</span><span id="' + uniqid + '_resolve" OnClick="window.open(\'whois.php?ip=' + list[i].ip + '\')">' + dnsLookupResult + '</span>';
						name.innerHTML = name.innerHTML + '<div class="inlineIcons"><span OnDblClick="deleteIP(\''+list[i].ip+'\')"><img src="./delete.png" /></span></div>';
						name.className = "expanded leftAlign";
						if (list[i].ip == list[i].name) {
							name.style.color = 'red';
						}
						traffic.innerHTML = list[i].traffic + " Mb"; traffic.className = "expanded centerAlign";
						firstSeen.innerHTML = list[i].firstSeen; firstSeen.className = "expanded centerAlign";
						lastUpdate.innerHTML = list[i].lastUpdate; lastUpdate.className = "expanded centerAlign";
					}
				td.innerHTML = originalInnerHTML;
				td.parentNode.className = "bold";
				document.getElementById(dstIP + '_expand').style.display = 'none';
				}
			}
			xmlhttp.open("GET","ajax.php?expandSourceIP=" + dstIP, true);
			xmlhttp.send();
		}

		function PopupEditName (ip, name) {
			document.getElementById('editPopupDiv').style.display = "block";

			document.getElementById('editPopupInputIP').innerHTML = ip;
			if (ip != name) {
				document.getElementById('editPopupInputName').value = name;
			  }
			  else {
			  	document.getElementById('editPopupInputName').value = '';	
			  }

			  document.getElementById('editPopupInputName').focus();
		}

		function updateNameInDB () {
			var ip = document.getElementById('editPopupInputIP').innerHTML;
			var name = document.getElementById('editPopupInputName').value;

			xmlhttp = (new XMLHttpRequest());
			xmlhttp.open("GET","ajax.php?updateName=" + name + "&ip=" + ip, true);
			xmlhttp.send();

			document.getElementById('editPopupDiv').style.display = "none";
		}

		function deleteIP (ip) {
			var confirm = prompt('Are you sure you want to delete all records with the IP ' + ip + '?\n\nType "YES"');

			if (confirm == "YES") {
				xmlhttp = (new XMLHttpRequest());
				xmlhttp.open("GET","ajax.php?deleteIP=" + ip, true);
				xmlhttp.send();
			}
		}
		</script>

</head>
<body>

<!--                       -->
<!-- The Edit popup window -->
<!--                       -->

<div id="editPopupDiv" class="editPopup">
	<div class="editPopupContent">
		<span id="editPopupClose" class="editPopupClose" OnClick="document.getElementById('editPopupDiv').style.display = 'none'">×</span>
		<p>Assign name to <span id="editPopupInputIP"></span>: <input type="text" id="editPopupInputName" size="30" OnBlur="updateNameInDB()" OnKeyDown="if (event.keyCode == 13) updateNameInDB()" /> </p>
	</div>
</div>

<script>
	window.onclick = function(event) {
		if (event.target == document.getElementById('editPopupDiv')) {
			document.getElementById('editPopupDiv').style.display = "none";
		}
	}
	window.onkeydown = function(event) {
		if (event.keyCode == 27) {
			document.getElementById('editPopupDiv').style.display = "none";	
		}
	}
</script>


<?
$sql = 'select ips.name as name, INET_NTOA(dstIP) as dstIP, sum(traffic)/1048576 as totalTraffic from traffic left join ips on traffic.dstIP = ips.ip group by dstIP having totalTraffic > 5 order by totalTraffic desc';

if (!$traffic = $db->query($sql)) {
	die ('Error: '.$sql.'<br>'.$db->error);
}
?>
	<table id="networkDetails" border="1" class="Table" style="width: 60%">
		<tr>
			<th style="width: 75%";>Destination</th>
			<th>Traffic</th>
			<th style="width: 7%";>First Seen<br />(days)</th>
			<th style="width: 7%";>Last update<br />(days)</th>
		</tr>
<?
			while ($row = $traffic->fetch_assoc()) { ?>
			<tr>
				<td id="<?echo ($row['dstIP'])?>_dst" class="leftAlign">
					<?if ($row['name'] == NULL) $row['name'] = $row['dstIP']; ?>
					<span id="<?echo($row['dstIP'])?>_name" OnClick="PopupEditName('<?echo($row['dstIP'])?>', '<?echo($row['name'])?>')">
						<?echo($row['name'])?>
					</span>
					<span id="<?echo($row['dstIP'])?>_resolve" OnClick="window.open('whois.php?ip=<?echo($row['dstIP'])?>')">
						<script>dnsLookup("<?echo($row['dstIP'])?>_resolve", "<?echo($row['dstIP'])?>")</script>
					</span>
					<div class="inlineIcons">
						<span OnDblClick="deleteIP('<?echo($row['dstIP'])?>')">
							<img src="./delete.png" />
						</span>
						<span id="<?echo($row['dstIP'])?>_expand" OnClick="expandDetails('<?echo($row['dstIP'])?>')">
							<img src="./expand.png" />
						</span>
					</div>
				</td>
				<td class="centerAlign"><?echo ((number_format($row['totalTraffic'],2,'.',',')))?> Mb</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
<?			}
		$sql = 'select sum(traffic)/1048576 as totalTraffic from traffic';
		if (!$totalTraffic = $db->query($sql)) {
			die ('Error: '.$sql.'<br>'.$db->error);
		}
		$row = $totalTraffic->fetch_assoc()
?>
		<tr>
			<td>Total</td>
			<td colspan="3"><?echo ((number_format($row['totalTraffic'],2,'.',',')))?> Mb</td>
	</table>
<?
$db->close();

exec('pgrep -f "python /usr/local/bin/sniffer.py"', $pids);
if(!empty($pids))
	echo ('Sniffer is <span style="color: green;">RUNNING</span><br />');
  else
  	echo ('Sniffer is <span style="color: red;">NOT RUNNING</span><br />');

exec('pgrep tcpdump', $pids);
if(!empty($pids))
	echo ('tcpdump is <span style="color: green;">RUNNING</span><br />');
  else
  	echo ('tcpdump is <span style="color: red;">NOT RUNNING</span><br />');
?>