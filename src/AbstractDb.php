<?php
/**
 * AbstractDbException
 *
 * Excessões
 *
 * PHP version 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author     Thiago Paes - mrprompt@gmail.com
 * @package    Ponto
 * @subpackage AbstractDbException
 * @filesource AbstractDb.php
 * @copyright  Copyright 2011, Thiago Paes
 * @link       http://github.com/mrprompt/Ponto/
 * @version    $Revision: 0.1 $
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class AbstractDbException extends \PDOException {

}

/**
 * AbstractDb
 *
 * Conexão com o banco de dados
 *
 * PHP version 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author     Thiago Paes - mrprompt@gmail.com
 * @package    Ponto
 * @subpackage AbstractDb
 * @filesource AbstractDb.php
 * @copyright  Copyright 2011, Thiago Paes
 * @link       http://github.com/mrprompt/Ponto/
 * @version    $Revision: 0.1 $
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class AbstractDb extends \PDO
{
    /**
     * Database Connection
     *
     * @var SQLite3 object
     */
    protected $_instance;

    /**
     * Caminho do banco de dados
     *
     * @var string
     */
    protected $_dbFile;

    /**
     * Construtor
     *
     * Seta o banco de dados e faz as verificações iniciais
     *
     * @throws \AbstractDbException
     */
    public function __construct()
    {
        if (!class_exists('SQLite3')) {
            throw new \AbstractDbException('Módulo SQLite3 não encontrado.');
        }

        if (!class_exists('DateTime')) {
            throw new \AbstractDbException('Módulo DateTime não encontrado.');
        }

        $phpversion = preg_replace('/[^[:digit:]]/', '', PHP_VERSION);
        $intversion = (integer) substr($phpversion, 0, 2);

        if ($intversion < 53) {
            throw new \AbstractDbException('PHP 5.3.x necessário.');
        }

        if (!function_exists('filter_var')) {
            throw new \AbstractDbException('Módulo Filter necessário.');
        }

        if (!function_exists('mcrypt_encrypt')) {
            throw new \AbstractDbException('Módulo Mcrypt necessário.');
        }

        if (!is_writable(__DIR__)) {
            $msg = 'Diretório do banco (' . __DIR__ . ') '
                 . 'sem permissões de escrita.';

            throw new \AbstractDbException($msg);
        }

        $this->_dbFile = __DIR__ . '/../data/ponto.db';

        if (!is_writable($this->_dbFile)) {
            $msg = 'Banco sem permissão de escrita.';

            throw new \AbstractDbException($msg);
        }

        if (!$this->_instance instanceof \SQLite3) {
            try {
                $this->_instance = new SQLite3($this->_dbFile);

                $query = "CREATE TABLE IF NOT EXISTS usuarios ("
                       . "id INTEGER PRIMARY KEY AUTOINCREMENT, "
                       . "owner INTEGER NULL DEFAULT 1, "
                       . "login CHAR(32) NOT NULL UNIQUE, "
                       . "password CHAR(120) NOT NULL"
                       . ");"
                       . "CREATE TABLE IF NOT EXISTS preferencias ("
                       . "usuario_id INTEGER NOT NULL UNIQUE, "
                       . "horas_dia INTEGER DEFAULT (4), "
                       . "horas_almoco INTEGER DEFAULT (1), "
                       . "nome TEXT NOT NULL, "
                       . "email TEXT, "
                       . "dias_trabalho TEXT"
                       . ");"
                       . "CREATE TABLE IF NOT EXISTS ponto ("
                       . "id INTEGER PRIMARY KEY AUTOINCREMENT, "
                       . "usuario_id INTEGER NOT NULL, "
                       . "entrada DATETIME NOT NULL, "
                       . "saida DATETIME, "
                       . "obs TEXT"
                       . ");";

                $this->_instance->exec($query);
            } catch (Exception $e) {
                $msg = 'Erro de conexão ao banco de dados: ' . $e->getMessage();

                throw new \AbstractDbException($msg);
            }
        }
    }

    /**
     * SingleTon da conexão
     *
     * @throws \AbstractDbException
     * @return SQLite3
     */
    public function getInstance()
    {
        return $this->_instance;
    }
}