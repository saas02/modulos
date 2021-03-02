<?php


namespace Interrapidisimo\Credibanco\Controller\Ipn;

/**
 * Unified IPN controller for all supported beGateway Payment Methods
 * Class Index
 * @package Interrapidisimo\Credibanco\Controller\Ipn
 */
class Index extends \Interrapidisimo\Credibanco\Controller\AbstractAction
{

    /**
     * Instantiate IPN model and pass IPN request to it
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            return;
        }

        try {
          $ipn = $this->getObjectManager()->create(
              "BeGateway\\BeGateway\\Model\\Ipn\\BeGatewayIpn"
          );

          $responseBody = $ipn->handleBeGatewayNotification();
          $this->getResponse()
              ->setHeader('Content-type', 'text/html')
              ->setBody($responseBody['body'])
              ->setHttpResponseCode($responseBody['code'])
              ->sendResponse();
        } catch (\Exception $e) {
            $this->getLogger()->critical($e);
            $this->getResponse()->setHttpResponseCode(500);
        }
    }
}
