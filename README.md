# horde/Text_Wiki - A Modernized Fork of pear/Text_Wiki and related modules

This library parses various styles of Wiki markup and renders them to 
 - HTML
 - plain text
 - (la)tex
 - Markdown (GFM and CommonMark)
 - other viewing/printing formats
 - other Wiki formats

Its prime usage in Horde is driving the Wicked wiki application.
It can also be used to read and convert legacy Wiki formats into Markdown or Mediawiki (Wikipedia) format.

## Heritage and conversion approach

This is a spinoff of pear/text_wiki and its subclasses 
- pear/text_wiki_tiki
- pear/text_wiki_mediawiki
- pear/text_wiki_creole
- pear/text_wiki_doku
- pear/text_wiki_docbook
- pear/text_wiki_cowiki
- pear/text_wiki_bbcode

The master branch contains Jan Schneider's original preservation-oriented fork from mid 2010s.
FRAMEWORK_6_0 tries to port to namespaces, composition/injectio, PSR-4 and PHP 8 native equivalents of pearisms and artifacts of PHP 4 era heritage

- PEAR_Exception is substituted by PHP 8's native exception tree.
- Integration with Horde's own exception tree or helper class ecosystem is avoided.

