#!/bin/sh

ROUGE=./rouge-1.5.5/ROUGE-1.5.5.pl
ROUGE_DATA=./rouge-1.5.5/data

if [ $# -lt 2 ]; then
	echo "USAGE: ./rouge-multi.sh <config.template.> <rouge.ref.txt>"
	echo "EXP: ./rouge-multi.sh config.gold.rouge.xml. rouge.sume.out.txt"
	exit 1
fi

motif=$1
motif_len=${#motif}
list=$(ls $motif*)
rougeref=$2

## STEP 1 : generate rouge score for all config file

#~ for f in $list
#~ do
	#~ echo ${f:$motif_len}
	#~ $ROUGE -e $ROUGE_DATA -n 4 -m -l 100 -x -c 95 -r 1000 -f 1 -p 0.5 -t 0 -d -a $f > rouge.out.txt.${f:$motif_len} 2> rouge.err.txt.${f:$motif_len}
#~ done

## STEP 2 : generate top 5 list for all rouge result

echo -e "=====   TOP 5   =====\n\n" > rouge.out.txt

for f in $list
do
	echo $f ' => ' rouge.out.txt.${f:$motif_len} >> rouge.out.txt
	echo "" >> rouge.out.txt
	for i in $(seq 1 4)
	do
		cat rouge.out.txt.${f:$motif_len} | grep "ROUGE-$i Eval" | sed 's/:/ /g' | awk '{print $6, $4, $2}' | sort -nr | head -n 5 | awk '{print $3,$2,$1}' >> rouge.out.txt
		echo "" >> rouge.out.txt
	done
	echo "" >> rouge.out.txt
done

## STEP 3 : generate top 5 csv format file all rouge result

echo "corpus,reference,score 1,score 2,score 3,score 4,score 5" > rouge.out.1.csv
echo "corpus,reference,score 1,score 2,score 3,score 4,score 5" > rouge.out.2.csv
echo "corpus,reference,score 1,score 2,score 3,score 4,score 5" > rouge.out.3.csv
echo "corpus,reference,score 1,score 2,score 3,score 4,score 5" > rouge.out.4.csv

for f in $list
do
	corpus=${f:$motif_len}
	for i in $(seq 1 4)
	do
		line=$(echo $corpus)
		refscore=$(cat rouge.out.txt.${f:$motif_len} | grep "Eval" | head -n 1 | awk '{print $4}')
		refscore=$(cat $rougeref | grep ${refscore:0:5} | grep "ROUGE-$i" | sed 's/:/ /' | awk '{print $6}')
		line=$(echo $line,$refscore)
		scores=$(cat rouge.out.txt.${f:$motif_len} | grep "ROUGE-$i Eval" | sed 's/:/ /g' | awk '{print $6, $4, $2}' | sort -nr | head -n 5 | awk '{print $1}')
		for score in $scores
		do
			line=$(echo $line,$score)
		done
		echo $line >> rouge.out.$i.csv
	done
done


