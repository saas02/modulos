<?php

namespace MercadoPago\Core\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;

/**
 * Return configs to Standard Method
 *
 * Class StandardConfigProvider
 *
 * @package MercadoPago\Core\Model
 */
class CustomBankTransferConfigProvider
    implements ConfigProviderInterface
{
    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $methodInstance;

    /**
     * @var string
     */
    protected $methodCode = CustomBankTransfer\Payment::CODE;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;
    protected $_coreHelper;
    protected $_productMetaData;

    /**
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        PaymentHelper $paymentHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \MercadoPago\Core\Helper\Data $coreHelper,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    )
    {
        $this->_request = $context->getRequest();
        $this->methodInstance = $paymentHelper->getMethodInstance($this->methodCode);
        $this->_checkoutSession = $checkoutSession;
        $this->_scopeConfig = $scopeConfig;
        $this->_urlBuilder = $context->getUrl();
        $this->_storeManager = $storeManager;
        $this->_assetRepo = $assetRepo;
        $this->_coreHelper = $coreHelper;
        $this->_productMetaData = $productMetadata;
    }

    /**
     * @return array
     */
    public function getConfig()
    {

        if (!$this->methodInstance->isAvailable()) {
            return [];
        }

        $paymentMethods = $this->methodInstance->getPaymentOptions();
        if (empty($paymentMethods)) {
            $this->_coreHelper->log("CustomTicketConfigProvider::getConfig - You have excluded all payment methods, the customer can not make the payment.");
        }

        $identificationTypes = $this->methodInstance->getIdentifcationTypes();

        $data = [
            'payment' => [
                $this->methodCode => [
                    'analytics_key' => $this->_coreHelper->getClientIdFromAccessToken($this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ACCESS_TOKEN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)),
                    'country' => strtoupper($this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_SITE_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)),
                    'bannerUrl' => $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_CUSTOM_BANK_TRANSFER_BANNER, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'payment_method_options' => $paymentMethods,
                    'identification_types' => $identificationTypes,
                    'success_url' => $this->methodInstance->getConfigData('order_place_redirect_url'),
                    'route' => $this->_request->getRouteName(),
                    'base_url' => $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK),
                    'loading_gif' => $this->_assetRepo->getUrl('MercadoPago_Core::images/loading.gif'),
                    'logoUrl' => $this->_assetRepo->getUrl("MercadoPago_Core::images/mp_logo.png"),
                    'platform_version' => $this->_productMetaData->getVersion(),
                    'module_version' => $this->_coreHelper->getModuleVersion()
                ]
            ]
        ];

        return $data;
    }
}