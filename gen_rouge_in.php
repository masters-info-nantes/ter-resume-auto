<?php
/* USAGE :
 *
 * php gen_rouge_in.php </path/to/models/dir> </path/to/peers/dir> [<peers_extensions>]
 *
 * exp1: php gen_rouge_in.php models peers txt >> config.xml
 * exp2: php gen_rouge_in.php models peers ter1,ter2 >> config.xml
 *
 */

$ROUGE_VERSION = "1.5.5";

if(count($argv) < 3) {
	echo "USAGE :\n";
	echo "php gen_rouge_in.php </path/to/models/dir> </path/to/peers/dir> [<peers_extensions>]\n\n";
	echo "exp1: php gen_rouge_in.php models peers txt >> config.xml\n";
	echo "exp2: php gen_rouge_in.php models peers ter1,ter2 >> config.xml\n";
	exit(1);
}

$MODEL_ROOT = realpath($argv[1]);
$PEER_ROOT = realpath($argv[2]);
if(isset($argv[3])) {
	$PEER_EXTENSIONS = array();
	$tmp_peers_ext = explode(',',$argv[3]);
	foreach($tmp_peers_ext as $ext) {
		if($ext !== '') {
			$PEER_EXTENSIONS[] = $ext;
		}
	}
} else {
	$PEER_EXTENSIONS = array('ter');
}
$INPUT_FORMAT_TYPE = "SPL";

$models = array();
$peers = array();

$models = sort_models(scandir($MODEL_ROOT));

echo "<ROUGE_EVAL version=\"$ROUGE_VERSION\">\n";

foreach($models as $m_class => $ms) {
	$find = false;
	foreach($PEER_EXTENSIONS as $p) {
		if(file_exists($PEER_ROOT.'/'.$m_class.'.'.$p)) {
			$find = true;
			break;
		}
	}
	if($find) {
		echo "<EVAL ID=\"$m_class\">\n";
		echo "<PEER-ROOT>$PEER_ROOT</PEER-ROOT>\n";
		echo "<MODEL-ROOT>$MODEL_ROOT</MODEL-ROOT>\n";
		echo "<INPUT-FORMAT TYPE=\"$INPUT_FORMAT_TYPE\"></INPUT-FORMAT>\n";
		echo "<PEERS>\n";
		foreach($PEER_EXTENSIONS as $p) {
			if(file_exists($PEER_ROOT.'/'.$m_class.'.'.$p)) {
				echo "<P ID=\"$p\">$m_class.$p</P>\n";
			}
		}
		echo "</PEERS>\n";
		echo "<MODELS>\n";
		foreach($ms as $m_id => $m) {
			echo "<M ID=\"$m_id\">$m</M>\n";
		}
		echo "</MODELS>\n";
		echo "</EVAL>\n";
	}
}

echo "</ROUGE_EVAL>\n";
// /home/anthony/Dropbox/Cours/TER/ter-2015/peers/D0848-A.M.100.H.txt.1
/* *** FUNCTIONS *** */

function sort_models($in_models) {
	$out_models = array();
	foreach($in_models as $m) {
		if(!($m == '.' || $m == '..')) {
			$class = substr($m,0,strlen($m)-2);
			$id = substr($m,strlen($m)-1);
			if(!isset($out_models[$class]))
				$out_models[$class] = array();
			$out_models[$class][$id] = $m;
		}
	}
	return $out_models;
}
?>
