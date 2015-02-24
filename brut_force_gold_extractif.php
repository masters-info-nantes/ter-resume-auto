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

foreach($corpus_list as $corpus_dir) {// comment for only one corpus test
//~ $corpus_dir = $corpus_list[0];// uncomment for only one corpus test
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
	$best = combine_sentences_rec($sentences,$models);
	var_dump($best);
	$real_best = gen_real_sum($sentences,$best);
	var_dump($real_best);
	echo "combinaisons found = \n";
}// comment for only one corpus test


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

function concat_array(&$array,$separator='.') {
	if(isset($array[0])) {
		$ret = $array[0];
		for($i=1;$i<count($array);$i++) {
			$ret .= $separator.$array[$i];
		}
		return $ret;
	} else {
		return '';
	}
}

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
	return $score;
}

function firstBetterSum($sum1,$sum2) {
	$ret = ( $sum1['score'] > $sum2['score']
		|| ($sum1['score'] == $sum2['score'] && $sum1['len'] < $sum2['len'] && count($sum1['sen']) > count($sum2['sen']))
	);
	$concat_key1 = concat_array($sum1['sen']);
	$concat_key2 = concat_array($sum2['sen']);
	return $ret;
}

$i = 0;
$j = 0;
function combine_sentences_rec(&$sentences, &$models, $best = array('len'=>0,'sen'=>array(),'score' => 0), $sum=array('len'=>0,'sen'=>array())) {
	global $i,$j;
	$maxsize = true;
	foreach($sentences as $key => $s) {
		if(in_array($key,$sum['sen'])){continue;}
		if($sum['len']+$s['len'] <= 100) {
			$maxsize = false;
			$newsum = $sum;
			$newsum['len'] += $s['len'];
			$newsum['sen'][] = $key;
			if(count($sum['sen']) < 6) {
				$sub_best = combine_sentences_rec($sentences,$models,$best,$newsum);
				if(firstBetterSum($sub_best,$best)) {
					$best['len'] = $sub_best['len'];
					$best['sen'] = $sub_best['sen'];
					$best['score'] = $sub_best['score'];
				}
			}
		}
	}
	if($maxsize) {
		$sum_score = avg_score_sum(clean_sum(gen_real_sum($sentences,$sum)['sen']),$models);
		$j++;
		
		$sum['score'] = $sum_score;
		if(firstBetterSum($sum,$best)) {
			$i++;
			$best['len'] = $sum['len'];
			$best['sen'] = $sum['sen'];
			$best['score'] = $sum_score;
			$concat_key = concat_array($best['sen']);
			echo "\t\t\t\t\t\t\t\t\t\tfound (".$best['len'].'/'.count($best['sen']).' // '.$best['score'].") [$i/$j] $concat_key\n";
		} else {
			$concat_key = concat_array($sum['sen']);
			echo 'erase ('.$sum['len'].'/'.count($sum['sen']).' // '.$sum_score.") [$i/$j] $concat_key\n";
		}
	}
	return $best;
}

function gen_real_sum(&$sentences,&$sum) {
	$newsum = $sum;
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
