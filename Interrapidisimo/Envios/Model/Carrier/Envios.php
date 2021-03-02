<?php

namespace Interrapidisimo\Envios\Model\Carrier;

use Magento\Shipping\Model\Rate\Result;

class Envios extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements \Magento\Shipping\Model\Carrier\CarrierInterface {

    protected $_code = 'envios';

    /**     * @var \Magento\Shipping\Model\Rate\ResultFactory  */
    protected $_rateResultFactory;

    /**     * @var \Magento\Quote\Model\Quote\Address\RateResult\  MethodFactory  */
    protected $_rateMethodFactory;
    protected $_trackFactory;
    protected $_trackStatusFactory;
    protected $_request; 

    /* public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory, \Psr\Log\LoggerInterface $logger, \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory, \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory, array $data = []  ) {

      $this->_rateResultFactory = $rateResultFactory;
      $this->_rateMethodFactory = $rateMethodFactory;
      $this->_trackFactory = new \Magento\Shipping\Model\Tracking\ResultFactory() $trackFactory;
      $this->_trackStatusFactory = new \Magento\Shipping\Model\Tracking\Result\StatusFactory() $trackStatusFactory;

      parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
      } *///original  

    public function __construct(\Magento\Framework\App\Request\Http $request, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory, \Psr\Log\LoggerInterface $logger, \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory, \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory, \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory, \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory, \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory, array $data = []) {

        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_trackFactory = $trackFactory;
        $this->_trackStatusFactory = $trackStatusFactory;
        $this->_request = $request;
        
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /* 			public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory, \Psr\Log\LoggerInterface $logger, Security $xmlSecurity, \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory, \Magento\Shipping\Model\Rate\ResultFactory $rateFactory, \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory, \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory, \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory, \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory, \Magento\Directory\Model\RegionFactory $regionFactory, \Magento\Directory\Model\CountryFactory $countryFactory, \Magento\Directory\Model\CurrencyFactory $currencyFactory, \Magento\Directory\Helper\Data $directoryData, \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry, \Magento\Framework\Locale\FormatInterface $localeFormat, Config $configHelper, array $data = [])
      {
      $this->_localeFormat = $localeFormat;
      $this->configHelper = $configHelper;
      parent::__construct($scopeConfig, $rateErrorFactory, $logger, $xmlSecurity, $xmlElFactory, $rateFactory, $rateMethodFactory, $trackFactory, $trackErrorFactory, $trackStatusFactory, $regionFactory, $countryFactory, $currencyFactory, $directoryData, $stockRegistry, $data);
      } *///ejemplo

    public function collectRates(\Magento\Quote\Model\Quote\Address\RateRequest $request) {
        if (!$this->getConfigFlag('active')) {
            return false;
        }
        $result = $this->_rateResultFactory->create();        
                        
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $om->get('Magento\Customer\Model\Session');
        $storeManager = $om->create("\Magento\Store\Model\StoreManagerInterface");        
        $InfoStore = $om->create('\Magento\Store\Model\Information')->getStoreInformationObject($storeManager->getStore());
        $cityStore = $InfoStore->getData('city');
        
        if($customerSession->isLoggedIn()) {
            $cityCustomer = $customerSession->getCustomer()->getDefaultShippingAddress()->getCity();
        }else{
            $data = json_decode($this->_request->getContent(), true);
            $cityCustomer = $data['address']['city'];
        }
        
        $dataCotizador = [
            "idLocalidadOrigen" => "54003000",//$cityStore,
            "idLocalidadDestino" => "05004000",//$cityCustomer,
            "idCLiente" => \BeGateway\Settings::$idClienteCotizador,
            "homologarCiudad" => true,
            "url" => 'cotizador',
            "metodo" => 'GET'
        ];                
        
        $begateway = "\\BeGateway\\captureOperation";

        $begateway = new $begateway;

        $responseObjectCotizador = $begateway->submit($dataCotizador);
        if($responseObjectCotizador->isSuccess()){
            $serviciosCotizador = NULL;
            foreach($responseObjectCotizador->getMessage() as $servicios){                
                if(!empty($servicios) && isset($servicios->IdServicio) && $servicios->IdServicio == 3){
                    $serviciosCotizador = $servicios;
                }                
            }
        }        
        
        $fechaEntrega = !empty($serviciosCotizador) ? ' Fecha de entrega: '.(isset($serviciosCotizador->fechaEntrega) ? $serviciosCotizador->fechaEntrega : ' ---- ') : NULL ;
        
        $precio = !empty($serviciosCotizador) ? $serviciosCotizador->Precio->Valor : NULL ;
        
        
        if ($this->getConfigData('free_service')) {
            $method = $this->_rateMethodFactory->create();
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($fechaEntrega);
            $method->setMethod('inter');
            $method->setMethodTitle($this->getConfigData('free_method'));
            $method->setPrice(0);
            $method->setCost(0);
            $result->append($method);
        } else {
//            Check if express method is enabled
            if ($this->getConfigData('express_enabled')) {
                $method = $this->_rateMethodFactory->create();
                $method->setCarrier($this->_code);
                $method->setCarrierTitle($fechaEntrega);
                $method->setMethod('express');
                $method->setMethodTitle($this->getConfigData('name'));
                $method->setPrice($this->getConfigData('express_price'));
                $method->setCost($this->getConfigData('express_price'));
                $result->append($method);
            }
            //Check if business method is enabled    
            if ($this->getConfigData('business_enabled')) {
                $method = $this->_rateMethodFactory->create();
                $method->setCarrier($this->_code);
                $method->setCarrierTitle($fechaEntrega);
                $method->setMethod('business');
                $method->setMethodTitle($this->getConfigData('name'));
                $method->setPrice($this->getConfigData('business_price'));
                $method->setCost($this->getConfigData('business_price'));
                $result->append($method);
            }
        }
        return $result;
    }

    public function getAllowedMethods() {
        return ['envios' => $this->getConfigData('name')];
    }

    public function isTrackingAvailable() {
        return true;
    }

    public function getTrackingInfo($trackings) {
        $result = $this->_trackFactory->create();
        $tracking = $this->_trackStatusFactory->create();

        $tracking->setCarrier($this->_code);
        $tracking->setCarrierTitle("Interapidisimo");
        $tracking->setTracking($trackings);
        $tracking->setUrl('http://www.marketweb.com/?cn=' . $trackings); //This is tracking URL

        $result->append($tracking);

        return $tracking;
    }
}
