<?php
/**
 * PontoException
 *
 * Exceções
 *
 * PHP version 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author     Thiago Paes - mrprompt@gmail.com
 * @package    Ponto
 * @subpackage Ponto
 * @filesource Ponto.php
 * @copyright  Copyright 2011, Thiago Paes
 * @link       http://github.com/mrprompt/Ponto/
 * @version    $Revision: 0.1 $
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * @see AbstractDb
 */
require_once __DIR__ . '/AbstractDb.php';

/**
 * @see Usuarios
 */
require_once __DIR__ . '/Usuarios.php';

class PontoException extends \AbstractDbException
{
    //
}

/**
 * Classe Ponto
 *
 * Ponto eletrônico
 *
 * PHP version 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author     Thiago Paes - mrprompt@gmail.com
 * @package    Ponto
 * @filesource Ponto.php
 * @copyright  Copyright 2011, Thiago Paes
 * @link       http://github.com/mrprompt/Ponto/
 * @version    $Revision: 0.1 $
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class Ponto extends \Usuarios
{
    //private $_id;
    //private $_usuario_id;
    private $_entrada;
    private $_saida;
    private $_obs;
    /*
    public function getId()
    {
        return $this->_id;
    }

    public function setId($_id)
    {
        $this->_id = $_id;

        return $this;
    }

    public function getUsuarioId()
    {
        return $this->_usuario_id;
    }

    public function setUsuarioId($_usuario_id)
    {
        $this->_usuario_id = $_usuario_id;

        return $this;
    }
    */
    public function getEntrada()
    {
        return $this->_entrada;
    }

    public function setEntrada($_entrada)
    {
        $this->_entrada = $_entrada;

        return $this;
    }

    public function getSaida()
    {
        return $this->_saida;
    }

    public function setSaida($_saida)
    {
        $this->_saida = $_saida;

        return $this;
    }

    public function getObs()
    {
        return $this->_obs;
    }

    public function setObs($_obs)
    {
        $this->_obs = $_obs;

        return $this;
    }

    /**
     * Gera um relatório das horas trabalhadas em cada dia
     *
     * @param  string $data
     * @return array
     */
    public function relatorio($data = null)
    {
        if ($data === null) {
            $data = date('Y-m-d');
        }

        list($ano, $mes, $dia) = explode('-', $data);

        $objDataInicial = new DateTime("{$ano}-{$mes}-01 00:00:00");
        $objDataFinal   = new DateTime("{$ano}-{$mes}-{$dia} 23:59:59");

        $sql = "SELECT id, entrada, saida, obs "
             . "FROM ponto "
             . "WHERE entrada BETWEEN :inicio AND :final "
             . "AND usuario_id = :usuario_id "
             . "ORDER BY entrada DESC";

        $prep = $this->getInstance()->prepare($sql);
        $prep->bindValue(':inicio', $objDataInicial->format('Y-m-d H:i:s'));
        $prep->bindValue(':final', $objDataFinal->format('Y-m-d H:i:s'));
        $prep->bindValue(':usuario_id', $this->_decrypt($this->getId()));

        $result  = $prep->execute();
        $retorno = array();

        while ($output = $result->fetchArray(SQLITE3_ASSOC)) {
            $dtEntrada = new DateTime($output['entrada']);
            $strSaida  = null;
            $strHoras  = '00:00';

            if (!empty($output['saida'])) {
                $dtSaida   = new DateTime($output['saida']);
                $strSaida  = $dtSaida->format('H:i');
                $dtCalculo = $dtEntrada->diff($dtSaida, true);
                $strHoras  = $dtCalculo->format('%H:%I');
            }

            $retorno[] = array(
                'data'      => $dtEntrada->format('d/m/Y'),
                'entrada'   => $dtEntrada->format('H:i'),
                'saida'     => $strSaida,
                'horas'     => $strHoras,
                'obs'       => $output['obs'],
            );
        }

        return $retorno;
    }

    /**
     * Registra o ponto de entrada
     *
     * @return integer
     * @throws PontoException
     */
    public function entrada()
    {
        // decriptografo o id do usuário
        $uid = $this->_decrypt($this->getId());

        // primeiro busco se existe uma entrada sem registro de saída
        $sql = "SELECT id "
             . "FROM ponto "
             . "WHERE entrada "
             . "BETWEEN STRFTIME('%Y-%m-%d 00:00:00', DATE('NOW', 'LOCALTIME')) "
             . "AND DATETIME('NOW', 'LOCALTIME') "
             . "AND saida IS NULL "
             . "AND usuario_id = :usuario_id "
             . "ORDER BY entrada DESC "
             . "LIMIT 1";

        $stmt = $this->getInstance()->prepare($sql);
        $stmt->bindValue(':usuario_id', $uid);

        $result = $stmt->execute();

        if ($result == true) {
            $retorno = $result->fetchArray(SQLITE3_ASSOC);

            if (empty($retorno['id'])) {
                $sql = "INSERT INTO ponto(id, entrada, saida, obs, usuario_id) "
                     . "VALUES(NULL, DATETIME('NOW', 'LOCALTIME'), NULL, "
                     . ":obs, :usuario_id)";

                $stmt = $this->getInstance()->prepare($sql);
                $stmt->bindValue(':obs', $this->getObs());
                $stmt->bindValue(':usuario_id', $uid);

                if ($stmt->execute() == true) {
                    return $this->getInstance()->lastInsertRowID();
                } else {
                    throw new \PontoException('Erro registrando ponto.');
                }
            } else {
                throw new \PontoException('Registro de saída não encontrado.');
            }
        } else {
            throw new \PontoException('Erro buscando ponto.');
        }
    }

    /**
     * Registra o ponto de saída
     *
     * @return integer
     * @throws PontoException
     */
    public function saida()
    {
        $sql = "UPDATE ponto "
             . "SET saida = DATETIME('NOW', 'LOCALTIME'), obs = :obs "
             . "WHERE entrada BETWEEN "
             . "STRFTIME('%Y-%m-%d 00:00:00', DATE('NOW', 'LOCALTIME')) "
             . "AND DATETIME('NOW', 'LOCALTIME') "
             . "AND usuario_id = :usuario_id "
             . "AND id = (SELECT id FROM ponto WHERE entrada BETWEEN "
             . "STRFTIME('%Y-%m-%d 00:00:00', DATE('NOW', 'LOCALTIME')) "
             . "AND DATETIME('NOW', 'LOCALTIME') "
             . "AND saida IS NULL "
             . "AND usuario_id = :usuario_id "
             . "ORDER BY entrada DESC LIMIT 1)";

        $stmt = $this->getInstance()->prepare($sql);
        $stmt->bindValue(':obs', $this->getObs());
        $stmt->bindValue(':usuario_id', $this->_decrypt($this->getId()));

        if ($stmt->execute() == true) {
            return $this->getInstance()->changes();
        } else {
            throw new \PontoException('Erro registrando ponto.');
        }
    }

    /**
     * Removo um registro
     *
     * @throws \PontoException
     * @return boolean
     */
    public function delete()
    {
        $sql = "DELETE FROM ponto WHERE usuario_id = :id";

        $prep = $this->getInstance()->prepare($sql);
        $prep->bindValue(':id', $this->_decrypt($this->getId()));

        $exec = $prep->execute();

        if ($exec == true) {
            return true;
        } else {
            throw new \PontoException('Erro removendo registro.');
        }
    }
}
