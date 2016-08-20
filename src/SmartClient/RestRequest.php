<?php
/*    Mindbit PHP Library
 *    Copyright (C) 2009 Mindbit SRL
 *
 *    This library is free software; you can redistribute it and/or
 *    modify it under the terms of the GNU Lesser General Public
 *    License as published by the Free Software Foundation; either
 *    version 2.1 of the License, or (at your option) any later version.
 *
 *    This library is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *    Lesser General Public License for more details.
 *
 *    You should have received a copy of the GNU Lesser General Public
 *    License along with this library; if not, write to the Free Software
 *    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace Mindbit\Mpl\SmartClient;

use Mindbit\Mpl\Mvc\Controller\OmRequest;

abstract class RestRequest extends OmRequest
{
    protected $startRow;
    protected $endRow;
    protected $textMatchStyle;
    protected $componentId;
    protected $dataSource;
    protected $oldValues;

    protected $response;
    protected $joinMap = array();

    public function decode()
    {
        /* Data sources must use the postMessage dataProtocol */
        if (!isset($_SERVER["REQUEST_METHOD"]) ||
                $_SERVER["REQUEST_METHOD"] != "POST" ||
                !isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
            throw new Exception("Unsupported request");
        }

        /* Decode the json encoded parameters from the raw HTTP request */
        $request = (array)json_decode($GLOBALS['HTTP_RAW_POST_DATA']);

        if (!isset($request["operationType"])) {
            throw new Exception("Operation type not specified");
        }

        switch ($request["operationType"]) {
            case 'fetch':
                $this->operationType = self::OPERATION_FETCH;
                break;
            case 'add':
                $this->operationType = self::OPERATION_ADD;
                break;
            case 'update':
                $this->operationType = self::OPERATION_UPDATE;
                break;
            case 'remove':
                $this->operationType = self::OPERATION_REMOVE;
                break;
            default:
                throw new Exception("Unknown operation type");
        }

        if (isset($request["startRow"])) {
            $this->startRow = (int)$request["startRow"];
        }

        if (isset($request["endRow"])) {
            $this->endRow = (int)$request["endRow"];
        }

        if (isset($request["textMatchStyle"])) {
            $this->textMatchStyle = $request["textMatchStyle"];
        }

        if (isset($request["oldValues"])) {
            $this->oldValues = (array)$request["oldValues"];
        }

        if (isset($request["data"])) {
            $this->data = (array)$request["data"];
        }
    }

    protected function init()
    {
        parent::init();
        $this->response = new RestResponse();
    }

    public function addJoinMap($om, $joinParams)
    {
        $table = constant("Base" . get_class($om->getPeer()) . "::TABLE_NAME");
        if (!isset($this->joinMap[$table])) {
            $this->joinMap[$table] = array(
                    "peer" => $om->getPeer(),
                    "join" => array()
                    );
        }
        $this->joinMap[$table]["join"][] = $joinParams;
    }

    public function buildFetchCriteria()
    {
        $c = new Criteria();
        if (null !== $this->startRow) {
            $c->setLimit($this->endRow - $this->startRow);
            $c->setOffset($this->startRow);
        }

        // build a new array of fields that are bound directly to our
        // OM; mapped fields need to be taken care of separately
        $omData = array();
        $mapped = array();
        foreach ($this->data as $k => $v) {
            if (strstr($k, ".") === false) {
                $omData[$k] = $v;
                continue;
            }
            list($table, $field) = explode(".", $k, 2);
            if (!isset($mapped[$table])) {
                $mapped[$table] = true;
                foreach ($this->joinMap[$table]["join"] as $params) {
                    call_user_func_array(array($c, "addJoin"), $params);
                }
                $colName = $this->joinMap[$table]["peer"]->translateFieldName(
                    $field,
                    BasePeer::TYPE_FIELDNAME,
                    BasePeer::TYPE_COLNAME
                );
                $c->add($colName, $v);
            }
        }

        $omFields = $this->arrayToOm($omData);
        foreach ($omFields as $field => $value) {
            $colName = $this->omPeer->translateFieldName(
                $field,
                BasePeer::TYPE_FIELDNAME,
                BasePeer::TYPE_COLNAME
            );
            $c->add($colName, $value);
        }
        return $c;
    }

    public function doFetch()
    {
        $objs = $this->omPeer->doSelect($this->buildFetchCriteria());
        foreach ($objs as $obj) {
            $this->response->addRow($this->omToArray($obj));
        }
    }

    public function doSave()
    {
        parent::doSave();
        $this->response->addRow($this->omToArray($this->om));
    }

    public function dispatch()
    {
        try {
            parent::dispatch();
        } catch (Exception $e) {
            $this->handleException($e);
            $this->response->setFailure($e->getMessage());
        }
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getJsonResponse()
    {
        try {
            return $this->getResponse()->jsonEncode();
        } catch (Exception $e) {
            $this->handleException($e);
            /* If we catch an exception here, probably we failed to jsonEncode()
               the original response. Therefore, we create a new response object
               in which we encode the exception message. This new response object
               should not fail at json encoding.
             */
            $response = new RestResponse();
            $response->setFailure($e->getMessage());
            return $response->jsonEncode();
        }
    }

    public function handleException($e)
    {
    }
}
