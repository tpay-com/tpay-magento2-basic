<?php

namespace tpaycom\magento2basic\Controller\tpay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Registry;
use Tpay\OriginApi\Utilities\Util;
use tpaycom\magento2basic\Api\TpayInterface;
use tpaycom\magento2basic\Model\ApiFacade\CardTransaction\CardApiFacade;
use tpaycom\magento2basic\Service\TpayService;
use tpaycom\magento2basic\Service\TpayTokensService;

class CardPayment extends Action
{
    /** @var TpayService */
    protected $tpayService;

    /** @var Session */
    protected $checkoutSession;

    /** @var TpayInterface */
    private $tpay;

    /** @var TpayTokensService */
    private $tokensService;

    public function __construct(Context $context, TpayInterface $tpayModel, TpayService $tpayService, Session $checkoutSession, ModelContext $modelContext, Registry $registry, ResourceConnection $resourceConnection)
    {
        $this->tpay = $tpayModel;
        $this->tpayService = $tpayService;
        $this->checkoutSession = $checkoutSession;
        $this->tokensService = new TpayTokensService($modelContext, $registry, $resourceConnection);
        Util::$loggingEnabled = false;
        parent::__construct($context);
    }

    public function execute()
    {
        /** @var int $orderId */
        $orderId = $this->checkoutSession->getLastRealOrderId();

        if ($orderId) {
            $cardTransaction = new CardApiFacade($this->tpay, $this->tokensService, $this->tpayService);
            $redirectUrl = $cardTransaction->makeCardTransaction($orderId);

            return $this->_redirect($redirectUrl);
        }
        $this->checkoutSession->unsQuoteId();

        return $this->_redirect('magento2basic/tpay/error');
    }
}
