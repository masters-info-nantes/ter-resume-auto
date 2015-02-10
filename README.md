# ter-resume-auto


## running ROUGE

```
ROUGE-1.5.5.pl -e data -n 4 -m -l 100 -x -c 95 -r 1000 -f 1 -p 0.5 -t 0 -d -a config.in &> rouge.out.txt
```

**Note:** "Cannot open exception db file for reading: data/WordNet-2.0.exc.db"

```
cd data/WordNet-2.0-Exceptions/
rm WordNet-2.0.exc.db # only if exist
./buildExeptionDB.pl . exc WordNet-2.0.exc.db

cd ../
rm WordNet-2.0.exc.db # only if exist
ln -s WordNet-2.0-Exceptions/WordNet-2.0.exc.db WordNet-2.0.exc.db
```

[http://kavita-ganesan.com/rouge-howto](how to ROUGE)
