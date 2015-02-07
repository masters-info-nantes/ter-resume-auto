# -*- coding: utf-8 -*-
import sume, os, codecs
import os.path
import sys

def to_peers_name(corpus_name,extension='txt'):
	part1 = corpus_name[:5]
	part2 = corpus_name[5:6]
	part3 = corpus_name[7:]
	return part1+'-'+part3+'.M.100.'+part2+'.'+extension

# directory from which text documents to be summarized are loaded. Input
# files are expected to be in one tokenized sentence per line format.
corpus_path = "."
corpus_dir_name = "tac2008"
dest_path = "."
dest_dir_name = "peers"
files = []
os.chdir(corpus_path)
for root, dirs, file in os.walk(corpus_dir_name):
    for d in dirs:
        files.append(d)
files_number = len(files)
i = 1

# create the summarizer
for f in files:
    fo_name = to_peers_name(corpus_name=f,extension='txt')
    fo = codecs.open(dest_path+'/'+dest_dir_name+'/'+fo_name, "wb", "utf-8")
    name = corpus_dir_name+"/"+f
    s = sume.ilp_models.ConceptBasedILPSummarizer(name)
    sys.stdout.write(f+' pending')
    # load documents
    s.read_documents()
    # compute the parameters needed by the model
    # extract bigrams as concepts
    s.extract_ngrams()
    # compute document frequency as concept weights
    s.compute_document_frequency()
    # prune sentences that are shorter than 10 words, identical sentences and
    # those that begin and end with a quotation mark
    s.prune_sentences(mininum_sentence_length=10,remove_citations=True,remove_redundancy=True)
    # solve the ilp model
    value, subset = s.solve_ilp_problem(solver='glpk')
    # outputs the summary
    fo.write('\n'.join([s.sentences[j].untokenized_form for j in subset]))
    fo.close()
    sys.stdout.write('\r'+f+' => '+fo_name+' done\n')
