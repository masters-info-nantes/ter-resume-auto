# -*- coding: utf-8 -*-
import sume, os, codecs
import os.path

# directory from which text documents to be summarized are loaded. Input
# files are expected to be in one tokenized sentence per line format.
path = "/home/erilien/Fac/TER/ter-2015/"
files = []
os.chdir(path)
for root, dirs, file in os.walk('tac2008'):
    for d in dirs:
        files.append(d)

# create the summarizer
for f in files:
    fo = codecs.open(f+".txt", "wb", "utf-8")
    name = "tac2008/"+f
    s = sume.ilp_models.ConceptBasedILPSummarizer(name)

    # load documents
    s.read_documents()

    # compute the parameters needed by the model
    # extract bigrams as concepts
    s.extract_ngrams()

    # compute document frequency as concept weights
    s.compute_document_frequency()

    # prune sentences that are shorter than 10 words, identical sentences and
    # those that begin and end with a quotation mark
    s.prune_sentences(mininum_sentence_length=10,
                      remove_citations=True,
                      remove_redundancy=True)

    # solve the ilp model
    value, subset = s.solve_ilp_problem()

    # outputs the summary
    fo.write('\n'.join([s.sentences[j].untokenized_form for j in subset]))
    fo.close()
