#!/bin/sh

ROUGE=./rouge-1.5.5/ROUGE-1.5.5.pl
ROUGE_DATA=./rouge-1.5.5/data

if [ $# -lt 2 ]; then
	echo "USAGE: ./rouge-multi.sh <config.template.> <rouge.ref.txt>"
	exit 1
fi

motif=$1
motif_len=${#motif}
list=$(ls $motif*)
rougeref=$2

## STEP 1 : generate rouge score for all config file

for f in $list
do
	echo ${f:$motif_len}
	$ROUGE -e $ROUGE_DATA -n 4 -m -l 100 -x -c 95 -r 1000 -f 1 -p 0.5 -t 0 -d -a $f > rouge.out.txt.${f:$motif_len} 2> rouge.err.txt.${f:$motif_len}
done


## STEP 2 : generate average and difference with reference

#~ echo "===== SUMMARY =====\n\n" > rouge.out.txt
#~ 
#~ avgref=0.0
#~ nbref=0.0
#~ for s in $(cat ${rougeref} | grep "ROUGE-2 Average_R" | awk '{print $4}')
#~ do
	#~ nbref=$(echo "$nbref + 1.0" | bc)
	#~ echo "$avgref + $s"
	#~ avgref=$(echo "$avgref + $s" | bc)
#~ done
#~ avgref=$(echo "$avgref / $nbref" | bc)

#~ echo ${avgref} ${nbref} >> rouge.out.txt

#~ for f in $list
#~ do
	#~ avg=0
	#~ nb=0
	#~ 
#~ done

#~ echo -e "\n\n" >> rouge.out.txt

## STEP 3 : generate top 5 list for all rouge result

echo -e "=====   TOP 5   =====\n\n" >> rouge.out.txt

for f in $list
do
	echo $f ' => ' rouge.out.txt.${f:$motif_len} >> rouge.out.txt
	echo "" >> rouge.out.txt
	for i in $(seq 1 4)
	do
		echo "ROUGE-$i" >> rouge.out.txt
		cat rouge.out.txt.${f:$motif_len} | grep "ROUGE-$i Eval" | sed 's/:/ /g' | awk '{print $6, $4}' | sort -nr | head -n 5 | awk '{print $2,$1}' >> rouge.out.txt
		echo "" >> rouge.out.txt
	done
	echo "" >> rouge.out.txt
done


