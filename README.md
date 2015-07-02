# BDD-Videoannotator-PHP
Contains a PHP client to BDD-Videoannotator-Server (see also [bdd_videoannotator](https://github.com/shell88/bdd_videoannotator)) and adapters 
for PHP-based BDD-Frameworks.

# Prerequisites
- PHING    2.11.0*  (set also on PATH)
- Composer 1.0-dev* (set also on PATH)
- PHP 5.6.7*        (set also on PATH)
  - INI-options:
  - php_soap enabled
  - soap.wsdl_cache_enabled=0 (recommended for development)

*Tested Version, others may also work
  
#Building
```sh
phing -f build.xml
````


