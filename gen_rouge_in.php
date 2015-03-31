<?php
/* USAGE :
 *
 * php gen_rouge_in.php <output_filename> </path/to/models/dir> </path/to/peers/dir> <peers_extensions> [--one-file-task]
 *
 * exp1: php gen_rouge_in.php config.xml models peers txt
 * exp2: php gen_rouge_in.php config.xml models peers ter1,ter2
 *
 */

$ROUGE_VERSION = "1.5.5";

if(count($argv) < 5) {
	echo "USAGE :\n";
	echo "php gen_rouge_in.php <output_filename> </path/to/models/dir> </path/to/peers/dir> <peers_extensions> [--one-file-by-task]\n\n";
	echo "exp1: php gen_rouge_in.php config.xml models peers txt\n";
	echo "exp2: php gen_rouge_in.php config.xml models peers ter1,ter2\n";
	exit(1);
}

$OUTPUT_FILE = mk_file($argv[1]);

$MODEL_ROOT = realpath($argv[2]);
$PEER_ROOT = realpath($argv[3]);
$PEER_EXTENSIONS = array();
$tmp_peers_ext = explode(',',$argv[4]);
foreach($tmp_peers_ext as $ext) {
	if($ext !== '') {
		$PEER_EXTENSIONS[] = $ext;
	}
}
if(isset($argv[5]) && $argv[5] == '--one-file-by-task') {
	$ONE_FILE_BY_TASK = true;
	unlink($OUTPUT_FILE);
} else {
	$ONE_FILE_BY_TASK = false;
}

$INPUT_FORMAT_TYPE = "SPL";

$models = array();
$peers = array();

$models = sort_models(scandir($MODEL_ROOT));
if(!$ONE_FILE_BY_TASK) {
	$output = fopen($OUTPUT_FILE, 'w');
	fwrite($output,"<ROUGE_EVAL version=\"$ROUGE_VERSION\">\n");
} else {
	$cpt = 0;
}

foreach($models as $m_class => $ms) {
	$find = false;
	foreach($PEER_EXTENSIONS as $p) {
		if(file_exists($PEER_ROOT.'/'.$m_class.'.'.$p)) {
			$find = true;
			break;
		}
	}
	if($find) {
		if($ONE_FILE_BY_TASK) {
			$output = fopen($OUTPUT_FILE.'.'.$cpt, 'w');
			fwrite($output,"<ROUGE_EVAL version=\"$ROUGE_VERSION\">\n");
		}
		$eval_id = to_corpus_name($m_class);
		fwrite($output,"<EVAL ID=\"$eval_id\">\n");
		fwrite($output,"<PEER-ROOT>$PEER_ROOT</PEER-ROOT>\n");
		fwrite($output,"<MODEL-ROOT>$MODEL_ROOT</MODEL-ROOT>\n");
		fwrite($output,"<INPUT-FORMAT TYPE=\"$INPUT_FORMAT_TYPE\"></INPUT-FORMAT>\n");
		fwrite($output,"<PEERS>\n");
		foreach($PEER_EXTENSIONS as $p) {
			if(file_exists($PEER_ROOT.'/'.$m_class.'.'.$p)) {
				fwrite($output,"<P ID=\"$p\">$m_class.$p</P>\n");
			}
		}
		fwrite($output,"</PEERS>\n");
		fwrite($output,"<MODELS>\n");
		foreach($ms as $m_id => $m) {
			fwrite($output,"<M ID=\"$m_id\">$m</M>\n");
		}
		fwrite($output,"</MODELS>\n");
		fwrite($output,"</EVAL>\n");
		if($ONE_FILE_BY_TASK) {
			$cpt++;
			fwrite($output,"</ROUGE_EVAL>\n");
			fclose($output);
		}
	}
}

if(!$ONE_FILE_BY_TASK) {
	fwrite($output,"</ROUGE_EVAL>\n");
	fclose($output);
}
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

// D0801-A.M.100.A => D0801A-A
function to_corpus_name($model_class) {
	$corpus_name = substr($model_class,0,5);
	$corpus_name .= substr($model_class,strlen($model_class)-1,5);
	$corpus_name .= '-';
	$corpus_name .= substr($model_class,6,1);
	return $corpus_name;
}

// return realpath($path)
function mk_file($path) {
	$tmp = realpath($path);
	if($tmp === false) {
		touch($path);
		$tmp = realpath($path);
		if($tmp === false) {
			echo "Creation of $path impossible.\n";
			exit(1);
		}
	}
	return $tmp;
}
?>
