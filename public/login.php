<?php
/**
 * Login
 *
 * Efetua o login de usuário
 *
 * PHP version 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author     Thiago Paes - mrprompt@gmail.com
 * @package    Ponto
 * @subpackage Login
 * @filesource login.php
 * @copyright  Copyright 2011, Thiago Paes
 * @link       http://github.com/mrprompt/Ponto/
 * @version    $Revision: 0.1 $
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 */
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true ");
header("Access-Control-Allow-Methods: OPTIONS, GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Depth, User-Agent, X-File-Size, X-Requested-With, If-Modified-Since, X-File-Name, Cache-Control");
header("Content-type:application/json");

/**
 * @see Usuarios
 */
require_once __DIR__ . '/../src/Usuarios.php';

$retorno = null;

try {
    $objUsuarios = new \Usuarios();

    $retorno = $objUsuarios->setLogin($_POST['usuario'])
                           ->setPassword($_POST['senha'])
                           ->login();
} catch (Exception $e) {
    $retorno = $e->getMessage();
}

echo json_encode($retorno);