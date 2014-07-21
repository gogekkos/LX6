<?php
error_reporting(E_ERROR | E_PARSE);

$the_server="";
$the_stats_file="/status.xsl";
$is_shoutcast=true;
$streamtitle="";
$streamgenre="";
if (!isset($_GET['translateAllRadioStations'])) {
	$translateAllRadioStations="ALL RADIO STATIONS";
} else {
	$translateAllRadioStations=$_GET['translateAllRadioStations'];
}



if (!isset($_GET['the_stream']) || !isset($_GET['cur_i'])) {
			//nothing
			die();
} else {
	
		$the_link=trim($_GET['the_stream']);
		if (strpos($the_link,'radionomy.com')) { //is a radionomy.com link
			$streamgenre=$translateAllRadioStations;
			preg_match ( '#^http://(.*)/(.*)#' , $the_link,  $matches);
			$streamtitle=$matches[2];
		} else {
					
					preg_match ( '#^http://(.*):(.*)/|;#' , $the_link,  $matches);
					$ip=$matches[1];
					$port=$matches[2];
			
					//echo $ip.'  ---  '.$port.'  ---  ';
		
					//shoutcast start
					$fp = @fsockopen($ip,$port,$errno,$errstr,1);
					if (!$fp) { 
						$streamtitle = "Connection timed out or the server is offline"; // If you always get this error then it means your web host is blocking outward connections. If you ask nicely, they might add your server IP to their allow list.
						//$is_shoutcast=false;
					} else {
						fputs($fp, "GET /index.html HTTP/1.0\r\nUser-Agent: Mozilla\r\n\r\n");
						while (!feof($fp)) {
							$info = fgets($fp);
						}
						$info = str_replace('</body></html>', "", $info);
						$split = explode('Stream Title: </font></td><td><font class=default><b>', $info);
						if (count($split)>=2) { // is shoutcast
							$split = explode('</b></td></tr><tr>', $split[1]);
							$streamtitle = $split[0];
							//the genre
							$split_genre = explode('Stream Genre: </font></td><td><font class=default><b>', $info);
							$split_genre = explode('</b></td></tr>', $split_genre[1]);
							$streamgenre = $split_genre[0];	
						} else {
							$is_shoutcast=false;
						}
					}
		
					//shoutcast end
					
					
					
					//icecast start
					if ($is_shoutcast===false) {
							$the_server='http://'.$ip.':'.$port;
							//get statistics file contents
							$fp = fopen($the_server.$the_stats_file,'r');
							
							if(!$fp) {
							   //error connecting to server!
							   $streamtitle = "Unable to connect to Icecast server.";
							} else {
							
								$stats_file_contents = '';
								
								while(!feof($fp)) {
								   $stats_file_contents .= fread($fp,1024);
								}
								
								fclose($fp);
								
								//create array to store results for later usage
								$radio_info = array();
								$radio_info['server'] = $the_server;
								$radio_info['title'] = '';
								$radio_info['description'] = '';
								$radio_info['content_type'] = '';
								$radio_info['mount_start'] = '';
								$radio_info['bit_rate'] = '';
								$radio_info['listeners'] = '';
								$radio_info['most_listeners'] = '';
								$radio_info['genre'] = '';
								$radio_info['url'] = '';
								$radio_info['now_playing'] = array();
								   $radio_info['now_playing']['artist'] = '';
								   $radio_info['now_playing']['track'] = '';
								
								$temp = array();
								
								//format results into array
								$search_for = "<td\s[^>]*class=\"streamdata\">(.*)<\/td>";
								$search_td = array('<td class="streamdata">','</td>');
								
								if(preg_match_all("/$search_for/siU",$stats_file_contents,$matches)) {
								   foreach($matches[0] as $match) {
									  $to_push = str_replace($search_td,'',$match);
									  $to_push = trim($to_push);
									  array_push($temp,$to_push);
								   }
								}
								
								//build final array from temp array
								$radio_info['title'] = $temp[0];
								$radio_info['description'] = $temp[1];
								$radio_info['content_type'] = $temp[2];
								$radio_info['mount_start'] = $temp[3];
								$radio_info['bit_rate'] = $temp[4];
								$radio_info['listeners'] = $temp[5];
								$radio_info['most_listeners'] = $temp[6];
								$radio_info['genre'] = $temp[7];
								$radio_info['url'] = $temp[8];
								
								$streamtitle=$radio_info['title'];
								$streamgenre=$radio_info['genre'];		
							}
					}
					//icecast end
					
					if (trim($streamtitle)!='') {
						//nothing
					} else {
						$streamtitle='The stream title is currently empty';
					}/**/
					
					if (trim($streamgenre)!='') {
						$streamgenre=str_replace ( "," , ";" , $streamgenre);
					} else {
						//$streamgenre='No genre available';
					}
					
					$streamgenre=$translateAllRadioStations.";".$streamgenre;
					
					//echo $_GET['cur_i'].'#----#'.'http://'.$ip.':'.$port.$the_stats_file.count($split).' ----  '.$is_shoutcast;
		}
			echo $_GET['cur_i'].'#----#'.strip_tags($streamtitle).'#----#'.strip_tags($streamgenre);
}
?>