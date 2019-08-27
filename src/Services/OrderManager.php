<?php

namespace Chuckki\HvzIframeBundle\Services;

use Chuckki\ContaoHvzBundle\HvzOrderModel;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;

class OrderManager
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * OrderManager constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function createOrderAndSaveToDatabase($arrSubmitted): HvzOrderModel
    {
        $hvzOrder                      = new HvzOrderModel();
        $hvzOrder->tstamp              = time();
        $hvzOrder->hvz_solo_price      = $arrSubmitted['hvz_solo_price'];
        $hvzOrder->hvz_extra_tag       = $arrSubmitted['hvzTagesPreis'];
        $hvzOrder->hvz_rabatt_percent  = $arrSubmitted['Rabatt'];
        $hvzOrder->hvz_preis           = $arrSubmitted['Preis'];
        $hvzOrder->hvzTagesPreis       = $arrSubmitted['hvzTagesPreis'];
        $hvzOrder->hvz_gutscheincode   = $arrSubmitted['rabattCode'];
        $hvzOrder->hvz_rabatt          = $arrSubmitted['rabattValue'];
        $hvzOrder->user_id             = $arrSubmitted['customerId'];
        $hvzOrder->type                = $arrSubmitted['submitType'];
        $hvzOrder->hvz_type            = $arrSubmitted['type'];
        $hvzOrder->hvz_type_name       = $arrSubmitted['Genehmigung'];
        $hvzOrder->hvz_ge_vorhanden    = substr($arrSubmitted['genehmigungVorhanden'], 0, 1);
        $hvzOrder->hvz_ort             = $arrSubmitted['Ort'];
        $hvzOrder->hvz_plz             = $arrSubmitted['PLZ'];
        $hvzOrder->hvz_strasse_nr      = $arrSubmitted['Strasse'];
        $hvzOrder->hvz_vom             = $arrSubmitted['vom'];
        $hvzOrder->hvz_bis             = $arrSubmitted['bis'];
        $hvzOrder->hvz_vom_time        = $arrSubmitted['vomUhrzeit'];
        $hvzOrder->hvz_vom_bis         = $arrSubmitted['bisUhrzeit'];
        $hvzOrder->hvz_anzahl_tage     = $arrSubmitted['wievieleTage'];
        $hvzOrder->hvz_meter           = $arrSubmitted['Meter'];
        $hvzOrder->hvz_fahrzeugart     = $arrSubmitted['Fahrzeug'];
        $hvzOrder->hvz_zusatzinfos     = $arrSubmitted['Zusatzinformationen'];
        $hvzOrder->hvz_grund           = $arrSubmitted['Grund'];
        $hvzOrder->re_anrede           = $arrSubmitted['Geschlecht'];
        $hvzOrder->re_umstid           = $arrSubmitted['umstid'];
        $hvzOrder->re_firma            = $arrSubmitted['firma'];
        $hvzOrder->re_name             = $arrSubmitted['Name'];
        $hvzOrder->re_vorname          = $arrSubmitted['Vorname'];
        $hvzOrder->re_strasse_nr       = $arrSubmitted['strasse_rechnung'];
        $hvzOrder->re_ort_plz          = $arrSubmitted['ort_rechnung'];
        $hvzOrder->re_email            = $arrSubmitted['email'];
        $hvzOrder->re_telefon          = $arrSubmitted['Telefon'];
        $hvzOrder->re_ip               = $arrSubmitted['client_ip'];
        $hvzOrder->re_agb_akzeptiert   = $arrSubmitted['agbakzeptiert'];
        $hvzOrder->ts                  = $arrSubmitted['ts'];
        $hvzOrder->orderNumber         = $arrSubmitted['orderNumber'];
        $hvzOrder->paypal_paymentId    = $arrSubmitted['paypal_id'];
        $hvzOrder->paypal_approvalLink = '';
        $hvzOrder->klarna_session_id   = '';
        $hvzOrder->klarna_client_token = '';
        $hvzOrder->klarna_auth_token   = '';
        $hvzOrder->hvz_id              = $arrSubmitted['hvzID'];
        $hvzOrder->choosen_payment     = \Input::post('Payment');
        $hvzOrder->klarna_order_id     = '';
        $hvzOrder->generateHash();
        $hvzOrder->save();
        return $hvzOrder;
    }


}
