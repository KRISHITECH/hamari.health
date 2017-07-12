<?php

/* +-----------------------------------------------------------------------------+
 *    OpenEMR - Open Source Electronic Medical Record
 *    Copyright (C) 2013 Z&H Consultancy Services Private Limited <sam@zhservices.com>
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Affero General Public License as
 *    published by the Free Software Foundation, either version 3 of the
 *    License, or (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *    @author  Sanal Jacob <sanalj@zhservices.com>
 * +------------------------------------------------------------------------------+
 */

namespace Lab\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use \Application\Model\ApplicationTable;

class ProviderTable extends AbstractTableGateway {

    public $tableGateway;
    protected $applicationTable;

    public function __construct(TableGateway $tableGateway) {
        $this->tableGateway = $tableGateway;
        $adapter = \Zend\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter();
        $this->adapter = $adapter;
        $this->applicationTable = new ApplicationTable();
    }

    /**
     * saveProcedureProvider()
     * @param type $post
     * @return array
     */
    public function saveProcedureProvider($post) {
        $return = array();
        if ($post['ppid'] && $post['ppid'] > 0) {
            $sql = "UPDATE procedure_providers SET name = ?, 
                                                    npi = ?,           
                                                    send_app_id = ?, 
                                                    send_fac_id = ?, 
                                                    recv_app_id = ?, 
                                                    recv_fac_id = ?, 
                                                    DorP = ?, 
                                                    protocol = ?, 
                                                    remote_host = ?, 
                                                    login = ?, 
                                                    password = ?, 
                                                    orders_path = ?, 
                                                    results_path = ?, 
                                                    notes = ?,
                                                    mirth_lab_id = ?,
                                                    mirth_lab_name = ?
                                                    WHERE ppid = ?";
            $ret = $this->applicationTable->zQuery($sql, array($post['name'],
                $post['npi'],
                $post['send_app_id'],
                $post['send_fac_id'],
                $post['recv_app_id'],
                $post['recv_fac_id'],
                $post['DorP'],
                $post['protocol'],
                $post['remote_host'],
                $post['login'],
                $post['password'],
                $post['orders_path'],
                $post['results_path'],
                $post['notes'],
                $post['mirth_lab_id'],
                $post['mirth_lab_name'],
                $post['ppid'])
            );
        } else {
            $sql = "INSERT INTO procedure_providers SET name = ?, 
                                                        npi = ?,           
                                                        send_app_id = ?, 
                                                        send_fac_id = ?, 
                                                        recv_app_id = ?, 
                                                        recv_fac_id = ?, 
                                                        DorP = ?, 
                                                        protocol = ?, 
                                                        remote_host = ?, 
                                                        login = ?, 
                                                        password = ?, 
                                                        orders_path = ?, 
                                                        results_path = ?, 
                                                        notes = ?,
                                                        mirth_lab_id = ?,
                                                        mirth_lab_name = ?";
            $ret = $this->applicationTable->zQuery($sql, array($post['name'],
                $post['npi'],
                $post['send_app_id'],
                $post['send_fac_id'],
                $post['recv_app_id'],
                $post['recv_fac_id'],
                $post['DorP'],
                $post['protocol'],
                $post['remote_host'],
                $post['login'],
                $post['password'],
                $post['orders_path'],
                $post['results_path'],
                $post['notes'],
                $post['mirth_lab_id'],
                $post['mirth_lab_name'])
            );
        }
        return $return;
    }

    // Delete Procedure Provider
    public function deleteProcedureProvider($post) {
        if ($post['ppid'] && $post['ppid'] > 0) {
            $sql = "DELETE FROM procedure_providers WHERE ppid=?";
            $return = $this->applicationTable->zQuery($sql, array($post['ppid']));
        }
    }

}
