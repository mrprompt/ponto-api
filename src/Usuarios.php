<?php
/**
 * UsuariosException
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
 * @subpackage Usuarios
 * @filesource Usuarios.php
 * @copyright  Copyright 2011, Thiago Paes
 * @link       http://github.com/mrprompt/Ponto/
 * @version    $Revision: 0.1 $
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * @see AbstractDb
 */
require_once __DIR__ . '/AbstractDb.php';

class UsuariosException extends \AbstractDbException
{
    //
}

/**
 * Usuarios
 *
 * Usuários do sistema
 *
 * PHP version 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author     Thiago Paes - mrprompt@gmail.com
 * @package    Ponto
 * @subpackage Usuarios
 * @filesource Usuarios.php
 * @copyright  Copyright 2011, Thiago Paes
 * @link       http://github.com/mrprompt/Ponto/
 * @version    $Revision: 0.1 $
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class Usuarios extends \AbstractDb
{
    private $_id          = null;
    private $_login       = null;
    private $_password    = null;
    private $_owner       = null;
    private $_horas_dia   = null;
    private $_horas_almoco= null;
    private $_nome        = null;
    private $_email       = null;
    private $_dias_trabalho = null;
    private $_key         = '4ArqdTSjdcuzhsT1';
    private $_cipher      = MCRYPT_RIJNDAEL_256;
    private $_mode        = MCRYPT_MODE_CFB;

    private function _createIv()
    {
        //we want a 32 byte binary blob
        $aes256Key = hash("SHA256", $this->_key, true);

        return $aes256Key;
    }

    protected function _encrypt($text)
    {
        $iv     = $this->_createIv();
        $cipher = $this->_cipher;
        $key    = $this->_key;
        $mode   = $this->_mode;
        $input  = $key . '' . $text;

        $out = mcrypt_encrypt($cipher, $key, $input, $mode, $iv);

        return utf8_encode($out);
    }

    protected function _decrypt($text)
    {
        $iv     = $this->_createIv();
        $cipher = $this->_cipher;
        $key    = $this->_key;
        $mode   = $this->_mode;
        $input  = utf8_decode($text);

        $out = mcrypt_decrypt($cipher, $key, $input, $mode, $iv);

        return str_replace($key, '', $out);
    }

    public function getId()
    {
        return $this->_id;
    }

    public function setId($_id)
    {
        $this->_id = $_id;

        return $this;
    }

    public function getOwner()
    {
        return $this->_owner;
    }

    public function setOwner($_owner)
    {
        $this->_owner = $_owner;

        return $this;
    }

    public function getLogin()
    {
        return $this->_login;
    }

    public function setLogin($_login)
    {
        $this->_login = $_login;

        return $this;
    }

    public function getPassword()
    {
        return $this->_password;
    }

    public function setPassword($_password)
    {
        if (strlen($_password) !== 0) {
            $this->_password = sha1($_password);
        }

        return $this;
    }

    public function getHorasDia()
    {
        return $this->_horas_dia;
    }

    public function setHorasDia($_horas_dia)
    {
        $this->_horas_dia = $_horas_dia;

        return $this;
    }

    public function getHorasAlmoco()
    {
        return $this->_horas_almoco;
    }

    public function setHorasAlmoco($_horas_almoco)
    {
        $this->_horas_almoco = $_horas_almoco;

        return $this;
    }

    public function getNome()
    {
        return $this->_nome;
    }

    public function setNome($_nome)
    {
        $this->_nome = $_nome;

        return $this;
    }

    public function getEmail()
    {
        return $this->_email;
    }

    public function setEmail($_email)
    {
        $this->_email = $_email;

        return $this;
    }

    public function getDiasTrabalho()
    {
        return $this->_dias_trabalho;
    }

    public function setDiasTrabalho($_dias_trabalho)
    {
        $this->_dias_trabalho = $_dias_trabalho;

        return $this;
    }

    /**
     * Login de usuários
     *
     * @throws \UsuariosException
     * @return array
     */
    public function login()
    {
        $sql = "SELECT u.id, u.owner, u.login, "
             . "p.nome, p.email, p.horas_dia, p.horas_almoco, p.dias_trabalho "
             . "FROM usuarios u "
             . "JOIN preferencias p "
             . "ON u.id = p.usuario_id "
             . "WHERE u.login  = :login "
             . "AND u.password = :senha";

        $prep = $this->getInstance()->prepare($sql);
        $prep->bindValue(':login', $this->getLogin());
        $prep->bindValue(':senha', $this->getPassword());

        $exec = $prep->execute();

        if ($exec == true) {
            $arrUsuario = $exec->fetchArray(SQLITE3_ASSOC);

            if (!empty($arrUsuario['id'])) {
                $arrUsuario['id']    = $this->_encrypt($arrUsuario['id']);
                $arrUsuario['owner'] = $this->_encrypt($arrUsuario['owner']);

                return $arrUsuario;
            } else {
                throw new \UsuariosException('Usuário/senha inválidos!');
            }
        } else {
            throw new \UsuariosException('Erro buscando usuário.');
        }
    }

    /**
     * Busca um usuário pelo login
     *
     * @throws \UsuariosException
     * @return \Usuarios
     */
    private function _findByLogin()
    {
        $sql = "SELECT id, login "
             . "FROM usuarios "
             . "WHERE login = :login "
             . "LIMIT 1";

        $prep = $this->getInstance()->prepare($sql);
        $prep->bindValue(':login', $this->getLogin());

        $exec = $prep->execute();

        if ($exec == true) {
            return $exec->fetchArray(SQLITE3_ASSOC);
        } else {
            throw new \UsuariosException('Erro buscando usuário.');
        }
    }

    /**
     * Valido os campos
     *
     * @throws \UsuariosException
     */
    private function _validate()
    {
        if (strlen($this->getLogin()) === 0) {
            throw new \UsuariosException('Login inválido.');
        }

        if (strlen($this->getPassword()) === 0) {
            throw new \UsuariosException('Senha inválida.');
        }

        if (!filter_var($this->getHorasAlmoco(), FILTER_VALIDATE_INT)) {
            throw new \UsuariosException('Intervalo inválido.');
        }

        if (!filter_var($this->getHorasDia(), FILTER_VALIDATE_INT)) {
            throw new \UsuariosException('Carga horária inválida.');
        }

        if (!filter_var($this->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \UsuariosException('E-mail inválido.');
        }

        if (strlen($this->getNome()) === 0) {
            throw new \UsuariosException('Nome inválido.');
        }

        if (strlen($this->getDiasTrabalho()) === 0) {
            throw new \UsuariosException('Dias de trabalho inválido.');
        }
    }

    /**
     * Adiciona ao banco
     *
     * @throws \UsuariosException
     * @return array
     */
    public function save()
    {
        try {
            $id     = null;
            $owner  = 1;

            if (strlen($this->getId()) !== 0) {
                $id = $this->_decrypt($this->getId());

                if (!filter_var($id, FILTER_VALIDATE_INT)) {
                    throw new \UsuariosException('ID inválido!');
                }
            }

            // procuro por um usuário existente
            $search = $this->_findByLogin();

            if (strlen($search['id']) !== 0 && strlen($id) == 0) {
                throw new \UsuariosException('Usuário já cadastrado.');
            }

            // tratando o 'dono' do usuário
            if (strlen($this->getOwner()) !== 0) {
                $owner = $this->_decrypt($this->getOwner());

                if (!filter_var($owner, FILTER_VALIDATE_INT)) {
                    throw new \UsuariosException('Super usuário inválido!');
                }
            }

            // se o usuário estiver logado e não setar uma senha, recupero a atual
            // para o replace abaixo funcionar corretamente.
            if ($id !== null && strlen($this->getPassword()) == 0) {
                $sql = "SELECT login, password "
                     . "FROM usuarios "
                     . "WHERE id = :id "
                     . "LIMIT 1";

                $prep = $this->getInstance()->prepare($sql);
                $prep->bindValue(':id', $id);
                $exec = $prep->execute();
                $user = $exec->fetchArray(SQLITE3_ASSOC);

                $this->_password = $user['password'];
            }

            // valido os dados
            $this->_validate();

            $sql = "INSERT OR REPLACE INTO usuarios "
                 . "(id, owner, login, password) "
                 . "VALUES (:id, :owner, :login, :senha)";

            $prep = $this->getInstance()->prepare($sql);
            $prep->bindValue(':id', $id);
            $prep->bindValue(':owner', $owner);
            $prep->bindValue(':login', $this->getLogin());
            $prep->bindValue(':senha', $this->getPassword());

            if ($prep->execute() == true) {
                if ($id === null) {
                    $id = $this->getInstance()->lastInsertRowID();
                }

                $this->setId($this->_encrypt($id));
                $this->setOwner($this->_encrypt($owner));

                $sql = "INSERT OR REPLACE INTO preferencias "
                     . "(usuario_id, horas_dia, horas_almoco, "
                     . "email, nome, dias_trabalho) "
                     . "VALUES (:usuario_id, :horas, :almoco, "
                     . ":email, :nome, :dias)";

                $prep = $this->getInstance()->prepare($sql);
                $prep->bindValue(':usuario_id', $id);
                $prep->bindValue(':horas', $this->getHorasDia());
                $prep->bindValue(':almoco', $this->getHorasAlmoco());
                $prep->bindValue(':email', $this->getEmail());
                $prep->bindValue(':nome', $this->getNome());
                $prep->bindValue(':dias', $this->getDiasTrabalho());
                $prep->execute();

                return $this;
            } else {
                throw new \UsuariosException('Erro inserindo usuário.');
            }
        } catch (Exception $e) {
            throw new \UsuariosException($e->getMessage());
        }
    }

    /**
     * Lista todos os usuários
     *
     * @throws \UsuariosException
     * @return array
     */
    public function getAll()
    {
        $sql = "SELECT u.id, u.owner, u.login, "
             . "p.nome, p.email, p.horas_dia, p.horas_almoco, p.dias_trabalho "
             . "FROM usuarios AS u "
             . "LEFT JOIN preferencias AS p "
             . "ON u.id = p.usuario_id "
             . "WHERE u.owner = :owner "
             . "AND u.id != :owner "
             . "ORDER BY p.nome ASC";

        $prep = $this->getInstance()->prepare($sql);
        $prep->bindValue(':owner', $this->_decrypt($this->getOwner()));

        $exec = $prep->execute();

        if ($exec == true) {
            $retorno = array();

            while ($output = $exec->fetchArray(SQLITE3_ASSOC)) {
                $output['id']    = $this->_encrypt($output['id']);
                $output['owner'] = $this->_encrypt($output['owner']);

                $retorno[] = $output;
            }

            return $retorno;
        } else {
            throw new \UsuariosException('Erro buscando usuários.');
        }
    }

    /**
     * Removo usuários
     *
     * @throws \UsuariosException
     * @return boolean
     */
    public function delete()
    {
        $sql = "DELETE FROM usuarios WHERE id = :id AND owner = :dono; "
             . "DELETE FROM preferencias WHERE usuario_id = :id; "
             . "DELETE FROM ponto WHERE usuario_id = :id; ";

        $prep = $this->getInstance()->prepare($sql);
        $prep->bindValue(':dono', $this->_decrypt($this->getOwner()));
        $prep->bindValue(':id', $this->_decrypt($this->getId()));

        $exec = $prep->execute();

        if ($exec == true) {
            return true;
        } else {
            throw new \UsuariosException('Erro removendo registro.');
        }
    }
}
