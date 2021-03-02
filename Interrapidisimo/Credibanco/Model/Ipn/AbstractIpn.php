<?php


namespace Interrapidisimo\Credibanco\Model\Ipn;

/**
 * Base IPN Handler Class
 *
 * Class AbstractIpn
 * @package Interrapidisimo\Credibanco\Model\Ipn
 */
abstract class AbstractIpn
{

    /**
     * @var \Magento\Framework\App\Action\Context
     */
    private $_context;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;
    /**
     * @var \Interrapidisimo\Credibanco\Helper\Data
     */
    private $_moduleHelper;
    /**
     * @var \Interrapidisimo\Credibanco\Model\Config
     */
    private $_configHelper;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $_orderFactory;
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender
     */
    protected $_creditMemoSender;

    /**
     * Get Payment Solution Code (used to create an instance of the Config Object)
     * @return string
     */
    abstract protected function getPaymentMethodCode();

    /**
     * Update / Create Transactions; Updates Order Status
     * @param \stdClass $responseObject
     * @return void
     */
    abstract protected function processNotification($responseObject);

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender $creditMemoSender
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Interrapidisimo\Credibanco\Helper\Data $moduleHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender $creditMemoSender,
        \Psr\Log\LoggerInterface $logger,
        \Interrapidisimo\Credibanco\Helper\Data $moduleHelper
    ) {
        $this->_context = $context;
        $this->_orderFactory = $orderFactory;
        $this->_orderSender = $orderSender;
        $this->_creditMemoSender = $creditMemoSender;
        $this->_logger = $logger;
        $this->_moduleHelper = $moduleHelper;
        $this->_configHelper =
            $this->_moduleHelper->getMethodConfig(
                $this->getPaymentMethodCode()
            );
    }

    /**
     *
     * @return null|string (null => failed; responseText => success)
     * @throws \Exception
     * @throws \BeGateway\Exceptions\InvalidArgument
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handleBeGatewayNotification()
    {
        $this->_configHelper->initGatewayClient();

        $webhook = new \BeGateway\Webhook;

        if (!$webhook->isAuthorized())
          return array(
            'body' => 'Forbidden',
            'code' => 403
          );

        if (empty($webhook->getUid())) {
          return array(
            'body' => 'Error in params',
            'code' => 422
          );
        } else {
            $this->setOrderByReconcile($webhook);

            try {
                $this->processNotification($webhook);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $comment = $this->createIpnComment(__('Exception in webhook processing: %1', $e->getMessage()), true);
                $comment->save();
                throw $e;
            }

            return array(
              'body' => 'OK',
              'code' => 200
            );
        }
    }

    /**
     * Load order
     *
     * @return \Magento\Sales\Model\Order
     * @throws \Exception
     */
    protected function getOrder()
    {
        if (!isset($this->_order) || empty($this->_order->getId())) {
            throw new \Exception('IPN-Order is not set to an instance of an object');
        }

        return $this->_order;
    }

    /**
     * Get an Instance of the Magento Payment Object
     * @return \Magento\Sales\Api\Data\OrderPaymentInterface|mixed|null
     * @throws \Exception
     */
    protected function getPayment()
    {
        return $this->getOrder()->getPayment();
    }

    /**
     * Initializes the Order Object from the transaction in the Reconcile response object
     * @param $responseObject
     * @throws \Exception
     */
    private function setOrderByReconcile($responseObject)
    {
        $transaction_id = $responseObject->getTrackingId();
        list($incrementId, $hash) = explode('_', $transaction_id);

        $this->_order = $this->getOrderFactory()->create()->loadByIncrementId(
            intval($incrementId)
        );

        if (!$this->_order->getId()) {
            throw new \Exception(sprintf('Wrong order ID: "%s".', $incrementId));
        }
    }

    /**
     * Generate an "IPN" comment with additional explanation.
     * Returns the generated comment or order status history object
     *
     * @param string|null $message
     * @param bool $addToHistory
     * @return string|\Magento\Sales\Model\Order\Status\History
     */
    protected function createIpnComment($message = null, $addToHistory = false)
    {
        if ($addToHistory && !empty($message)) {
            $message = $this->getOrder()->addStatusHistoryComment($message);
            $message->setIsCustomerNotified(null);
        }
        return $message;
    }

    /**
     * Get an instance of the Module Config Helper Object
     * @return \Interrapidisimo\Credibanco\Model\Config
     */
    protected function getConfigHelper()
    {
        return $this->_configHelper;
    }

    /**
     * Get an instance of the Magento Action Context Object
     * @return \Magento\Framework\App\Action\Context
     */
    protected function getContext()
    {
        return $this->_context;
    }

    /**
     * Get an instance of the Magento Logger Interface
     * @return \Psr\Log\LoggerInterface
     */
    protected function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Get an Instance of the Module Helper Object
     * @return \Interrapidisimo\Credibanco\Helper\Data
     */
    protected function getModuleHelper()
    {
        return $this->_moduleHelper;
    }

    /**
     * Get an Instance of the magento Order Factory Object
     * @return \Magento\Sales\Model\OrderFactory
     */
    protected function getOrderFactory()
    {
        return $this->_orderFactory;
    }

    /**
     * @param \stdClass $responseObject
     * @return bool
     */
    protected function getShouldSetCurrentTranPending($responseObject)
    {
        return
            !$responseObject->isSuccess();
    }

    /**
     * @param \stdClass $responseObject
     * @return bool
     */
    protected function getShouldCloseCurrentTransaction($responseObject)
    {
        $helper = $this->getModuleHelper();
        $voidableTransactions = [
            $helper::AUTHORIZE
        ];

        /*
         *  It the last transaction is closed, it cannot be voided
         */
        return !in_array($responseObject->getResponse()->transaction->type, $voidableTransactions);
    }
}
