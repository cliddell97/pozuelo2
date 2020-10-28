<?php

namespace Imagineer\RevisionPagos\Model\Order;

class Payment extends \Magento\Sales\Model\Order\Payment
{
    public function accept()
    {
        $transactionId = $this->getLastTransId();

        /** @var \Magento\Payment\Model\Method\AbstractMethod $method */
        $method = $this->getMethodInstance();
        $method->setStore($this->getOrder()->getStoreId());
        if (true /*$method->acceptPayment($this)*/) {
            $invoice = $this->_getInvoiceForTransactionId($transactionId);
            $message = $this->_appendTransactionToMessage(
                $transactionId,
                $this->prependMessage(__('Approved the payment online.'))
            );
            $this->updateBaseAmountPaidOnlineTotal($invoice);
            $this->setOrderStateProcessing($message);
        } else {
            $message = $this->_appendTransactionToMessage(
                $transactionId,
                $this->prependMessage(__('There is no need to approve this payment.'))
            );
            $this->setOrderStatePaymentReview($message, $transactionId);
        }

        return $this;
    }

    /**
     * Accept order with payment method instance
     *
     * @param bool $isOnline
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deny($isOnline = true)
    {
        $transactionId = $isOnline ? $this->getLastTransId() : $this->getTransactionId();

        if (true /*$isOnline*/) {
            /** @var \Magento\Payment\Model\Method\AbstractMethod $method */
            $method = $this->getMethodInstance();
            $method->setStore($this->getOrder()->getStoreId());

            $result = $method->denyPayment($this);
        } else {
            $result = (bool)$this->getNotificationResult();
        }

        if (true /*$result*/) {
            $invoice = $this->_getInvoiceForTransactionId($transactionId);
            $message = $this->_appendTransactionToMessage(
                $transactionId,
                $this->prependMessage(__('Denied the payment online'))
            );
            $this->cancelInvoiceAndRegisterCancellation($invoice, $message);
        } else {
            $txt = $isOnline ?
                'There is no need to deny this payment.' : 'Registered notification about denied payment.';
            $message = $this->_appendTransactionToMessage(
                $transactionId,
                $this->prependMessage(__($txt))
            );
            $this->setOrderStatePaymentReview($message, $transactionId);
        }

        return $this;
    }

}
