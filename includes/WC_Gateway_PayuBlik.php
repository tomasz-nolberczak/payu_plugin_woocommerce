<?php

class WC_Gateway_PayuBlik extends WC_PayUGateways
{
    protected $paytype = 'blik';

    function __construct()
    {
        parent::__construct('payublik');

        if ($this->is_enabled()) {
            $this->has_terms_checkbox = true;
            $this->icon = apply_filters('woocommerce_payu_icon', plugins_url( '/assets/images/blik.svg', PAYU_PLUGIN_FILE ));

            if (!is_admin()) {
                if (!$this->try_retrieve_banks()) {
                    add_filter('woocommerce_available_payment_gateways', [$this, 'unset_gateway']);
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function try_retrieve_banks()
    {
        $response = $this->get_payu_response();
        if (isset($response) && $response->getStatus() === 'SUCCESS') {
            $payMethods = $response->getResponse();

            return $payMethods->payByLinks && $this->process_pay_methods($payMethods->payByLinks);
        }

        return false;
    }

    /**
     * @return null
     */
    public function payment_fields()
    {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }
        $response = $this->get_payu_response();
        if (isset($response)
            && $response->getStatus() === 'SUCCESS'
            && $this->retrieve_methods($response)
        ) {
                $this->agreements_field();
        }
    }

    /**
     * @param OpenPayU_Result $response
     *
     * @return bool
     */
    function retrieve_methods($response)
    {
        $payMethods = $response->getResponse();

        return $payMethods->payByLinks && $this->process_pay_methods($payMethods->payByLinks);
    }

    /**
     * @param array $payMethods
     *
     * @return bool
     */
    function process_pay_methods($payMethods)
    {
        foreach ($payMethods as $payMethod) {
            if (!$this->check_min_max($payMethod, $this->paytype)) {
                return false;
            }
        }

        return true;
    }
}
