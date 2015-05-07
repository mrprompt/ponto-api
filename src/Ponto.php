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
    private $_entrada;
    private $_saida;
    private $_obs;

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
        $uid        = $this->_decrypt($this->getId());
        $db         = $this->getInstance();
        $now        = new DateTime();
        $entrada    = $now->format('Y-m-d H:i:s');
        $sql        = "INSERT INTO ponto(id, entrada, saida, obs, usuario_id) "
                    . "VALUES(NULL, :entrada, NULL, :obs, :usuario_id)";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':obs', $this->getObs());
        $stmt->bindValue(':usuario_id', $uid);
        $stmt->bindValue(':entrada', $entrada);

        if ($stmt->execute() === false) {
            throw new PontoException('Erro registrando ponto.');
        }

        return $db->lastInsertRowID();
    }

    /**
     * Registra o ponto de saída
     *
     * @return integer
     * @throws PontoException
     */
    public function saida()
    {
        $db         = $this->getInstance();
        $now        = new DateTime();
        $saida      = $now->format('Y-m-d H:i:s');
        $entrada    = sprintf('%s 00:00:00', $now->format('Y-m-d'));
        $sql        = "
        UPDATE ponto
        SET
            saida           = :saida,
            obs             = :obs
        WHERE usuario_id    = :usuario_id
        AND id = (
            SELECT id
            FROM ponto
            WHERE entrada BETWEEN :entrada AND :saida
            AND saida IS NULL
            AND usuario_id = :usuario_id
            ORDER BY entrada DESC
            LIMIT 1
        )";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':obs', $this->getObs());
        $stmt->bindValue(':usuario_id', $this->_decrypt($this->getId()));
        $stmt->bindValue(':saida', $saida);
        $stmt->bindValue(':entrada', $entrada);

        $result = $stmt->execute();

        if ($result === false || $db->changes() == 0) {
            $result = $this->entrada();
        }

        return $result;
    }
}
