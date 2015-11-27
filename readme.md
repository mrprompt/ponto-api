Ponto - API
===========

API utilizada na aplicação de [ponto eletrônico](https://github.com/mrprompt/ponto).

Características
---------------

- média de horas cumpridas por dia
- gráfico de horas trabalhadas por dia
- gráfico de horas cumpridas por mês
- sub-usuários
- logar-se como um sub-usuário sem a necessidade de saber a senha
- navegação por mês, podendo selecionar um dia limite para o mês


Requerimentos
-------------

- PHP 5.3.x com módulos: Mcrypt, Filter, SQLite3
- SQLite3

Instalação
----------

Com todos os requisitos obedecidos, somente é necessário dar permissão
de gravação ao sub-diretório php da aplicação, que é onde será gravado
o banco de dados.
É importante que o acesso via web a esta pasta seja proibido por questões
de segurança, para isso, foi criado um arquivo .htaccess dentro do mesmo.
Será necessário fazer essa navegação via outros meios caso você não use o
Servidor Apache ou não possua o módulo Rewrite habilitado.

Iniciando
---------
O usuário e senha padrões para o projeto é 'admin'. É recomendado que 
após o primeiro login, vá na tela de preferências e troque a senha do
mesmo.
Após isso, é possível ir adicionando usuários à aplicação.
