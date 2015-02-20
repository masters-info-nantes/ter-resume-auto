<?php
/* USAGE :
 *
 * php brut_force_gold_extractif.php </path/to/corpus/dir> </path/to/models/dir> </path/to/peers/dir>
 *
 * exp: php brut_force_gold_extractif.php tac2008 models peers
 *
 */

$CORPUS_DIR = realpath($argv[1]);
$MODEL_DIR = realpath($argv[2]);
$PEER_DIR = realpath($argv[3]);

$useless_word = array('','\'','``', '\'\'',',','.',':');

if(!file_exists($PEER_DIR)) {
	echo "You must indicate an existing peer directory\n";
	exit(1);
}

$corpus_list = array_values(array_filter(scandir($CORPUS_DIR),'remove_dot_file'));
$model_list = array_values(array_filter(scandir($MODEL_DIR),'remove_dot_file'));

/* init map corpus -> models */
$corpus_model = array();

foreach($corpus_list as $corpus_name) {
	$model_matcher = gen_model_name_matcher($corpus_name);
	$corpus_model[$corpus_name] = array();
	foreach($model_list as $model_name) {
		if($model_matcher == substr($model_name,0,strlen($model_name)-1)) {
			$corpus_model[$corpus_name][] = $model_name;
		}
	}
}

$corpus_sum = array();

foreach($corpus_list as $corpus_dir) {
//~ $corpus_dir = $corpus_list[0];
	// get full corpus document list
	$dir = $CORPUS_DIR.'/'.$corpus_dir;
	$doc_list = scandir($dir);
	// extract all .sentences file
	$doc_list = array_filter($doc_list,'only_dot_sentences');
	// extract all sentences of corpus
	$sentences = array();
	foreach($doc_list as $doc) {
		//~ $sentences = merge_arrays($sentences,file($dir.'/'.$doc));
		merge_arrays($sentences,file($dir.'/'.$doc));
	}
	// indicate word count with each sentences
	foreach($sentences as $key => $s) {
		$sentences[$key] = array(
			'len' => count_word($s),
			'sen' => $s);
	}
	
	// generate easy usable models form
	$models = array();
	foreach($corpus_model[$corpus_dir] as $m) {
		$models[$m] = clean_sum(file_get_contents($MODEL_DIR.'/'.$m));
	}
	
	// generate alls combinaisons of sentences
	// remove sentences more than 100 words
	foreach($sentences as $key => $s) {
		if($s['len'] > 100) {
			unset($sentences[$key]);
		}
	}
	$sentences = array_values($sentences);
	//~ $combinaisons = array();
	$best = combine_sentences_rec($sentences,$models,$combinaisons);
	gen_real_sum($sentences,$best);
	echo "combinaisons found = \n";
	var_dump($best);
}


/** FUNCTIONS **/
function gen_model_name_matcher(&$corpus_name) {
	// D0808B-A => D0808-A.M.100.B.B => D0808-A.M.100.B.
	return substr($corpus_name,0,5) . '-' . substr($corpus_name,7) . '.M.100.' . substr($corpus_name,5,1) . '.';
}

function remove_dot_file(&$file_name) {
	return ('.' !== $file_name) && ('..' !== $file_name);
}

function only_dot_sentences(&$file_name) {
	return '.sentences' === substr($file_name,strlen($file_name)-10);
}

function merge_arrays(&$array1,&$array2) {
	foreach($array2 as $val) {
		$array1[] = $val;
	}
	return $array1;
}

function count_word(&$sentence) {
	global $useless_word;
	return count(
		array_diff(
			explode(
				' ',
				explode("\n",$sentence)[0]
				),
			$useless_word
		)
	);
}

/*
function concat_array(&$array,$separator='.') {
	$ret = $array[0];
	for($i=1;$i<count($array);$i++) {
		$ret .= $separator.$array[$i];
	}
	return $ret;
}*/

function clean_sum(&$str_model) {
	global $useless_word;
	$explode_model = explode(' ',explode("\n",$str_model)[0]);
	$explode_model = array_unique($explode_model);
	$explode_model = array_diff($explode_model,$useless_word);
	return array_values($explode_model);
}

function calc_score_sum(&$clean_sum,&$clean_model) {
	$score = 0;
	foreach($clean_sum as &$word) {
		if(in_array($word,$clean_model)) {
			$score++;
		}
	}
	return $score;
}

function avg_score_sum(&$array_sum_test,&$models) {
	$score = 0;
	foreach($models as &$model) {
		$score += calc_score_sum($array_sum_test,$model);
	}
	$score /= count($models);
	return (int)round($score*100.0);
}

$i = 0;
function combine_sentences_rec(&$sentences, &$models, &$best = array('len'=>0,'sen'=>array()), &$best_score = 0, $sum=array('len'=>0,'sen'=>array())) {
	global $i;
	foreach($sentences as $key => &$s) {
		if(in_array($key,$sum['sen'])){continue;}
		if($sum['len']+$s['len'] <= 100) {
			$newsum = $sum;
			$newsum['len'] += $s['len'];
			$newsum['sen'][] = $key;
			combine_sentences_rec($sentences,$models,$best,$best_score,$newsum);
		}
	}
	$sum_score = avg_score_sum(clean_sum(gen_real_sum($sentences,$sum)['sen']),$models);
	if($sum_score > $best_score) {
		$best = $sum;
		$best_score = $sum_score;
		$i++;
		echo 'found ('.$sum['len'].'/'.count($sum['sen']).' // '.$sum_score.") $i\n";
	}
	return $best;
}

function gen_real_sum(&$sentences,&$sum) {
	$newsum['len'] = $sum['len'];
	$newsum['sen'] = '';
	foreach($sum['sen'] as $sentence_key) {
		$newsum['sen'] .= ' '.$sentences[$sentence_key]['sen'];
	}
	return $newsum;
}

function gen_real_sums(&$sentence,&$sums) {
	foreach($sums as $key => $sum) {
		$sums[$key] = gen_real_sum($sentence,$sum);
	}
}
?>
