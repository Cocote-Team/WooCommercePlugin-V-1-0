<?php

/**
 * Class CashbackCocote
 */
class CashbackCocote
{
    public $_shopId;
    public $_privateKey;
    public $_email;
    public $_orderId;
    public $_orderPrice;
    public $_priceCurrency;
    public $_orderState;
    public $_skus;

    /**
     * CashbackCocote constructor.
     * @param $shopId
     * @param $privateKey
     * @param $email
     * @param $orderId
     * @param $orderPrice
     * @param $priceCurrency
     * @param $orderState
     * @param $skus
     */
    public function __construct($shopId, $privateKey, $email, $orderId, $orderPrice, $priceCurrency, $orderState, $skus){
        $this->_shopId          = $shopId;
        $this->_privateKey      = $privateKey;
        $this->_email           = $email;
        $this->_orderId         = $orderId;
        $this->_orderPrice      = $orderPrice;
        $this->_priceCurrency   = $priceCurrency ;
        $this->_orderState      = $orderState;
        $this->_skus            = $skus;
    }

    /**
     * send Order To Cocote function
     */
    public function sendOrderToCocote()
    {
        $fp = $this->initializeLog();

        if($this->isCurlLoad()) {
            $this->setLog($fp, "Start function sendOrderToCocote()");
            $elements = explode(',', $this->_skus);
            try {
                $data = array(
                    'shopId' => $this->_shopId,
                    'privateKey' => $this->_privateKey,
                    'email' => $this->_email,
                    'orderId' => $this->_orderId,
                    'orderPrice' => $this->_orderPrice,
                    'priceCurrency' => $this->_priceCurrency,
                    'orderState' => $this->_orderState,
                    'skus' => $elements,
                );

                $this->setLog($fp, "data = ".json_encode($data));

                $start = mktime();

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

                curl_setopt($curl, CURLOPT_URL, "https://fr.cocote.com/api/cashback/request");  // API de prod
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_TIMEOUT, 1);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);

                $result = curl_exec($curl);
                $curl_errno = curl_errno($curl);
                $curl_error = curl_error($curl);
                curl_close($curl);

                if($curl_errno > 0) {
                    $this->setLog($fp, "curl_errno = " . $curl_errno);
                    $this->setLog($fp, "curl_error = " . $curl_error);
                }else{
                    $json_data = json_decode($result);
                    $status = '';
                    $errors = '';
                    if ($json_data != '') {
                        foreach ($json_data as $v) {
                            if ($v->status != '')
                                $status = $v->status;

                            if ($v->errors[0] != '')
                                $errors = $v->errors[0];

                        }
                    }

                    $this->setLog($fp, "Status Curl = " . $status);
                    $this->setLog($fp, "Errors Curl = " . $errors);
                    $this->setLog($fp, "Response Curl = " . $result);

                    $end = mktime();
                    $dure = date("s", $end - $start);
                    $this->setLog($fp, "Durée Curl = " . $dure . " s");
                }

            } catch (Exception $e) {
                $this->setLog($fp, "Error: " . $e->getMessage());
            }

            $this->setLog($fp, "End function sendOrderToCocote()");
            fclose($fp);
        }else{
            $this->setLog($fp, "CURL IS NOT LOAD");
            fclose($fp);
        }
    }

    private function isCurlLoad()
    {
        return extension_loaded('curl') ? true : false;
    }

    private function initializeLog()
    {
        if(! is_dir(__DIR__ . DIRECTORY_SEPARATOR . 'log')) {mkdir (__DIR__ . DIRECTORY_SEPARATOR . 'log', 0755);}

        return fopen(__DIR__ . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'log_' . date('Ymd') . '.log', 'w');
    }

    private function setLog($fp, $message = "")
    {
        fwrite($fp, "[COCOTE DEBUG " . date('Y-m-d H:i:s') . "] " . $message . "\n");
    }
}

if(isset($argv[1]) && isset($argv[2]) && isset($argv[3]) && isset($argv[4]) && isset($argv[5]) && isset($argv[7])) {
    $cashback_cocote = new CashbackCocote($argv[1], $argv[2], $argv[3], $argv[4], number_format($argv[5], 2, '.', ' '), 'EUR', $argv[6], $argv[7]);
    $cashback_cocote->sendOrderToCocote();
}