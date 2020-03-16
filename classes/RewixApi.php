<?php
/**
 * NOTICE OF LICENSE.
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 * @author    Zero11
 * @copyright 2015-2018 Zero11 S.r.l.
 * @license   Proprietary
 */

use BDroppy\Includes\WC\Models\ProductModel;

include_once dirname(__FILE__).'/ImportTools.php';
include_once dirname(__FILE__).'/RemoteOrder.php';
include_once dirname(__FILE__).'/ConfigKeys.php';

class BdroppyRewixApi
{
    const SOLD_API_LOCK_OP   = 'lock';
    const SOLD_API_SET_OP    = 'set';
    const SOLD_API_UNLOCK_OP = 'unlock';

    private $processingCache;
    private $pendingCache;
    private $logger;

    public function __construct()
    {
        $this->logger = new FileLogger(AbstractLogger::DEBUG);
        $this->logger->setFilename(_PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'bdroppy-api.log');
    }

    public function getProduct($product_id, $catalog_id) {
        $api_token = Configuration::get('BDROPPY_TOKEN');
        $header = "Authorization: Bearer " . $api_token;

        $url = Configuration::get('BDROPPY_API_URL') . "/restful/product/$product_id/usercatalog/$catalog_id";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json', 'Content-Type: application/json', $header));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5000);
        $data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        if($http_code != 200)
            $this->logger->logDebug( 'getProduct - http_code : ' . $http_code . ' - url : ' . $url . ' data : ' . $data );
        $ret['http_code'] = $http_code;
        $ret['data'] = $data;
        return $ret;
    }

    public function getProductsJson($api_catalog) {
        $base_url = Configuration::get('BDROPPY_API_URL');
        $api_token = Configuration::get('BDROPPY_TOKEN');
        $url = $base_url . '/restful/export/api/products.json?user_catalog=' . $api_catalog . '&acceptedlocales=en_US&onlyid=true';

        $header = "Authorization: Bearer " . $api_token;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json', 'Content-Type: application/json', $header));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        $data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        if($http_code != 200)
            $this->logger->logDebug( 'getProductsJson - http_code : ' . $http_code . ' - url : ' . $url . ' data : ' . $data );
        $ret['http_code'] = $http_code;
        $ret['data'] = $data;
        return $ret;
    }

    public function getUserInfo() {
        $catalogs = [];
        $base_url = Configuration::get('BDROPPY_API_URL');
        $api_key = Configuration::get('BDROPPY_API_KEY');
        $api_password = Configuration::get('BDROPPY_API_PASSWORD');
        $api_token = Configuration::get('BDROPPY_TOKEN');

        $url = $base_url . '/api/user/me';

        $header = "Authorization: Bearer " . $api_token;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json', 'Content-Type: application/json', $header));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        $data  = curl_exec($ch);
        $http_code  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $curl_error = curl_error( $ch );
        curl_close( $ch );
        if($http_code != 200)
            $this->logger->logDebug( 'getUserInfo - http_code : ' . $http_code . ' - url : ' . $url . ' data : ' . $data );
        if ($http_code === 200)
        {
            $data = json_decode($data);
        }
        $ret['http_code'] = $http_code;
        $ret['data'] = $data;
        return $ret;
    }

    public function getUserCatalogs() {
        $catalogs = [];
        $base_url = Configuration::get('BDROPPY_API_URL');
        $api_key = Configuration::get('BDROPPY_API_KEY');
        $api_password = Configuration::get('BDROPPY_API_PASSWORD');
        $api_token = Configuration::get('BDROPPY_TOKEN');

        $url = $base_url . '/restful/user_catalog';

        $header = "Authorization: Bearer " . $api_token;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json', 'Content-Type: application/json', $header));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        $data  = curl_exec($ch);
        $http_code  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $curl_error = curl_error( $ch );
        curl_close( $ch );
        if($http_code != 200)
            $this->logger->logDebug( 'getUserCatalogs - http_code : ' . $http_code . ' - url : ' . $url . ' data : ' . $data );
        if ($http_code === 200)
        {
            $catalogs = json_decode($data);
        }
        $ret['http_code'] = $http_code;
        $ret['catalogs'] = $catalogs;
        return $ret;
    }

    public function getCatalogById2($catalog = null)
    {
        if (is_null($catalog)) {return null;}

        $base_url = Configuration::get('BDROPPY_API_URL');
        $api_key = Configuration::get('BDROPPY_API_KEY');
        $api_password = Configuration::get('BDROPPY_API_PASSWORD');
        $api_token = Configuration::get('BDROPPY_TOKEN');

        $url = $base_url . '/restful/export/api/products.json?user_catalog='.$catalog.'&acceptedlocales=en_US&onlyid=true';

        $header = "Authorization: Bearer " . $api_token;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json', 'Content-Type: application/json', $header));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        $data  = curl_exec($ch);
        $http_code  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $curl_error = curl_error( $ch );
        curl_close( $ch );
        $response = json_decode($data);

        if($http_code != 200)
            $this->logger->logDebug( 'getCatalogById2 - http_code : ' . $http_code . ' - url : ' . $url . ' data : ' . $data );
        if($response === 500 || empty($response->userCatalog) ) return 500;

        $ids=[];
        foreach ($response->items as $key => $item)
        {
            $ids[] = $item->refId;
        }
        $response = $response->userCatalog;
        $response->ids = $ids;
        return $response;
    }

    public function modifyGrowingOrder($operations)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><root></root>');
        $operationLock = $xml->addChild('operation');
        $operationLock->addAttribute('type', self::SOLD_API_LOCK_OP);
        $operationSet = $xml->addChild('operation');
        $operationSet->addAttribute('type', self::SOLD_API_SET_OP);
        $operationUnlock = $xml->addChild('operation');
        $operationUnlock->addAttribute('type', self::SOLD_API_UNLOCK_OP);
        foreach ($operations as $op) {
            switch ($op['type']) {
                case self::SOLD_API_LOCK_OP:
                    $model = $operationLock->addChild('model');
                    break;
                case self::SOLD_API_SET_OP:
                    $model = $operationSet->addChild('model');
                    break;
                case self::SOLD_API_UNLOCK_OP:
                    $model = $operationUnlock->addChild('model');
                    break;
            }
            if ( isset( $model ) ) {
                $model->addAttribute('stock_id', $op['model_id']);
                $model->addAttribute('quantity', $op['qty']);
                $this->logger->logDebug('Model Ref.ID #'.$op['model_id'].', qty: '.$op['qty'].', operation type: '.$op['type']);
            } else {
                $this->logger->logInfo( 'Invalid operation type: ' . $op['type'] );
            }
        }
        $xmlText = $xml->asXML();

        $username = Configuration::get('BDROPPY_API_KEY');
        $password = (string)Configuration::get('BDROPPY_API_PASSWORD');
        $api_token = Configuration::get('BDROPPY_TOKEN');
        $url = Configuration::get('BDROPPY_API_URL') . '/restful/ghost/orders/sold';
        $header = "Authorization: Bearer " . $api_token;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml','Accept: application/xml', $header));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlText);
        $data = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$this->handleCurlError($httpCode)) {
            return false;
        }

        $reader = new XMLReader();
        $reader->xml($data);
        $reader-> read();
        $this->setGrowingOrderId($reader->getAttribute('order_id'));

        $growingOrder = array();
        while ($reader->read()) {
            if ($reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'model') {
                $stock_id                   = $reader->getAttribute('stock_id');
                $growingOrder[ $stock_id ] = array(
                    'stock_id'  => $stock_id,
                    'locked'    => $reader->getAttribute('locked'),
                    'available' => $reader->getAttribute('available'),
                );
            }
        }
        foreach ( $operations as $op ) {
            if ( isset( $growing_order[ $op['model_id'] ] ) ) {
                $product             = $growingOrder[ $op['model_id'] ];
                $success             = true;
                $pending_quantity    = $this->getPendingQtyByRewixModel( (int) $op['model_id'] );
                $processing_quantity = $this->getProcessingQtyByRewixModel( (int) $op['model_id'] );

                if ( $op['type'] == self::SOLD_API_LOCK_OP && $product['locked'] < ( $pending_quantity + $processing_quantity + $op['qty'] ) ) {
                    $success = false;
                } else if ( $op['type'] == self::SOLD_API_UNLOCK_OP && $product['locked'] < ( $pending_quantity + $processing_quantity - $op['qty'] ) ) {
                    $success = false;
                } else if ( $op['type'] == self::SOLD_API_SET_OP && $product['locked'] < $op['qty'] ) {
                    $success = false;
                }

                if ( ! $success ) {
                    $this->logger->logError( 'Model Ref.ID #' . $op['model_id'] . ', looked: ' . $product['locked'] . ', qty: ' . $op['qty'] . ', pending: ' . $this->getPendingQtyByRewixModel( $stock_id ) . ', processing: ' . $this->getPendingQtyByRewixModel( $stock_id ) . ', operation type: ' . $op['type'] . ' : OPERATION FAILED!' );
                    $errors[ $op['model_id'] ] = $op['qty'];
                } else {
                    $this->logger->logInfo( 'Model Ref.ID #' . $op['model_id'] . ', looked: ' . $product['locked'] . ', qty: ' . $op['qty'] . ', pending: ' . $this->getPendingQtyByRewixModel( $stock_id ) . ', processing: ' . $this->getPendingQtyByRewixModel( $stock_id ) . ', operation type: ' . $op['type'] );
                }
            } else {
                $errors[ $op['model_id'] ] = $op['qty'];
            }
        }

        return $errors;
    }

    public function validateOrder($cart)
    {
        $this->logger->logInfo('Validating order');

        $lines = $cart->getProducts(true);

        $operations = array();
        foreach ($lines as $line) {
            $productId = (int)$line['id_product'];
            $modelId = (int)$line['id_product_attribute'];
            $rewixId = (int)$line['isbn'];
            //$rewixId = BdroppyRemoteCombination::getRewixModelIdByProductAndModelId($productId, $modelId);
            if ($rewixId) {
                $operation = array(
                    'type' => BdroppyRewixApi::SOLD_API_LOCK_OP,
                    'model_id' => $rewixId,
                    'qty' => (int) $line['cart_quantity']
                );
                $operations[] = $operation;
            } else {
                $err = 'Model ID Not Found!';
                throw new Exception($err);
            }
        }

        $success = true;
        if (count($operations) > 0) {
            $this->logger->logDebug('Remote locking items');

            $errors = '';
            $errors = $this->modifyGrowingOrder($operations);
            if (isset($errors['curl_error'])) {
                throw new Exception('Error while placing order ('. $errors['message'].').', 'error');
            } else if (count($errors) > 0) {
                foreach ($errors as $model_id => $qty) {
                    throw new Exception(sprintf('Error while placing order. Product %s is not available in quantity requested (%d).',
                        $this->getProductNameFromRewixModelId((int)$model_id),
                        $qty));
                }
            }
        }
        return $success;
    }

    public function sendBdroppyOrder($order)
    {
        $this->logger->logInfo('Sending bdroppy order ' . $order->id);
        $mixed = false;
        $lines = $order->getProductsDetail();

        $catalog_id =  Configuration::get('BDROPPY_CATALOG');
        $rewix_order_key = $catalog_id .$order->id . time();

        $currency = new CurrencyCore($order->id_currency);
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><root></root>');
        $order_list = $xml->addChild('order_list');
        $xmlOrder = $order_list->addChild('order');
        $item_list = $xmlOrder->addChild('item_list');
        $xmlOrder->addChild('key', $rewix_order_key);
        $xmlOrder->addChild('date', str_replace('-', '/', $order->date_add) . ' +0000');
        $xmlOrder->addChild( 'user_catalog_id',Configuration::get('BDROPPY_CATALOG'));
        $xmlOrder->addChild( 'shipping_taxable', $order->total_shipping);
        $xmlOrder->addChild( 'shipping_currency', $currency->iso_code);
        $xmlOrder->addChild( 'price_total', $order->total_paid);
        $xmlOrder->addChild( 'price_currency', $currency->iso_code);

        $rewixProduct = 0;
        foreach ($lines as $line) {
            $productId = (int)$line['product_id'];
            $modelId = (int)$line['product_attribute_id'];
            $rewixId = (int)$line['product_isbn'];

            //$rewixId = BdroppyRemoteCombination::getRewixModelIdByProductAndModelId($productId, $modelId);
            if (!$rewixId && $rewixProduct > 0) {
                $this->logger->logError('Order #'.$order->id.': Mixed Order!!!');
                $mixed = true;
            }
            if ( $rewixId ) {
                $rewixProduct++;
                $orderedQty = (int) $line['product_quantity'];
                $item = $item_list->addChild('item');
                $item->addChild('price_taxable', $line['price']);
                $item->addChild('price_currency', $currency->iso_code);
                $item->addChild('stock_id', $rewixId);
                $item->addChild('quantity', $orderedQty);
                $this->logger->logDebug('Creating bdroppy order with model ID#'.$rewixId.' with quantity '.$orderedQty);
            }
        }
        if ($rewixProduct == 0) {
            return false;
        }
        if ( $mixed ){
            $this->logger->logError( 'Order #' . $order->get_order_number() . ': Mixed Order!!!' );
            return false;
        }

        $shippingAddress = new Address($order->id_address_delivery);
        $customer = new Customer((int)($shippingAddress->id_customer));
        $recipient_details = $xmlOrder->addChild('recipient_details');
        $email = $recipient_details->addChild( 'email', $customer->email);
        $recipient_details->addChild('recipient', $shippingAddress->firstname.' '.$shippingAddress->lastname);
        $recipient_details->addChild('careof', $shippingAddress->company);
        $cfpiva = $recipient_details->addChild( 'cfpiva' );
        $customer_key = $recipient_details->addChild('customer_key', (int)($order->id_customer));
        $notes = $recipient_details->addChild('notes', $order->getFirstMessage());

        $phone = $recipient_details->addChild('phone');
        $phone_prifix = $phone->addChild( 'prefix');
        $phone->addChild('number', $shippingAddress->phone);

        $address = $recipient_details->addChild('address');
        $street_type = $address->addChild( 'street_type' );
        $address->addChild('street_name', $shippingAddress->address1 . ' ' . $shippingAddress->address2);
        $address_number = $address->addChild( 'address_number' );
        $address->addChild('zip', $shippingAddress->postcode);
        $address->addChild('city', $shippingAddress->city);

        $state = new State($shippingAddress->id_state);
        $address->addChild('province', $state->iso_code);

        $country = new Country($shippingAddress->id_country);
        $address->addChild('countrycode', $country->iso_code);

        $xmlText = $xml->asXML();

        $url = Configuration::get('BDROPPY_API_URL') . '/restful/ghost/orders/0/dropshipping';
        $api_token = Configuration::get('BDROPPY_TOKEN');
        $header = "Authorization: Bearer " . $api_token;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml','Accept: application/xml', $header));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlText);
        $data = curl_exec($ch);
        $httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );
        $reader = new XMLReader();
        $reader->xml($data);
        $reader->read();
        $rewixOrder = json_decode($data);

        if (!$this->handleCurlError($httpCode)) {
            return false;
        }
        $this->logger->logInfo('Rewix order key: ' . $rewix_order_key . ' ' . $data );

        $url = Configuration::get('BDROPPY_API_URL')  . '/restful/ghost/clientorders/clientkey/'.$rewix_order_key;
        $api_token = Configuration::get('BDROPPY_TOKEN');
        $header = "Authorization: Bearer " . $api_token;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/xml', $header));
        $data = curl_exec( $ch );

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        //echo "url : $url<br>apiKey : $username<br>password : $password<br>httpCode : $httpCode<br>data : $data";die;
        if ($httpCode == 401) {
            $this->logger->logError('Send dropshipping order: UNAUTHORIZED!!');
            return false;
        } elseif ($httpCode == 500) {
            $this->logger->logError('Exception: Order #'.$order->id.' does not exists on rewix platform');
            $this->logger->logError('Bdroppy operation for order #'.$order->id.' failed!!');

            $association    = new BdroppyRemoteOrder();
            $association->rewix_order_key = $rewix_order_key;
            $association->rewix_order_id = (int) 0;
            $association->ps_order_id = (int) $order->id;
            $association->status = (int) BdroppyRemoteOrder::STATUS_FAILED;
            $association->save();
            return false;
        } elseif ($httpCode != 200) {
            $this->logger->logError('Send dropshipping order - Url : ' . $rewix_order_key. ' - ERROR ' . $httpCode);
            return false;
        }

        $this->logger->logInfo('Bdroppy order created successfully');

        $reader = new XMLReader();
        $reader->xml($data);
        $doc = new DOMDocument('1.0', 'UTF-8');

        while ($reader -> read()) {
            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'order') {
                $xmlOrder = simplexml_import_dom($doc -> importNode($reader -> expand(), true));
                $rewixOrderId   = (int) $xmlOrder->order_id;
                $status         = (int) $xmlOrder->status;
                $orderId        = (int) $order->id;
                $association    = new BdroppyRemoteOrder();
                $association->rewix_order_key = $rewix_order_key;
                $association->rewix_order_id = $rewixOrderId;
                $association->ps_order_id = $orderId;
                $association->status = $status;
                $association->save();
                $this->logger->logInfo('Entry (' . $rewixOrderId . ',' . $orderId . ') in association table created' );
                $this->logger->logInfo('Supplier order ' . $rewixOrderId . ' created successfully');
            }
        }
    }

    private function handleCurlError($httpCode)
    {
        if ($httpCode == 401) {
            $this->logger->logError('UNAUTHORIZED!!');
            Tools::displayError('You are NOT authorized to access this service. <br/> Please check your configuration in System -> Configuration or contact your supplier.');
            return false;
        } elseif ($httpCode == 0) {
            $this->logger->logError('HTTP Error 0!!');
            Tools::displayError('There has been an error executing the request.<br/> Please check your configuration in System -> Configuration');
            return false;
        } elseif ($httpCode != 200) {
            $this->logger->logError('HTTP Error '.$httpCode.'!!');
            Tools::displayError('There has been an error executing the request.<br/> HTTP Error Code: '.$httpCode);
            return false;
        }
        return true;
    }

    public function setGrowingOrderId($orderId)
    {
        Configuration::updateValue(BdroppyConfigKeys::GROWING_ORDER_ID, $orderId);
    }

    public function getPendingQtyByRewixModel($modelId)
    {
        return $this->getPendingQty($this->getRewixModelId($modelId));
    }

    public function getPendingQty($productId)
    {

        if (is_null($this->pendingCache)) {
            $query = 'select od.product_id, sum(od.product_quantity) as ordered_qty ' .
                'from `'._DB_PREFIX_.'order_detail` od ' .
                'where od.id_order in (select id_order from `'._DB_PREFIX_.'orders` where current_state in (select value from `'._DB_PREFIX_.'configuration` where name in (\'PS_OS_CHEQUE\', \'PS_OS_PAYMENT\', \'PS_OS_BANKWIRE\', \'PS_OS_WS_PAYMENT\', \'PS_OS_COD_VALIDATION\') )) ' .
                'and od.id_order not in (select x.ps_order_id from `'._DB_PREFIX_.'bdroppy_remoteorder` x) ' .
                'group by od.product_id ';
            $this->pendingCache = Db::getInstance()->ExecuteS($query);
        }

        foreach ($this->pendingCache as $pendingItem) {
            if ($pendingItem['product_id'] == $productId) {
                return (int) $pendingItem['ordered_qty'];
            }
        }
        return 0;
    }

    private function getPsModelId($modelId)
    {
        $query = 'select p.ps_product_id ' .
            'from `'._DB_PREFIX_.'bdroppy_remoteproduct` p ' .
            'where p.rewix_product_id = ' . (int) $modelId;

        $rewixProducts = Db::getInstance()->ExecuteS($query);

        foreach ($rewixProducts as $rewixProduct) {
            return $rewixProduct['ps_product_id'];
        }

        $this->logger->logError($modelId . " not found.");
    }

    private function getRewixModelId($modelId)
    {
        $query = 'select p.rewix_product_id ' .
            'from `'._DB_PREFIX_.'bdroppy_remoteproduct` p ' .
            'where p.ps_product_id = ' . (int) $modelId;

        $products = Db::getInstance()->ExecuteS($query);

        foreach ($products as $product) {
            return $product['rewix_product_id'];
        }

        $this->logger->logError("Rewix " . $modelId . " not found.");
    }

    private function getProductNameFromRewixModelId($modelId)
    {
        $query = 'select p.rewix_product_id ' .
            'from `'._DB_PREFIX_.'bdroppy_remoteproduct` p ' .
            'where p.ps_product_id = ' . (int) $modelId;

        $products = Db::getInstance()->ExecuteS($query);

        foreach ($products as $product) {
            $prd = new Product($product['rewix_product_id']);
            if($prd)
                return $prd->name;
        }

        $this->logger->logError("Rewix " . $modelId . " not found.");
    }

    public function getProcessingQtyByRewixModel($modelId)
    {
        return $this->getProcessingQty($this->getRewixModelId($modelId));
    }

    public function getProcessingQty($productId)
    {
        if (is_null($this->pendingCache)) {
            $query = 'select od.product_id, sum(od.product_quantity) as ordered_qty ' .
                'from `'._DB_PREFIX_.'order_detail` od ' .
                'where od.id_order in (select id_order from `'._DB_PREFIX_.'orders` where current_state in (select value from `'._DB_PREFIX_.'configuration` where name in (\'PS_OS_PREPARATION\') )) ' .
                'and od.id_order not in (select r.ps_order_id from `'._DB_PREFIX_.'bdroppy_remoteorder` r) ' .
                'group by od.product_id ';
            $this->pendingCache = Db::getInstance()->ExecuteS($query);
        }

        foreach ($this->pendingCache as $pendingItem) {
            if ($pendingItem['product_id'] == $productId) {
                return (int) $pendingItem['ordered_qty'];
            }
        }
        return 0;
    }

    public function syncBookedProducts()
    {
        $bookedProducts = $this->getGrowingOrderProducts();

        $this->logger->logDebug('Syncing booked products ');

        $locked = 0;
        $operations = array();
        if ($bookedProducts) {
            foreach ($bookedProducts as $bookedProduct) {
                $productId = BdroppyRemoteCombination::getIdByRewixId($bookedProduct['stock_id']);
                if ($productId > 0) {
                    $locked = $bookedProduct['locked'];
                    //$available = $bookedProduct['available'];

                    $processingQty = $this->getProcessingQty($productId);
                    $pendingQty = $this->getPendingQty($productId);

                    if ($processingQty+$pendingQty > 0 || $locked > 0) {
                        $operation = array(
                            'type' => self::SOLD_API_SET_OP,
                            'model_id' => $bookedProduct['stock_id'],
                            'qty' => $processingQty + $pendingQty
                        );
                        $operations[] = $operation;
                    }
                }
            }

            $this->logger->logDebug('Applying following operations to growing order ' . print_r($operations, true));
            if (sizeof($operations) > 0) {
                $this->modifyGrowingOrder($operations);
            }
        }

        return true;
    }

    public function sendMissingOrders()
    {
        $orderIds = BdroppyRemoteOrder::getMissingOrdersId();
        foreach ($orderIds as $orderId) {
            $order = new Order(isset($orderId['id_order']) ? $orderId['id_order'] : $orderId);
            $this->sendBdroppyOrder($order);
        }
    }

    public function getGrowingOrderProducts()
    {
        $url = Configuration::get('BDROPPY_API_URL')  . '/restful/ghost/orders/dropshipping/locked/';
        $this->logger->logDebug('Retrieving growing order ' . $url);
        $api_token = Configuration::get('BDROPPY_TOKEN');
        $header = "Authorization: Bearer " . $api_token;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml','Accept: application/xml', $header));
        $data = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$this->handleCurlError($httpCode)) {
            return false;
        }

        $reader = new XMLReader();
        $reader->xml($data);

        //$doc = new DOMDocument('1.0', 'UTF-8');
        $reader -> read();
        $this->setGrowingOrderId($reader -> getAttribute('order_id'));

        $bookedProducts = array();

        while ($reader -> read()) {
            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'model') {
                $bookedProducts[] = array(
                    'stock_id' => $reader->getAttribute('stock_id'),
                    'locked' => $reader->getAttribute('locked'),
                    'available' => $reader->getAttribute('available')
                );
            }
        }

        return $bookedProducts;
    }
}