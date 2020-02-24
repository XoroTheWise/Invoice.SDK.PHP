<?php
require_once "../sdk/RestClient.php";
require_once "../sdk/GET_TERMINAL.php";
require_once "../sdk/CREATE_TERMINAL.php";
require_once "../sdk/CREATE_PAYMENT.php";
require_once "../sdk/CREATE_REFUND.php";
require_once "../sdk/CLOSE_PAYMENT.php";
require_once "../sdk/common/ITEM.php";
require_once "../sdk/common/SETTINGS.php";
require_once "../sdk/common/ORDER.php";
require_once "../sdk/common/REFUND_INFO.php";
require_once "../sdk/GET_PAYMENT.php";

class PseudoProcessing
{
    const shopName = "Название магазина";
    const shopDescription = "Описание магазина";
    const terminalType = "dynamical";
    const defaultPrice = 0;

    private $restClient;
    private $paymentInfo;
    private $terminalInfo;
    private $refundInfo;

    public function init($login, $apiKey)
    {
        if($this->terminalInfo == null)
        {
            $this->restClient = new RestClient($login, $apiKey);

            $create_terminal = new CREATE_TERMINAL(self::shopName,self::terminalType);

            $create_terminal->description = self::shopDescription;
            $create_terminal->defaultPrice = self::defaultPrice;

            $this->restClient->CreateTerminal($create_terminal);
        }
    }

    public function onPay(array $items, $amount)
    {
        $settings = new SETTINGS($this->terminalInfo->id);
        $order = new ORDER($amount);

        $create_payment = new CREATE_PAYMENT($order, $settings,$items);
        $this->paymentInfo = $this->restClient->CreatePayment($create_payment);

        if($this->paymentInfo == null or $this->paymentInfo->id == null)
        {
            return false;
        }else
        {
            return true;
        }
    }

    public function onCancel($orderId)
    {
        $close_payment = new CLOSE_PAYMENT($orderId);

        $this->restClient->ClosePayment($close_payment);
    }

    public function onRefund($orderID, array $items, string $reason, int $amount)
    {
        $refund_info = new REFUND_INFO($amount, $reason);
        $refund_info->reason = $reason;
        $refund_info->amount = $amount;

        $create_refund = new CREATE_REFUND($orderID,$refund_info);
        $create_refund->receipt = $items;
        $create_refund->refund = $refund_info;

        $this->refundInfo = $this->restClient->CreateRefund($create_refund);

        if($this->refundInfo->error == null)
        {
            return true;
        }else {
            return false;
        }
    }

    public function getStatus(string $orderId)
    {
        $get_payment = new GET_PAYMENT($orderId);
        $this->paymentInfo = $this->restClient->GetPayment($get_payment);

        return $this->paymentInfo->status;
    }

    public function getPayment()
    {
        return $this->paymentInfo;
    }

    public function getTerminal()
    {
        return $this->terminalInfo;
    }

    public function getRefund()
    {
        return $this->refundInfo;
    }
}