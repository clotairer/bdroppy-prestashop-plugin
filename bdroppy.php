<?php
/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once dirname(__FILE__) . '/classes/ConfigKeys.php';
include_once dirname(__FILE__) . '/classes/ImportTools.php';
include_once dirname(__FILE__) . '/classes/RemoteProduct.php';
include_once dirname(__FILE__) . '/classes/RemoteCategory.php';
include_once dirname(__FILE__) . '/classes/RewixApi.php';

class Bdroppy extends Module
{
    protected $config_form = false;
    private $errors = null;
    private $logger;
    private $orderLogger;
    public $tab = null;

    public function __construct()
    {
        $this->name = 'bdroppy';
        $this->tab = '';
        $this->version = '2.0.0';
        $this->author = 'Hamid Isaac';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Bdroppy');
        $this->description = $this->l('Bdroppy of Brandsdistributions');

        $this->confirmUninstall = $this->l('Are you sure you want to delete the Bdroppy module?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->errors = array();
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function installTabs()
    {
        $languages = Language::getLanguages();
        // Install Tabs:
        // parent tab
        $parentTab = new Tab();
        foreach ($languages as $lang) {
            $parentTab->name[$lang['id_lang']] = $this->l('Bdroppy');
        }
        $parentTab->class_name = 'AdminBdroppy';
        $parentTab->id_parent = 0; // Home tab
        $parentTab->module = $this->name;
        $parentTab->add();

        // child tab settings
        $importTab = new Tab();
        foreach ($languages as $lang) {
            $importTab->name[$lang['id_lang']] = $this->l('Settings');
        }
        $importTab->class_name = 'AdminSettingsBdroppy';
        $importTab->id_parent = $parentTab->id;
        $importTab->module = $this->name;
        $importTab->add();
    }

    public function installFeatures()
    {
        $lngSize = [];
        $lngSize['it'] = 'Taglia';
        $lngSize['en'] = 'Size';
        $lngSize['gb'] = 'Size';
        $lngSize['fr'] = 'Taille';
        $lngSize['pl'] = 'Rozmiar';
        $lngSize['es'] = 'Talla';
        $lngSize['de'] = 'Größe';
        $lngSize['ru'] = 'Размер';
        $lngSize['nl'] = 'Grootte';
        $lngSize['ro'] = 'Mărimea';
        $lngSize['et'] = 'Suurus';
        $lngSize['hu'] = 'Méret';
        $lngSize['sv'] = 'Storlek';
        $lngSize['sk'] = 'veľkosť';
        $lngSize['cs'] = 'Velikost';
        $lngSize['pt'] = 'Tamanho';
        $lngSize['fi'] = 'Koko';
        $lngSize['bg'] = 'размер';
        $lngSize['da'] = 'Størrelse';
        $lngSize['lt'] = 'Dydis';

        $lngGender = [];
        $lngGender['it'] = 'Genere';
        $lngGender['en'] = 'Gender';
        $lngGender['gb'] = 'Gender';
        $lngGender['fr'] = 'Le sexe';
        $lngGender['pl'] = 'Płeć';
        $lngGender['es'] = 'Género';
        $lngGender['de'] = 'Geschlecht';
        $lngGender['ru'] = 'Пол';
        $lngGender['nl'] = 'Geslacht';
        $lngGender['ro'] = 'Sex';
        $lngGender['et'] = 'Sugu';
        $lngGender['hu'] = 'Nem';
        $lngGender['sv'] = 'Kön';
        $lngGender['sk'] = 'Rod';
        $lngGender['cs'] = 'Rod';
        $lngGender['pt'] = 'Gênero';
        $lngGender['fi'] = 'sukupuoli';
        $lngGender['bg'] = 'пол';
        $lngGender['da'] = 'Køn';
        $lngGender['lt'] = 'Lytis';

        $lngColor = [];
        $lngColor['it'] = 'Colore';
        $lngColor['en'] = 'Color';
        $lngColor['gb'] = 'Color';
        $lngColor['fr'] = 'Couleur';
        $lngColor['pl'] = 'Kolor';
        $lngColor['es'] = 'Color';
        $lngColor['de'] = 'Farbe';
        $lngColor['ru'] = 'цвет';
        $lngColor['nl'] = 'Kleur';
        $lngColor['ro'] = 'Culoare';
        $lngColor['et'] = 'Värv';
        $lngColor['hu'] = 'Szín';
        $lngColor['sv'] = 'Färg';
        $lngColor['sk'] = 'Farba';
        $lngColor['cs'] = 'Barva';
        $lngColor['pt'] = 'Cor';
        $lngColor['fi'] = 'Väri';
        $lngColor['bg'] = 'цвят';
        $lngColor['da'] = 'Farve';
        $lngColor['lt'] = 'Spalva';

        $lngSeason = [];
        $lngSeason['it'] = 'Stagione';
        $lngSeason['en'] = 'Season';
        $lngSeason['gb'] = 'Season';
        $lngSeason['fr'] = 'Saison';
        $lngSeason['pl'] = 'Pora roku';
        $lngSeason['es'] = 'Temporada';
        $lngSeason['de'] = 'Jahreszeit';
        $lngSeason['ru'] = 'Время года';
        $lngSeason['nl'] = 'Seizoen';
        $lngSeason['ro'] = 'Sezon';
        $lngSeason['et'] = 'Hooaeg';
        $lngSeason['hu'] = 'Évszak';
        $lngSeason['sv'] = 'Säsong';
        $lngSeason['sk'] = 'Sezóna';
        $lngSeason['cs'] = 'Sezóna';
        $lngSeason['pt'] = 'Estação';
        $lngSeason['fi'] = 'Kausi';
        $lngSeason['bg'] = 'сезон';
        $lngSeason['da'] = 'Sæson';
        $lngSeason['lt'] = 'Sezonas';

        $flgSize = true;
        $flgGender = true;
        $flgColor = true;
        $flgSeason = true;
        $languages = Language::getLanguages();
        $default_language = Language::getLanguage(Configuration::get('PS_LANG_DEFAULT'));
        $features = Feature::getFeatures($default_language['id_lang']);
        foreach ($features as $feature) {
            if ($feature['name'] == $lngSize[$default_language['iso_code']])
                $flgSize = false;
            if ($feature['name'] == $lngGender[$default_language['iso_code']])
                $flgGender = false;
            if ($feature['name'] == $lngColor[$default_language['iso_code']])
                $flgColor = false;
            if ($feature['name'] == $lngSeason[$default_language['iso_code']])
                $flgSeason = false;
        }
        if ($flgSize) {
            $feature = new Feature();
            foreach ($languages as $language)
                $feature->name[$language['id_lang']] = $lngSize[$language['iso_code']];
            $feature->add();
        }
        if ($flgGender) {
            $feature = new Feature();
            foreach ($languages as $language)
                $feature->name[$language['id_lang']] = $lngGender[$language['iso_code']];
            $feature->add();
        }
        if ($flgColor) {
            $feature = new Feature();
            foreach ($languages as $language)
                $feature->name[$language['id_lang']] = $lngColor[$language['iso_code']];
            $feature->add();
        }
        if ($flgSeason) {
            $feature = new Feature();
            foreach ($languages as $language)
                $feature->name[$language['id_lang']] = $lngSeason[$language['iso_code']];
            $feature->add();
        }
    }

    public function installAttributes()
    {
        $lngSize = [];
        $lngSize['it'] = 'Taglia';
        $lngSize['en'] = 'Size';
        $lngSize['gb'] = 'Size';
        $lngSize['fr'] = 'Taille';
        $lngSize['pl'] = 'Rozmiar';
        $lngSize['es'] = 'Talla';
        $lngSize['de'] = 'Größe';
        $lngSize['ru'] = 'Размер';
        $lngSize['nl'] = 'Grootte';
        $lngSize['ro'] = 'Mărimea';
        $lngSize['et'] = 'Suurus';
        $lngSize['hu'] = 'Méret';
        $lngSize['sv'] = 'Storlek';
        $lngSize['sk'] = 'veľkosť';
        $lngSize['cs'] = 'Velikost';
        $lngSize['pt'] = 'Tamanho';
        $lngSize['fi'] = 'Koko';
        $lngSize['bg'] = 'размер';
        $lngSize['da'] = 'Størrelse';
        $lngSize['lt'] = 'Dydis';

        $lngGender = [];
        $lngGender['it'] = 'Genere';
        $lngGender['en'] = 'Gender';
        $lngGender['gb'] = 'Gender';
        $lngGender['fr'] = 'Le sexe';
        $lngGender['pl'] = 'Płeć';
        $lngGender['es'] = 'Género';
        $lngGender['de'] = 'Geschlecht';
        $lngGender['ru'] = 'Пол';
        $lngGender['nl'] = 'Geslacht';
        $lngGender['ro'] = 'Sex';
        $lngGender['et'] = 'Sugu';
        $lngGender['hu'] = 'Nem';
        $lngGender['sv'] = 'Kön';
        $lngGender['sk'] = 'Rod';
        $lngGender['cs'] = 'Rod';
        $lngGender['pt'] = 'Gênero';
        $lngGender['fi'] = 'sukupuoli';
        $lngGender['bg'] = 'пол';
        $lngGender['da'] = 'Køn';
        $lngGender['lt'] = 'Lytis';

        $lngColor = [];
        $lngColor['it'] = 'Colore';
        $lngColor['en'] = 'Color';
        $lngColor['gb'] = 'Color';
        $lngColor['fr'] = 'Couleur';
        $lngColor['pl'] = 'Kolor';
        $lngColor['es'] = 'Color';
        $lngColor['de'] = 'Farbe';
        $lngColor['ru'] = 'цвет';
        $lngColor['nl'] = 'Kleur';
        $lngColor['ro'] = 'Culoare';
        $lngColor['et'] = 'Värv';
        $lngColor['hu'] = 'Szín';
        $lngColor['sv'] = 'Färg';
        $lngColor['sk'] = 'Farba';
        $lngColor['cs'] = 'Barva';
        $lngColor['pt'] = 'Cor';
        $lngColor['fi'] = 'Väri';
        $lngColor['bg'] = 'цвят';
        $lngColor['da'] = 'Farve';
        $lngColor['lt'] = 'Spalva';

        $lngSeason = [];
        $lngSeason['it'] = 'Stagione';
        $lngSeason['en'] = 'Season';
        $lngSeason['gb'] = 'Season';
        $lngSeason['fr'] = 'Saison';
        $lngSeason['pl'] = 'Pora roku';
        $lngSeason['es'] = 'Temporada';
        $lngSeason['de'] = 'Jahreszeit';
        $lngSeason['ru'] = 'Время года';
        $lngSeason['nl'] = 'Seizoen';
        $lngSeason['ro'] = 'Sezon';
        $lngSeason['et'] = 'Hooaeg';
        $lngSeason['hu'] = 'Évszak';
        $lngSeason['sv'] = 'Säsong';
        $lngSeason['sk'] = 'Sezóna';
        $lngSeason['cs'] = 'Sezóna';
        $lngSeason['pt'] = 'Estação';
        $lngSeason['fi'] = 'Kausi';
        $lngSeason['bg'] = 'сезон';
        $lngSeason['da'] = 'Sæson';
        $lngSeason['lt'] = 'Sezonas';

        $flgSize = true;
        $flgGender = true;
        $flgColor = true;
        $flgSeason = true;
        $languages = Language::getLanguages();
        $default_language = Language::getLanguage(Configuration::get('PS_LANG_DEFAULT'));
        $attributes = AttributeGroup::getAttributesGroups($default_language['id_lang']);
        foreach ($attributes as $attribute) {
            if ($attribute['name'] == $lngSize[$default_language['iso_code']])
                $flgSize = false;
            if ($attribute['name'] == $lngGender[$default_language['iso_code']])
                $flgGender = false;
            if ($attribute['name'] == $lngColor[$default_language['iso_code']])
                $flgColor = false;
            if ($attribute['name'] == $lngSeason[$default_language['iso_code']])
                $flgSeason = false;
        }
        if ($flgSize) {
            $newGroup = new AttributeGroup();
            foreach ($languages as $lang) {
                $newGroup->name[$lang['id_lang']] = $lngSize[$lang['iso_code']];
                $newGroup->public_name[$lang['id_lang']] = $lngSize[$lang['iso_code']];
            }
            $newGroup->group_type = 'select';
            $newGroup->save();
        }

        if ($flgGender) {
            $newGroup = new AttributeGroup();
            foreach ($languages as $lang) {
                $newGroup->name[$lang['id_lang']] = $lngGender[$lang['iso_code']];
                $newGroup->public_name[$lang['id_lang']] = $lngGender[$lang['iso_code']];
            }
            $newGroup->group_type = 'select';
            $newGroup->save();
        }

        if ($flgColor) {
            $newGroup = new AttributeGroup();
            foreach ($languages as $lang) {
                $newGroup->name[$lang['id_lang']] = $lngColor[$lang['iso_code']];
                $newGroup->public_name[$lang['id_lang']] = $lngColor[$lang['iso_code']];
            }
            $newGroup->group_type = 'color';
            $newGroup->save();
        }

        if ($flgSeason) {
            $newGroup = new AttributeGroup();
            foreach ($languages as $lang) {
                $newGroup->name[$lang['id_lang']] = $lngSeason[$lang['iso_code']];
                $newGroup->public_name[$lang['id_lang']] = $lngSeason[$lang['iso_code']];
            }
            $newGroup->group_type = 'select';
            $newGroup->save();
        }
    }

    public function install()
    {
        // make log folder
        if (!file_exists(_PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $this->name . DIRECTORY_SEPARATOR . 'log')) {
            mkdir(_PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'log', 0755, true);
        }

        $this->installAttributes();
        $this->installFeatures();
        $this->installTabs();

        //Init default value:

        include(dirname(__FILE__) . '/sql/install.php');

        if (!parent::install() ||
            !$this->registerHook('header') &&
            !$this->registerHook('displayBackOfficeHeader') ||
            !$this->registerHook('actionProductDelete') ||
            !$this->registerHook('actionCategoryDelete') ||
            !$this->registerHook('actionPaymentConfirmation') ||
            !$this->registerHook('actionAdminProductsListingFieldsModifier') ||
            !$this->registerHook('actionObjectOrderAddBefore')
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        // Uninstall Tabs
        $moduleTabs = Tab::getCollectionFromModule($this->name);
        if (!empty($moduleTabs)) {
            foreach ($moduleTabs as $moduleTab) {
                $moduleTab->delete();
            }
        }

        //include(dirname(__FILE__).'/sql/uninstall.php');

        if (!parent::uninstall()) {
            return false;
        }

        return true;
    }

    public function getCronURL()
    {
        return _PS_BASE_URL_ . __PS_BASE_URI__ . "modules/" . $this->name . "/cron.php";
    }

    private function getCatalogs()
    {
        $catalogs = [];
        $catalogs[0] = $this->l('Please Select', 'main');
        $rewixApi = new BdroppyRewixApi();
        $res = $rewixApi->getUserCatalogs();
        if ($res['catalogs'])
            $catalogs[-1] = 'No Catalog';
        foreach ($res['catalogs'] as $r) {
            $r = $rewixApi->getCatalogById2($r->_id);
            $catalogs[$r->_id] = isset($r->name) ? $r->name . " ( $r->currency ) ( " . count($r->ids) . " products )" : null;
        }
        $ret['http_code'] = $res['http_code'];
        $ret['catalogs'] = $catalogs;
        return $ret;
    }

    public function getCronCommand()
    {
        $result = '"' . _PS_MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR . 'cron.php" ';
        return $result;
    }

    public function getOrders()
    {
        $status = [
            '0' => ['value' => 'PENDING', 'desc' => 'User is managing the cart'],
            '1' => ['value' => 'MONEY WAITING', 'desc' => 'Awaiting for payment gateway response'],
            '2' => ['value' => 'TO DISPATCH	', 'desc' => 'Ready to be dispatched'],
            '3' => ['value' => 'DISPATCHED', 'desc' => 'Shipment has been picked up by carrier'],
            '5' => ['value' => 'BOOKED', 'desc' => 'Order created by API Acquire or booked by bank transfer'],
            '2000' => ['value' => 'CANCELLED', 'desc' => 'Order cancelled'],
            '2002' => ['value' => 'VERIFY FAILED', 'desc' => 'Payment was not accepted by payment gateway'],
            '3001' => ['value' => 'WORKING ON', 'desc' => 'Logistics office is working on the order'],
            '3002' => ['value' => 'READY', 'desc' => 'Order is ready for pick up'],
            '5003' => ['value' => 'DROPSHIPPER GROWING', 'desc' => 'Virtual order for growing cart'],
        ];
        $logs = array();
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'bdroppy_remoteorder;';
        if ($results = Db::getInstance()->ExecuteS($sql)) {
            foreach ($results as $row) {
                $row['status_value'] = $status[$row['status']]['value'];
                $row['status_desc'] = $status[$row['status']]['desc'];
                array_push($logs, $row);
            }
        }
        return $logs;
    }

    public function paginateUsers($users, $page = 1, $pagination = 20)
    {
        if (count($users) > $pagination)
            $users = array_slice($users, $pagination * ($page - 1), $pagination);

        return $users;
    }

    protected function renderOrdersList()
    {
        $fields_list = array(

            'id' => array(
                'title' => $this->l('ID'),
                'search' => false,
                'type' => 'text',
                'width' => 50,
            ),
            'rewix_order_id' => array(
                'title' => $this->l('Bdroppy Order ID'),
                'search' => false,
                'type' => 'text',
                'width' => 50,
            ),
            'rewix_order_key' => array(
                'title' => $this->l('Order Key'),
                'search' => false,
                'type' => 'text',
                'width' => 100,
            ),
            'ps_order_id' => array(
                'title' => $this->l('Prestashop Order ID'),
                'search' => false,
                'type' => 'text',
                'width' => 200,
            ),
            'status_value' => array(
                'title' => $this->l('Status'),
                'search' => false,
                'type' => 'text',
                'width' => 200,
            ),
            'status_desc' => array(
                'title' => $this->l('Description'),
                'search' => false,
                'type' => 'text',
                'width' => 200,
            )
        );

        $helper_list = new HelperList();
        $helper_list->module = $this;
        $helper_list->title = $this->l('Orders List');
        $helper_list->shopLinkType = '';
        $helper_list->no_link = true;
        $helper_list->show_toolbar = true;
        $helper_list->simple_header = false;
        $helper_list->identifier = 'id';
        $helper_list->table = 'merged';
        $helper_list->token = Tools::getAdminTokenLite('AdminModules');
        $helper_list->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;
        $this->_helperlist = $helper_list;

        /* Retrieve list data */
        $users = $this->getOrders();
        $helper_list->listTotal = count($users);

        if (Tools::getValue('submitFilter' . $helper_list->table))
            $this->tab = 'orders';
        /* Paginate the result */
        $page = ($page = Tools::getValue('submitFilter' . $helper_list->table)) ? $page : 1;
        $pagination = ($pagination = Tools::getValue($helper_list->table . '_pagination')) ? $pagination : 20;
        $users = $this->paginateUsers($users, $page, $pagination);

        return $helper_list->generateList($users, $fields_list);
    }

    public function getContent()
    {
        $output = '';
        $saved = false;
        $connectCatalog = false;
        $connectCronTxt = '';
        $cron_url = $this->getCronURL();

        // check if a FORM was submitted using the 'Save Config' button
        if (Tools::isSubmit('submitApiConfig')) {
            $apiToken = '';
            $apiUrl = (string)Tools::getValue('bdroppy_api_url');
            $apiKey = (string)Tools::getValue('bdroppy_api_key');
            $apiPassword = (string)Tools::getValue('bdroppy_api_password');
            //$apiToken = (string)Tools::getValue('bdroppy_token');

            Configuration::updateValue('BDROPPY_API_URL', $apiUrl);
            Configuration::updateValue('BDROPPY_API_KEY', $apiKey);
            Configuration::updateValue('BDROPPY_API_PASSWORD', $apiPassword);
            Configuration::updateValue('BDROPPY_TOKEN', '');
            if ($apiKey != Configuration::get('BDROPPY_API_KEY') || $apiPassword != Configuration::get('BDROPPY_API_PASSWORD') || Configuration::get('BDROPPY_TOKEN') == '') {
                Configuration::updateValue('BDROPPY_CATALOG', '');
                $rewixApi = new BdroppyRewixApi();
                $res = $rewixApi->loginUser();
                if ($res['http_code'] == 200) {
                    Configuration::updateValue('BDROPPY_TOKEN', $res['data']->token);
                }
            }
            $this->tab = 'configurations';
            $saved = true;
        } elseif (Tools::isSubmit('submitCatalogConfig')) {
            $bdroppy_catalog_changed = false;
            $bdroppy_catalog = (string)Tools::getValue('bdroppy_catalog');
            if(Configuration::get('BDROPPY_CATALOG') != $bdroppy_catalog)
                $bdroppy_catalog_changed = true;

            Configuration::updateValue('BDROPPY_CATALOG_CHANGED', $bdroppy_catalog_changed);

            Configuration::updateValue('BDROPPY_CATALOG', $bdroppy_catalog);

            if ($bdroppy_catalog != '0' && $bdroppy_catalog != '-1')
                Configuration::updateValue('BDROPPY_CATALOG_BU', $bdroppy_catalog);

            $bdroppy_active_product = (string)Tools::getValue('bdroppy_active_product');
            Configuration::updateValue('BDROPPY_ACTIVE_PRODUCT', $bdroppy_active_product);

            $bdroppy_custom_feature = (string)Tools::getValue('bdroppy_custom_feature');
            Configuration::updateValue('BDROPPY_CUSTOM_FEATURE', $bdroppy_custom_feature);

            $bdroppy_size = (string)Tools::getValue('bdroppy_size');
            Configuration::updateValue('BDROPPY_SIZE', $bdroppy_size);

            $bdroppy_gender = (string)Tools::getValue('bdroppy_gender');
            Configuration::updateValue('BDROPPY_GENDER', $bdroppy_gender);

            $bdroppy_color = (string)Tools::getValue('bdroppy_color');
            Configuration::updateValue('BDROPPY_COLOR', $bdroppy_color);

            $bdroppy_season = (string)Tools::getValue('bdroppy_season');
            Configuration::updateValue('BDROPPY_SEASON', $bdroppy_season);

            $bdroppy_category_structure = (string)Tools::getValue('bdroppy_category_structure');
            Configuration::updateValue('BDROPPY_CATEGORY_STRUCTURE', $bdroppy_category_structure);

            $bdroppy_import_image = (string)Tools::getValue('bdroppy_import_image');
            Configuration::updateValue('BDROPPY_IMPORT_IMAGE', $bdroppy_import_image);

            $bdroppy_reimport_image = (int)Tools::getValue('bdroppy_reimport_image');
            Configuration::updateValue('BDROPPY_REIMPORT_IMAGE', $bdroppy_reimport_image);

            $bdroppy_tax_rate = (string)Tools::getValue('bdroppy_tax_rate');
            Configuration::updateValue('BDROPPY_TAX_RATE', $bdroppy_tax_rate);

            $bdroppy_tax_rule = (string)Tools::getValue('bdroppy_tax_rule');
            Configuration::updateValue('BDROPPY_TAX_RULE', $bdroppy_tax_rule);

            $bdroppy_limit_count = (string)Tools::getValue('bdroppy_limit_count');
            Configuration::updateValue('BDROPPY_LIMIT_COUNT', $bdroppy_limit_count);

            $bdroppy_import_brand_to_title = (int)Tools::getValue('bdroppy_import_brand_to_title');
            Configuration::updateValue('BDROPPY_IMPORT_BRAND_TO_TITLE', $bdroppy_import_brand_to_title);

            $bdroppy_import_tag_to_title = (string)Tools::getValue('bdroppy_import_tag_to_title');
            Configuration::updateValue('BDROPPY_IMPORT_TAG_TO_TITLE', $bdroppy_import_tag_to_title);

            $bdroppy_auto_update_prices = (int)Tools::getValue('bdroppy_auto_update_prices');
            Configuration::updateValue('BDROPPY_AUTO_UPDATE_PRICES', $bdroppy_auto_update_prices);

            $rewixApi = new BdroppyRewixApi();
            $res = $rewixApi->connectUserCatalog();
            if ($res['http_code'] == 200)
                $connectCatalog = true;
            $cron = $rewixApi->setCronJob($cron_url);
            if ($cron['http_code'] == 200)
                if ($cron['data'] == 'url_already_exists')
                    $connectCronTxt = 'Your CronJob Already Added, For Change Contact Please';
                else
                    $connectCronTxt = 'CronJob Added (' . $cron_url . ')';
            $this->tab = 'my_catalogs';
            $saved = true;
        }
        $errors = "";
        $confirmations = "";
        // Control on variable putted in!
        if (isset($this->errors) && count($this->errors)) {
            $errors = $this->displayError(implode('<br />', $this->errors));
        } else {
            if ($saved) {
                $confirmations = $this->displayConfirmation($this->l('Settings updated'));
            }
            if ($connectCatalog) {
                $confirmations .= $this->displayConfirmation($this->l('Catalog Connected'));
            }
        }
        if ($connectCronTxt != '')
            $confirmations .= $this->displayConfirmation($this->l($connectCronTxt));

        $res = AttributeGroup::getAttributesGroups($this->context->language->id);
        //$res = Feature::getFeatures($this->context->language->id);
        $attributes = array(
            '0' => $this->l('Select', 'main'),
        );
        foreach ($res as $attribute) {
            $attributes[$attribute['id_attribute_group']] = $attribute['name'];
        }

        $tax_rules = [];
        $taxes = TaxRulesGroup::getTaxRulesGroups();
        foreach ($taxes as $tax) {
            $tax_rules[$tax['id_tax_rules_group']] = $tax['name'];
        }
        $tax_rates = [];
        $rates = Tax::getTaxes(Configuration::get('PS_LANG_DEFAULT'));
        foreach ($rates as $rate) {
            $tax_rates[$rate['id_tax']] = $rate['name'];
        }
        //return $output . $this->displayForm() . $this->displayPriceForm();
        $catalogs = $this->getCatalogs();

        $shopDomainSsl = Tools::getShopDomainSsl(true, true);
        $stripeBOCssUrl = $shopDomainSsl . __PS_BASE_URI__ . 'modules/' . $this->name . '/views/css/bdroppy.css';
        $base_url = "Unkown";
        $api_key = "Unkown";
        $api_token = "Unkown";
        $base_url = Configuration::get('BDROPPY_API_URL');
        $api_key = Configuration::get('BDROPPY_API_KEY');
        $api_token = Configuration::get('BDROPPY_TOKEN');
        $bdroppy_active_product = Configuration::get('BDROPPY_ACTIVE_PRODUCT');
        $bdroppy_custom_feature = Configuration::get('BDROPPY_CUSTOM_FEATURE');
        if ($bdroppy_custom_feature == '')
            $bdroppy_custom_feature = '0';
        $bdroppy_reimport_image = Configuration::get('BDROPPY_REIMPORT_IMAGE');
        if ($bdroppy_reimport_image == '')
            $bdroppy_reimport_image = '0';
        $bdroppy_import_brand_to_title = Configuration::get('BDROPPY_IMPORT_BRAND_TO_TITLE');
        $bdroppy_auto_update_prices = Configuration::get('BDROPPY_AUTO_UPDATE_PRICES');

        $txtStatus = '<span style="color: red;">Error Code : ' . $catalogs['http_code'] . '</span>';
        if (count($catalogs['catalogs']) > 1) {
            /*$rewixApi = new BdroppyRewixApi();
            $userInfo = $rewixApi->getUserInfo();
            if($userInfo['http_code'] == 200) {
                Configuration::updateValue('BDROPPY_USER_TAX', $userInfo['data']->tax);
            }*/
            $txtStatus = '<span style="color: green;">Ok</span>';
        }
        $urls = array(
            'https://dev.bdroppy.com' => $this->l('Sandbox mode', 'main'),
            'https://prod.bdroppy.com' => $this->l('Live mode', 'main')
        );
        $bdroppy_import_tag_to_title = array(
            '0' => $this->l('No Tag', 'main'),
            'color' => $this->l('Color', 'main')
        );

        $import_image = array(
            '0' => $this->l('No import', 'main'),
            '1' => $this->l('1 Picture', 'main'),
            '2' => $this->l('2 Picture', 'main'),
            '3' => $this->l('3 Picture', 'main'),
            '4' => $this->l('4 Picture', 'main'),
            'all' => $this->l('All Pictures', 'main'),
        );
        $category_structure = array(
            '1' => $this->l('Category > Subcategory', 'main'),
            '2' => $this->l('Gender > Category > Subcategory', 'main'),
        );
        $limit_counts = array(
            '' => $this->l('Select', 'main'),
            '1' => '1',
            '5' => '5',
            '10' => '10',
            '15' => '15',
            '20' => '20',
            '25' => '25',
            '30' => '30',
            '35' => '35',
            '40' => '40',
            '45' => '45',
            '50' => '50'
        );

        $iso_lang = Language::getIsoById($this->context->cookie->id_lang);
        if (!in_array($iso_lang, array('en', 'fr', 'es', 'de', 'it', 'nl', 'pl', 'pt', 'ru'))) {
            $iso_lang = 'en';
        }
        $home_url = sprintf('https://www.brandsdistribution.com', $iso_lang, urlencode($this->name));

        $queue_queued = BdroppyRemoteProduct::getCountByStatus(BdroppyRemoteProduct::SYNC_STATUS_QUEUED);
        $queue_importing = BdroppyRemoteProduct::getCountByStatus(BdroppyRemoteProduct::SYNC_STATUS_IMPORTING);
        $queue_imported = BdroppyRemoteProduct::getCountByStatus(BdroppyRemoteProduct::SYNC_STATUS_UPDATED);
        $queue_all = BdroppyRemoteProduct::getCountByStatus('');
        $renderedOrders = $this->renderOrdersList();
        if ($this->tab == '')
            $this->tab = 'configurations';
        $tplVars = array(
            'module_display_name' => $this->displayName,
            'module_version' => $this->version,
            'description_big_html' => '',
            'description' => '',
            'home_url' => $home_url,
            'urls' => $urls,
            'urls' => $urls,
            'erros' => $errors,
            'confirmations' => $confirmations,
            'module_path' => '/modules/bdroppy/',
            'base_url' => $base_url,
            'api_key' => $api_key,
            'ordersHtml' => $renderedOrders,
            'cron_command' => $this->getCronCommand(),
            'active_tab' => $this->tab,
            'api_token' => $api_token,
            'cron_url' => $cron_url,
            'catalogs' => $catalogs['catalogs'],
            'attributes' => $attributes,
            'import_image' => $import_image,
            'tax_rule' => $tax_rules,
            'tax_rate' => $tax_rates,
            'category_structure' => $category_structure,
            'stripeBOCssUrl' => $stripeBOCssUrl,
            'base_url' => $base_url,
            'api_key' => $api_key,
            'txtStatus' => $txtStatus,
            'queue_queued' => $queue_queued,
            'queue_importing' => $queue_importing,
            'queue_imported' => $queue_imported,
            'queue_all' => $queue_all,
            'limit_counts' => $limit_counts,
            'bdroppy_active_product' => $bdroppy_active_product,
            'bdroppy_custom_feature' => $bdroppy_custom_feature,
            'bdroppy_import_brand_to_title' => $bdroppy_import_brand_to_title,
            'bdroppy_reimport_image' => $bdroppy_reimport_image,
            'bdroppy_import_tag_to_title' => $bdroppy_import_tag_to_title,
            'bdroppy_auto_update_prices' => $bdroppy_auto_update_prices,
        );
        $this->context->smarty->assign($tplVars);
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output;
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'BDROPPY_LIVE_MODE' => Configuration::get('BDROPPY_LIVE_MODE', true),
            'BDROPPY_ACCOUNT_EMAIL' => Configuration::get('BDROPPY_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'BDROPPY_ACCOUNT_PASSWORD' => Configuration::get('BDROPPY_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    public function hookActionObjectOrderAddBefore($params)
    {
        if (!isset($params)) {
            return;
        }

        $rewixApi = new BdroppyRewixApi();

        try {
            return $rewixApi->validateOrder($params['cart']);
        } catch (Exception $e) {
            //$this->orderLogger->logDebug($e->getMessage());
            $this->context->controller->errors[] = $this->l($e->getMessage());
            $this->context->controller->redirectWithNotifications("index.php");
        }
    }

    /**
     * Send order to Rewix
     */
    public function hookActionPaymentConfirmation($params)
    {
        $order = new Order((int)$params['id_order']);
        $rewixApi = new BdroppyRewixApi();

        try {
            $r = $rewixApi->sendBdroppyOrder($order);
            if (isset($r['module_error'])) {
                $this->errors[] = Tools::displayError($r['message']);
                $this->context->controller->errors[] = Tools::displayError($r['message']);
                return false;
            }
            return true;
        } catch (Exception $e) {
            //$this->orderLogger->logDebug($e->getMessage());
            $this->context->controller->errors[] = $this->l($e->getMessage());
            $this->context->controller->redirectWithNotifications("index.php");
        }
    }

    public function hookActionProductDelete($params)
    {
        $id = $params['id_product'];
        $rewixId = BdroppyRemoteProduct::getRewixIdByPsId($id);
        BdroppyRemoteCombination::deleteByRewixId($rewixId);
        BdroppyRemoteProduct::deleteByPsId($id);
    }

    public function hookActionCategoryDelete($params)
    {
        $category = $params['category'];
        $subcategories = $params['deleted_children'];

        BdroppyRemoteCategory::deleteByPsId($category->id);
        foreach ($subcategories as $cat) {
            BdroppyRemoteCategory::deleteByPsId($cat->id);
        }
    }

    public function hookActionAdminProductsListingFieldsModifier($list)
    {
        if (isset($list['sql_select'])) {
            $list['sql_select']['unity'] = array(
                "table"=>"p",
                "field"=>"unity",
                "filtering"=>" %s "
            );
            $list['sql_select']['rewix_product_id'] = array(
                "table"=>"br",
                "field"=>"rewix_product_id",
                "filtering"=>" %s "
            );
            $list['sql_select']['rewix_catalog_id'] = array(
                "table"=>"br",
                "field"=>"rewix_catalog_id",
                "filtering"=>" %s "
            );
        }
        if (isset($list['sql_table'])) {
            $list['sql_table']['br'] = array(
                "table"=>"bdroppy_remoteproduct",
                "join"=>"LEFT JOIN",
                "on"=>"br.`ps_product_id` = p.`id_product`"
            );
        }
        //echo "<pre>";var_dump($list);die;
    }
}
